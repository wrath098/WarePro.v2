<?php

namespace App\Http\Controllers;

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
        $inventory = ProductInventory::all();

        $inventory = $inventory->map(function($item) {
            $stockNo = $this->productService->getProductCode($item->prod_id);
            $prodDesc = $this->productService->getProductName($item->prod_id);
            $prodUnit = $this->productService->getProductUnit($item->prod_id);
            $status = $item->qty_on_stock <= $item->reorder_level ? 'Reorder' : 'Available';
            return [
                'id' => $item->id,
                'stockNo' => $stockNo,
                'prodDesc' => $prodDesc,
                'prodUnit' => $prodUnit,
                'stockAvailable' => $item->qty_on_stock,
                'status' => $status,
            ];
        });
        return Inertia::render('Inventory/Index', ['inventory' => $inventory]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductInventory $productInventory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductInventory $productInventory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductInventory $productInventory)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductInventory $productInventory)
    {
        //
    }
}
