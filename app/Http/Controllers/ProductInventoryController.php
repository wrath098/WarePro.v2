<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductInventory;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ProductInventoryController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(): Response
    {
        $products = Product::where('prod_status', 'active')->with('inventory')->get();

        $products = $products->map(function($product) {
            $inventory = $product->inventory;
            $status = 'Reorder';
            $qty_on_stock = 0;
            $reorder_level = 0;

            if ($inventory) {
                $qty_on_stock = $inventory->qty_on_stock;
                $reorder_level = $inventory->reorder_level;
                $status = $qty_on_stock <= $reorder_level ? 'Reorder' : 'Available';
            }
        
            return [
                'id' => $inventory ? $inventory->id : null,
                'stockNo' => $product->prod_newNo,
                'prodDesc' => $product->prod_desc,
                'prodUnit' => $product->prod_unit,
                'beginningBalance' => $inventory->qty_physical_count ?? 0,
                'stockAvailable' => $qty_on_stock,
                'purchases' => $inventory->qty_purchase ?? 0,
                'issuances' => $inventory->qty_issued ?? 0,
                'status' => $status,
                'prodId' => $product->id
            ];
        });
        
        return Inertia::render('Inventory/Index', ['inventory' => $products]);
    }

    public function showStockCard(Request $request)
    {
        $productList = $this->productListInventory();
        return Inertia::render('Inventory/StockCard', ['productList' => $productList]);
    }

    public function getProductInventoryLogs(Request $request)
    {
        $productDetails = $this->getProductInventory($request->product['prodId']);

        Log::info('Request Data:', $productDetails->toArray());

        return response()->json(['data' => $productDetails], 200);
    }

    public function productListInventory()
    {
        $productList = [];
        $groupOfProductId = ProductInventory::pluck('prod_id')->all();

        foreach($groupOfProductId as $productId) {
            $product = $this->getProductDetails($productId);
            $productList[] = $product;
        }

        return $productList;
    }

    private function getProductDetails($productId)
    {
        $productDetail = Product::withTrashed()->findOrFail($productId);
        
        return [
            'prodId' => $productDetail->id,
            'prodDesc' => $productDetail->prod_desc,
            'prodUnit' => $productDetail->prod_unit,
            'prodStockNo' => $productDetail->prod_newNo,
        ];
    }

    private function getProductInventory($productId)
    {
        return ProductInventory::withTrashed()->findOrFail($productId);
    }
}
