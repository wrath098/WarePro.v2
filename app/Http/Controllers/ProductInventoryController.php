<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductInventory;
use App\Services\ProductService;
use Illuminate\Http\Request;
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
                'stockAvailable' => $qty_on_stock,
                'status' => $status,
                'prodId' => $product->id
            ];
        });
        
        return Inertia::render('Inventory/Index', ['inventory' => $products]);
    }
}
