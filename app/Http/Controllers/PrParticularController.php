<?php

namespace App\Http\Controllers;

use App\Models\PrParticular;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PrParticularController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function show(PrParticular $prParticular)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PrParticular $prParticular)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        try {
            $particular = PrParticular::findOrFail($request->partId);
            $particular->update([
                'unitPrice' => $request->prodPrice,
                'unitMeasure' => $request->prodMeasure,
                'qty' => $request->prodQty,
                'revised_specs' => $request->prodDesc,
                'updated_by' => Auth::id(),
            ]);

            return redirect()->back()->with(['message' => 'Successfully updated the particular. Product Code: ' . $request->prodCode]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return redirect()->back()->with(['error' => 'Failed to update the particular.Product Code: ' . $request->prodCode]);
        }
        
    }

    public function moveToTrash(PrParticular $prParticular)
    {
        try {
            $prodCode = $this->productService->getProductCode($prParticular->prod_id);
            $prParticular->update([
                'updated_by' => Auth::id(),
            ]);
            $prParticular->delete();

            return redirect()->back()->with(['message' => 'Successfully move the particular to the trash. Product Code: ' . $prodCode]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return redirect()->back()->with(['error' => 'Failed to move the particular to the trash. Product Code: ' . $prodCode]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PrParticular $prParticular)
    {
        //
    }
}
