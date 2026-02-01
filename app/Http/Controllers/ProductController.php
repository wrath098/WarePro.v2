<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ItemClass;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Services\ProductService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Rap2hpoutre\FastExcel\FastExcel;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(): Response
    {
        $activeCategories = Category::with('items')
            ->where('cat_status', 'active')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->cat_name,
                    'items' => $category->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'name' => $item->item_name,
                        ];
                    }),
                ];
        });
    
        $products = Product::with(['itemClass', 'itemClass.category'])
            ->where('prod_status', 'active')
            ->orderBy('item_id', 'asc')
            ->orderBy('prod_desc', 'asc')
            ->get()
            ->map(fn($product) => [
                'id' => $product->id,
                'newNo' => $product->prod_newNo,
                'desc' => $product->prod_desc,
                'unit' => $product->prod_unit,
                'remarks' => $product->prod_remarks,
                'status' => $product->prod_status,
                'price' => $this->productService->getLatestPrice($product->id),
                'oldNo' => $product->prod_oldNo,
                'expiry' => $product->has_expiry == 1 ? 'Yes' : 'No',
                'image_path' => $product->image ? asset('storage/' . $product->image) : '/WarePro.v2/assets/images/no_image.png',
                'catId' => optional($product->itemClass)->cat_id, 
                'itemId' => optional($product->itemClass)->id,
                'itemName' => optional($product->itemClass)->item_name,
                'className' => optional($product->itemClass)->category->cat_name,
            ]);

        return Inertia::render('Product/Index', [
            'products' => $products, 
            'categories' => $activeCategories ,
            'authUserId' => Auth::id()
        ]);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        $validatedData = $request->validate([
            'selectedCategory' => 'required|integer',
            'itemId' => 'required|integer',
            'prodPrice' => 'required|numeric',
            'prodDesc' => 'required|string',
            'prodUnit' => 'required|string',
            'prodRemarks' => 'required|integer',
            'prodOldCode' => 'nullable|string',
            'hasExpiry'=> 'nullable|integer',
            'createdBy' => 'nullable|integer',
        ]);

        try 
        {         
            $controlNo = $this->productService->generateStockNo($validatedData['itemId']);

            $product = Product::create([
                'prod_newNo' => $controlNo,
                'prod_desc' => $validatedData['prodDesc'],
                'prod_unit' => $validatedData['prodUnit'],
                'prod_remarks' => $validatedData['prodRemarks'],
                'prod_oldNo' => $validatedData['prodOldCode'],
                'has_expiry' => $validatedData['hasExpiry'] ?? 0,
                'item_id' => $validatedData['itemId'],
                'created_by' => $validatedData['createdBy'],
                'updated_by' => $validatedData['createdBy'],
            ]);

            ProductPrice::create([
                'prod_price' => $validatedData['prodPrice'],
                'prod_id' => $product->id,
            ]);

            DB::commit();
            return redirect()->back()
                ->with('message', 'New Product has been successfully added.');

        } catch (\Exception $e) {

            DB::rollBack();
            Log::error("Creation of New Product Failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'data' => $validatedData
            ]);
            return back()->with('error', 'Creation of New Product Failed. Please try again!');
        }
    }

    public function update(Request $request)
    {
        DB::beginTransaction();

        $validatedData = $request->validate([
            'prodId' => 'required|integer',
            'prodDesc' => 'required|string',
            'prodPrice' => 'required|numeric',
            'hasExpiry' => 'nullable|integer',
            'prodOldCode' => 'nullable|string',
            'updatedBy' => 'required|integer',
        ]);

        try {
            $product = Product::where('id', $validatedData['prodId'])->lockForUpdate()->firstOrFail();
            $latestPrice = $this->productService->getLatestPrice($validatedData['prodId']);

            $isOLdCodeFound = $validatedData['prodOldCode'] ? $this->verifyOldStockNo($validatedData['prodOldCode'], $product->id) : '';

            if ($validatedData['prodOldCode'] && $isOLdCodeFound) {
                DB::rollBack();
                return back()->withInput()->withErrors([
                    'prodOldCode' => 'Product Old Stock No is already in used by stock no# ' . $isOLdCodeFound->prod_newNo
                ]);
            }

            $product->update([
                'prod_desc' => $validatedData['prodDesc'],
                'updated_by' => $validatedData['updatedBy'],
                'has_expiry' => $validatedData['hasExpiry'],
                'prod_oldNo' => $validatedData['prodOldCode'],
            ]);

            if($latestPrice != $validatedData['prodPrice']) {
                ProductPrice::create([
                    'prod_price' => $validatedData['prodPrice'],
                    'prod_id' => $validatedData['prodId'],
                ]);
            }

            DB::commit();
            return redirect()->back()
                ->with('message', 'Product Information updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Updating Product Information Failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'data' => $validatedData
            ]);
            return back()->with('error', 'Updating Product Information Failed. Please try again!');
        }
    }

    public function uploadProductImage(Request $request) {
        $request->validate([
            'prodId' => 'required|integer',
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        DB::beginTransaction();
        $product = Product::find($request->prodId);

        try {

            if($product->image) {
                Storage::disk('public')->delete($product->image);
                $product->image = null;
                $product->save();
            }

            if ($request->hasFile('file')) {
                $filename = Str::uuid() . '.' . $request->file('file')->extension();
                $path = $request->file('file')->storeAs('product_image', $filename, 'public');
                $product->image = $path;
                $product->save();
            }

            DB::commit();
                return redirect()->back()->with(['message' => 'Product image has been uploaded successfully.']);
        } catch(\Exception $e) {
            DB::rollBack();
            Log::error("Product Image Upload Failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'data' => $product->prod_desc
            ]);
            return back()->with('error', 'Product Image Upload Failed. Please try again!');
        }
    }

    public function moveAndModify(Request $request)
    {
        DB::beginTransaction();

        $validatedData = $request->validate([
            'prodId' => 'required|integer',
            'selectedCategory' => 'required|integer',
            'itemId' => 'required|integer',
            'prodPrice' => 'required',
            'prodDesc' => 'required|string',
            'prodUnit' => 'required|string',
            'prodRemarks' => 'required|integer',
            'prodOldCode' => 'nullable|string',
            'hasExpiry'=> 'nullable|integer',
            'updatedBy' => 'nullable|integer',
        ]);

        try {            
                $controlNo = $this->productService->generateStockNo($validatedData['itemId']);
                $product = Product::where('id', $validatedData['prodId'])->lockForUpdate()->firstOrFail();

                $product->fill([
                        'prod_newNo' => $controlNo,
                        'prod_oldNo' => $product->prod_newNo,
                        'item_id' => $validatedData['itemId'],
                        'updated_by' => $validatedData['updatedBy'],
                    ]);
                $product->save();

                DB::commit();
                return redirect()->back()
                    ->with('message', 'Product has been move successfully.');
        } catch (\Exception $e) {

            DB::rollBack();
            Log::error("Modifying Product Failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'data' => $validatedData
            ]);
            return back()->with('error', 'Modifying Product Failed. Please try again!');
        }
    }

    public function showPriceList(Request $request)
    {
        $products = Product::with(['itemClass', 'prices' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(5);
            }])
            ->where('prod_status', 'active')
            ->orderBy('item_id', 'asc')
            ->orderBy('prod_desc', 'asc')
            ->get()
            ->map(fn($product) => [
            'id' => $product->id,
            'newNo' => $product->prod_newNo,
            'desc' => $product->prod_desc,
            'unit' => $product->prod_unit,
            'remarks' => $product->prod_remarks,
            'status' => $product->prod_status,
            'price' => array_pad(
                        $product->prices->pluck('prod_price')->toArray(),
                        5,
                        0.0
                    ),
            'oldNo' => $product->prod_oldNo,
            'itemName' => $this->productService->getItemName($product->item_id),
        ]);
        
        return Inertia::render('Product/PriceList', [
            'products' => $products,
        ]);
    }

    public function restore(Request $request)
    {
        $id = $request->prodId;
        $user = Auth::id();

        DB::beginTransaction();
        try {
            $query = Product::with('itemClass')->where('id', $id)->lockForUpdate()->firstOrFail();
            
            if ($query->itemClass->item_status == 'deactivated')
            {
                DB::rollBack();
                return back()->with(
                    'error', 'Unable to restore the selected product. Item Class is currently not active!'
                );
            }

            $query->update(['prod_status' => 'active', 'updated_by' => $user]);
            DB::commit();
            return redirect()->back()
                    ->with('message', 'Product has been restored successfully.');
        } catch (\Exception $e) {

            DB::rollBack();
            Log::error("Restoring Product Failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'data' => $request
            ]);
            return back()->with('error', 'Restoring Product Failed. Please try again!');
        }
    }

    public function deactivate(Request $request)
    {
        DB::beginTransaction();
        
        $validatedData = $request->validate([
            'prodId' => 'required|integer',
            'updatedBy' => 'nullable|integer',
        ]);

        $product = Product::with('prices')->where('id', $validatedData['prodId'])->lockForUpdate()->firstOrFail();

        try {
            foreach ($product->prices as $price) {
                $price->forceDelete();
            }
            $product->forceDelete();
            
            DB::commit();
            return redirect()->back()
                ->with('message', 'Product has been deleted successfully.');
        } catch (\Exception $e) {
            
            $product->fill([
                'prod_status' => 'deactivated',
                'updated_by' => $validatedData['updatedBy'],
            ]);

            if ($product->save()) {
                DB::commit();
                return redirect()->back()
                    ->with('warning', 'The product has been moved to the trash. Unable to delete!');
            }

            DB::rollBack();
            Log::error("Deletion of Product Failed: " . $product->prod_newNo, [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'data' => $validatedData
            ]);
            return back()->with('error', 'Deletion of Product Failed. Please try again!');
        }
    }

    public function getTrashedItems()
    {
        $query = Product::where('prod_status', 'deactivated')
            ->with(['updater', 'itemClass'])
            ->orderBy('prod_desc', 'asc')
            ->get()
            ->map(fn($product) => [
                'id' => $product->id,
                'stockNo' => $product->prod_newNo,
                'desc' => $product->prod_desc,
                'unit' => $product->prod_unit,
                'usedSince' => $product->prod_remarks,
                'oldNo' => $product->prod_oldNo,
                'updatedAt' => $product->updated_at->format('F j, Y'),
                'updatedBy' => $product->updater->name,
            ]);
        
        return response()->json(['data' => $query]);
    }

    public function filterProductCatalog(Request $request): JsonResponse
    {   
        $category = strtolower($request->category);
        $item = strtolower($request->item);

        $findCategory = Category::whereRaw('LOWER(cat_name) = ?', [$category])->first();

        $findItem = ItemClass::with(['products' => function($product) {
                $product->where('prod_status', 'active');
            }, 'category'])
            ->when($findCategory, function($query) use ($findCategory) {
                $query->where('cat_id', $findCategory->id);
            })
            ->when($item, function($query) use ($item) {
                $query->whereRaw('LOWER(item_name) = ?', [$item]);
            })
            ->get();

        $products = $findItem->flatMap(fn($item) =>
            $item->products->map(function($product) use ($item) {
                return [
                    'id' => $product->id,
                    'newNo' => $product->prod_newNo,
                    'desc' => $product->prod_desc,
                    'unit' => $product->prod_unit,
                    'remarks' => $product->prod_remarks,
                    'status' => $product->prod_status,
                    'price' => $this->productService->getLatestPrice($product->id),
                    'oldNo' => $product->prod_oldNo,
                    'expiry' => $product->has_expiry == 1 ? 'Yes' : 'No',
                    'image_path' => $product->image ? asset('storage/' . $product->image) : '/WarePro.v2/assets/images/no_image.png',
                    'catId' => optional($product->itemClass)->cat_id,
                    'itemId' => optional($product->itemClass)->id,
                    'itemName' => optional($product->itemClass)->item_name,
                    'className' => optional($product->itemClass)->category->cat_name,
                ];
            })
        );

        return response()->json(['data' => $products->values()]);
    }

    public function searchProductCatalog(Request $request){
        $query = Product::with(['updater', 'itemClass', 'itemClass.category'])
            ->where('prod_status', 'active')
            ->where('prod_desc', 'LIKE', "%{$request->search}%")
            ->orWhere('prod_newNo', 'LIKE', "%{$request->search}%")
            ->get()
            ->map(function($product) {
                    return [
                        'id' => $product->id,
                        'newNo' => $product->prod_newNo,
                        'desc' => $product->prod_desc,
                        'unit' => $product->prod_unit,
                        'remarks' => $product->prod_remarks,
                        'status' => $product->prod_status,
                        'price' => $this->productService->getLatestPrice($product->id),
                        'oldNo' => $product->prod_oldNo,
                        'expiry' => $product->has_expiry == 1 ? 'Yes' : 'No',
                        'image_path' => $product->image ? asset('storage/' . $product->image) : '/WarePro.v2/assets/images/no_image.png',
                        'catId' => optional($product->itemClass)->cat_id,
                        'itemId' => optional($product->itemClass)->id,
                        'itemName' => optional($product->itemClass)->item_name,
                        'className' => optional($product->itemClass)->category->cat_name,
                    ];
                });

        return response()->json(['data' => $query->values()]);
    }

    public function filterProductById(Product $prodId)
    {
        return response()->json(['data' => $prodId]);
    }

    private function verifyOldStockNo(string $oldStockNo, int $productId): ?Product
    {
        $query = Product::where('prod_oldNo', $oldStockNo);
        if ($productId !== null) {
            $query = $query->where('id', '!=', $productId);
        }
        return $query->first();
    }

    #FOR UPLOAD OF PRODUCTS FROM EXCEL FILE ONLY
    // public function importProduct()
    // {
    //     $sourcePath = 'd:/Users/User/Downloads/Book13.xlsx';
    //     $filename = 'Book13.xlsx';
    //     $destinationPath = 'uploads/' . $filename;

    //     Storage::disk('local')->put($destinationPath, File::get($sourcePath));
    //     $fullPath = storage_path('app/' . $destinationPath);

    //     $startRow = 0; // Assuming 1 to skip header row
    //     $currentRow = 0;
    //     $productsUpdated = 0;
    //     $productsSkipped = 0;

    //     try {
    //         (new FastExcel)->import($fullPath, function ($line) use ($startRow, &$currentRow, &$productsUpdated, &$productsSkipped) {
    //             $currentRow++;

    //             if ($currentRow < $startRow) {
    //                 return null; // Skip rows before startRow
    //             }

    //             $newStock = $line['New_Stock_No'] ?? null;
    //             $price = $line['Price'] ?? null;

    //             Log::info($newStock);

    //             // Validate stock number format
    //             if (!preg_match("/^\d{2}-\d{2}-\d{2,4}$/", $newStock)) {
    //                 $productsSkipped++;
    //                 return null;
    //             }

    //             $product = Product::where('prod_newNo', $newStock)->first();
    //             if (!$product) {
    //                 $productsSkipped++;
    //                 return null; // No product found, skip
    //             }

    //             $latestPrice = (float) $this->productService->getLatestPrice($product->id);
    //             $reformatPrice = (float) $price;

    //             if ($latestPrice !== $reformatPrice) {
    //                 ProductPrice::create([
    //                     'prod_price' => $reformatPrice,
    //                     'prod_id' => $product->id,
    //                 ]);
    //                 $productsUpdated++;
    //             } else {
    //                 $productsSkipped++;
    //             }
    //         });

    //         return response()->json([
    //             'rows_processed' => $currentRow,
    //             'prices_updated' => $productsUpdated,
    //             'rows_skipped' => $productsSkipped,
    //         ]);
    //     } catch (\Exception $e) {
    //         Log::error($e->getMessage());
    //         return response()->json(['error' => 'Error importing data: ' . $e->getMessage()], 500);
    //     }
    // }
}
