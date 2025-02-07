<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ItemClass;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Services\ProductService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
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

    public function index(Request $request): Response
    {
        $activeCategories  = Category::with('items')
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
    
        $products = Product::with('itemClass')
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
                'catId' => optional($product->itemClass)->cat_id,
                'itemId' => optional($product->itemClass)->id,
                'itemName' => optional($product->itemClass)->item_name,
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
            return redirect()->route('product.display.active')
                ->with(['message' => 'New Product has been successfully added.']);

        } catch (\Exception $e) {
            
            DB::rollBack();
            Log::error('Creating product failed: ' . $e->getMessage());
            return redirect()->back()
                ->with(['error' => 'Creation of new product failed.']);
        }
    }

    public function update(Request $request)
    {
        $validatedData = $request->validate([
            'prodId' => 'required|integer',
            'prodDesc' => 'required|string',
            'prodPrice' => 'required|numeric',
            'hasExpiry' => 'nullable|integer',
            'updatedBy' => 'required|integer',
        ]);

        try {            
            return DB::transaction(function () use ($validatedData) {
                $latestPrice = $this->productService->getLatestPrice($validatedData['prodId']);
                $hasExpiry = $validatedData['hasExpiry'] ?? 0;

                $product = Product::findOrFail($validatedData['prodId']);
                $product->update(['prod_desc' => $validatedData['prodDesc'], 'updated_by' => $validatedData['updatedBy'], 'has_expiry' => $hasExpiry]);

                if($latestPrice != $validatedData['prodPrice']) {
                    ProductPrice::create([
                        'prod_price' => $validatedData['prodPrice'],
                        'prod_id' => $validatedData['prodId'],
                    ]);
                }
                return redirect()->back()
                    ->with(['message' => 'Product has been updated successfully.']);
            });
        } catch (\Exception $e) {
            Log::error('Product update failed: ' . $e->getMessage());
            return redirect()->back()
                ->with(['error' => 'An error occurred while updating the product.']);
        }
    }

    public function moveAndModify(Request $request)
    {
        $validatedData = $request->validate([
            'prodId' => 'required|integer',
            'selectedCategory' => 'required|integer',
            'itemId' => 'required|integer',
            'prodPrice' => 'required|numeric',
            'prodDesc' => 'required|string',
            'prodUnit' => 'required|string',
            'prodRemarks' => 'required|integer',
            'prodOldCode' => 'nullable|string',
            'hasExpiry'=> 'nullable|integer',
            'updatedBy' => 'nullable|integer',
        ]);

        try {            
            return DB::transaction(function () use ($validatedData) {
                $controlNo = $this->productService->generateStockNo($validatedData['itemId']);

                $product = Product::findOrFail($validatedData['prodId']);
                $product->update(['updated_by' => $validatedData['updatedBy'], 'prod_status' => 'deactivated']);

                $product = Product::create([
                        'prod_newNo' => $controlNo,
                        'prod_desc' => $validatedData['prodDesc'],
                        'prod_unit' => $validatedData['prodUnit'],
                        'prod_remarks' => $validatedData['prodRemarks'],
                        'prod_remarks' => $validatedData['prodRemarks'],
                        'prod_oldNo' => $validatedData['prodOldCode'],
                        'has_expiry' => $validatedData['hasExpiry'] ?? 0,
                        'item_id' => $validatedData['itemId'],
                        'created_by' => $validatedData['updatedBy'],
                        'updated_by' => $validatedData['updatedBy'],
                    ]);

                ProductPrice::create([
                    'prod_price' => $validatedData['prodPrice'],
                    'prod_id' => $product->id,
                ]);
                return redirect()->back()
                    ->with(['message' => 'Product has been updated successfully.']);
            });
        } catch (\Exception $e) {
            Log::error('Product update failed: ' . $e->getMessage());
            return redirect()->back()
                ->with(['error' => 'Modifying the product failed.']);
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

    public function deactivate(Request $request)
    {
        $validatedData = $request->validate([
            'prodId' => 'required|integer',
            'updatedBy' => 'nullable|integer',
        ]);
        
        DB::beginTransaction();

        $product = Product::findOrFail($validatedData['prodId']);
        $product->load('prices');

        try {
            foreach ($product->prices as $price) {
                $price->forceDelete();
            }
            $product->forceDelete();
            
            DB::commit();
            return redirect()->route('product.display.active')
                ->with(['message' => 'Product has been remove successfully.']);
        } catch (\Exception $e) {
            
            $product->update([
                'updated_by' => $validatedData['updatedBy'], 
                'prod_status' => 'deactivated'
            ]);

            DB::commit();
            return redirect()->route('product.display.active')
                ->with(['message' => 'Product has been deactivated.']);
        }
    }

    #FOR UPLOAD OF PRODUCTS FROM EXCEL FILE ONLY
    // public function importProduct()
    // {
    //     $sourcePath = 'd:/Users/User/Downloads/Book13.xlsx';
    //         $filename = 'Book13.xlsx';
    //         $destinationPath = 'uploads/' . $filename;

    //         Storage::disk('local')->put($destinationPath, File::get($sourcePath));
    //         $fullPath = storage_path('app/' . $destinationPath);

    //         $startRow = 0;
    //         $currentRow = 0;
    //         $products = [];
    //         $duplicates = [];

    //     try {
            
    //         (new FastExcel)->import($fullPath, function ($line) use ($startRow, &$currentRow, &$products, &$duplicates){
    //             $currentRow++;

    //             if ($currentRow < $startRow) {
    //                 return null;
    //             }

    //             if(!preg_match("/^\d{4}$/", $line['Code'])) {
    //                 return null;
    //             }


    //             $itemId = $this->productService->getItemId((int)$line['Cat'], (int)$line['Item']);
    //             $controlNo = $this->productService->generateStockNo($itemId);

    //             $product = Product::create([
    //                 'prod_newNo' => $controlNo,
    //                 'prod_desc' => $line['Desc'] ?? 'No Description',
    //                 'prod_unit' => ucfirst($line['Unit']),
    //                 'prod_remarks' => 2025,
    //                 'prod_oldNo' => $line['Code'],
    //                 'item_id' => $itemId,
    //                 'created_by' => Auth::id()
    //             ]);

    //             if($product)
    //             {
    //                 ProductPrice::create([
    //                     'prod_price' => $line['Price'] ? (float)$line['Price'] : 0,
    //                     'prod_id' => $product->id,
    //                 ]);
    //             } else {
    //                 return response()->json(['error' => 'Uploading in error in line ' . $line['Code']]);
    //             }

    //         });

    //         return response()->json(['products' => $products, 'duplicates' => $duplicates, 'count' => $currentRow]);
    //     } catch (\Exception $e) {
    //         Log::error($e->getMessage());
    //         return response()->json(['error', 'Error importing data: ' . $e->getMessage()]);
    //     }
    // }
}
