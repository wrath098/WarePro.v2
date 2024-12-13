<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ItemClass;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $category = Category::where('cat_status', 'active')->count();
        $items = ItemClass::where('item_status', 'active')->count();
        $products = Product::where('prod_status', 'active')->count();

        $reorder = Product::where('prod_status', 'active')->with('inventory')->get();

        $reorder = $reorder->map(function($product) {
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

        $reorderCount = $reorder->filter(function($product) {
            return $product['status'] === 'Reorder';
        })->count();

        $core = [
            'category' => $category,
            'item' => $items,
            'product' => $products,
            'redorder' => $reorderCount,
        ];
        
        return Inertia::render('Dashboard', ['core' => $core]);
    }
}
