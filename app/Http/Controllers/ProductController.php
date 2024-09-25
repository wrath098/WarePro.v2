<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
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
    
        $products = Product::query()
            ->when($search, function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('prod_newNo', 'like', '%' . $search . '%')
                      ->orWhere('prod_desc', 'like', '%' . $search . '%')
                      ->orWhere('prod_oldNo', 'like', '%' . $search . '%')
                      ->orWhereHas('itemClass', function ($q) use ($search) {
                            $q->where('item_name', 'like', '%' . $search . '%');
                        });
                });
            }, function ($query) {})
            ->with('itemClass')
            ->where('prod_status', 'active')
            ->orderBy('item_id', 'asc')
            ->orderBy('prod_desc', 'asc')
            ->paginate(10)
            ->through(fn($product) => [
                'id' => $product->id,
                'newNo' => $product->prod_newNo,
                'desc' => $product->prod_desc,
                'unit' => $product->prod_unit,
                'remarks' => $product->prod_remarks,
                'status' => $product->prod_status,
                'price' => $this->productService->getLatestPrice($product->id),
                'oldNo' => $product->prod_oldNo,
                'catId' => optional($product->itemClass)->cat_id,
                'itemId' => optional($product->itemClass)->id,
                'itemName' => optional($product->itemClass)->item_name,
            ]);


        return Inertia::render('Product/Index', [
            'products' => $products, 
            'categories' => $activeCategories ,
            'filters' => $request->only(['search']), 
            'authUserId' => Auth::id()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'selectedCategory' => 'required|integer',
            'itemId' => 'required|integer',
            'prodPrice' => 'required|numeric',
            'prodDesc' => 'required|string',
            'prodUnit' => 'required|string',
            'prodRemarks' => 'required|integer',
            'prodOldCode' => 'nullable|string',
            'createdBy' => 'nullable|integer',
        ]);

        try {            
            return DB::transaction(function () use ($validatedData) {
                $controlNo = $this->productService->generateStockNo($validatedData['itemId']);

               $product = Product::create([
                    'prod_newNo' => $controlNo,
                    'prod_desc' => $validatedData['prodDesc'],
                    'prod_unit' => $validatedData['prodUnit'],
                    'prod_remarks' => $validatedData['prodRemarks'],
                    'prod_remarks' => $validatedData['prodRemarks'],
                    'prod_oldNo' => $validatedData['prodOldCode'],
                    'item_id' => $validatedData['itemId'],
                    'created_by' => $validatedData['createdBy'],
                ]);

                ProductPrice::create([
                    'prod_price' => $validatedData['prodPrice'],
                    'prod_id' => $product->id,
                ]);
                return redirect()->route('product.display.active')->with(['message' => 'New Product has been successfully added.']);
            });
        } catch (\Exception $e) {
            Log::error('Product update failed: ' . $e->getMessage());
            return redirect()->route('product.display.active')->with(['error' => 'An error occurred while adding the product.']);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $validatedData = $request->validate([
            'prodId' => 'required|integer',
            'prodDesc' => 'required|string',
            'prodPrice' => 'required|numeric',
            'updatedBy' => 'required|integer',
        ]);

        try {            
            return DB::transaction(function () use ($validatedData) {
                $latestPrice = $this->productService->getLatestPrice($validatedData['prodId']);

                $product = Product::findOrFail($validatedData['prodId']);
                $product->update(['prod_desc' => $validatedData['prodDesc']]);

                if($latestPrice != $validatedData['prodPrice']) {
                    ProductPrice::create([
                        'prod_price' => $validatedData['prodPrice'],
                        'prod_id' => $validatedData['prodId'],
                    ]);
                }
                return redirect()->route('product.display.active')->with(['message' => 'Product has been updated successfully.']);
            });
        } catch (\Exception $e) {
            Log::error('Product update failed: ' . $e->getMessage());
            return redirect()->route('product.display.active')->with(['error' => 'An error occurred while updating the product.']);
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
                        'item_id' => $validatedData['itemId'],
                        'created_by' => $validatedData['updatedBy'],
                    ]);

                ProductPrice::create([
                    'prod_price' => $validatedData['prodPrice'],
                    'prod_id' => $product->id,
                ]);
                return redirect()->route('product.display.active')->with(['message' => 'Product has been updated successfully.']);
            });
        } catch (\Exception $e) {
            Log::error('Product update failed: ' . $e->getMessage());
            return redirect()->route('product.display.active')->with(['error' => 'An error occurred while adding the product.']);
        }
    }

    public function showPriceList(Request $request)
    {
        $search = $request->input('search');
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
    
        $products = Product::query()
            ->when($search, function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('prod_newNo', 'like', '%' . $search . '%')
                      ->orWhere('prod_desc', 'like', '%' . $search . '%')
                      ->orWhere('prod_oldNo', 'like', '%' . $search . '%')
                      ->orWhereHas('itemClass', function ($q) use ($search) {
                            $q->where('item_name', 'like', '%' . $search . '%');
                        });
                });
            }, function ($query) {})
            ->with('itemClass')
            ->where('prod_status', 'active')
            ->orderBy('item_id', 'asc')
            ->orderBy('prod_desc', 'asc')
            ->paginate(10)
            ->through(fn($product) => [
                'id' => $product->id,
                'newNo' => $product->prod_newNo,
                'desc' => $product->prod_desc,
                'unit' => $product->prod_unit,
                'remarks' => $product->prod_remarks,
                'status' => $product->prod_status,
                'price' => $this->productService->getLatestPrice($product->id),
                'oldNo' => $product->prod_oldNo,
                'catId' => optional($product->itemClass)->cat_id,
                'itemId' => optional($product->itemClass)->id,
                'itemName' => optional($product->itemClass)->item_name,
            ]);


        return Inertia::render('Product/PriceList', [
            'products' => $products, 
            'categories' => $activeCategories ,
            'filters' => $request->only(['search']), 
            'authUserId' => Auth::id()
        ]);
    }

    public function deactivate(Request $request)
    {
        $validatedData = $request->validate([
            'prodId' => 'required|integer',
            'updatedBy' => 'nullable|integer',
        ]);
        
        try {
            $product = Product::findOrFail($validatedData['prodId']);
            $product->update([
                'updated_by' => $validatedData['updatedBy'], 
                'prod_status' => 'deactivated'
            ]);
            return redirect()->route('product.display.active')->with(['message' => 'Product has been remove successfully.']);
        } catch (\Exception $e) {
            Log::error('Product update failed: ' . $e->getMessage());
            return redirect()->route('product.display.active')->with(['error' => 'An error occurred while adding the product.']);
        }
    }
}
