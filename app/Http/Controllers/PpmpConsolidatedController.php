<?php

namespace App\Http\Controllers;

use App\Models\PpmpConsolidated;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PpmpConsolidatedController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function update(Request $request, PpmpConsolidated $ppmpConsolidated)
    {
        try {
            $ppmpConsolidated->update([
                'qty_first' => (int)$request->firstQty, 
                'qty_second' => (int)$request->secondQty,
                'updated_by' => $request->user,
            ]);
            return redirect()->back()->with(['message' => 'Product No. '. $request->prodCode . ' updated successfully!']);
        } catch(\Exception $e) {
            return redirect()->back()->with(['error' => 'Product No. '. $request->prodCode . ' updating failed!']);
        }
    }

    public function destroy(PpmpConsolidated $ppmpConsolidated)
    {
        $prodCode = $this->productService->getProductCode($ppmpConsolidated->prod_id);
        try {
            $ppmpConsolidated->update([
                'updated_by' => Auth::id(),
            ]);

            $ppmpConsolidated->delete();
            return redirect()->back()->with(['message' => 'Product No. '. $prodCode . ' successfully moved to trash!']);
        } catch(\Exception $e) {
            return redirect()->back()->with(['error' => 'Product No. '. $prodCode . ' failed to move to trash!']);
        }
    }
}
