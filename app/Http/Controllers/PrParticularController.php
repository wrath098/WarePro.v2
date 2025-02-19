<?php

namespace App\Http\Controllers;

use App\Models\PpmpConsolidated;
use App\Models\PrParticular;
use App\Models\PrTransaction;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrParticularController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function update(Request $request)
    {
        try {
            $particular = PrParticular::findOrFail($request->partId);
            $controller = PpmpConsolidated::findOrFail($particular->conpar_id);
            $purchases = $controller->load('purchaseRequest');
            
            $maxQty = (int) $controller->qty_first + (int) $controller->qty_second;
            $minQty = floor($maxQty * 0.7);

            $qtyPurchases = $purchases->purchaseRequest
                ->filter(function ($item) use ($particular) {
                    return $item->id != $particular->id;
                })
                ->groupBy('prod_id')
                ->map(function ($product) {
                    return (int) $product->sum('qty');
                })
                ->values();
            
            $qtyPurchases = $qtyPurchases->isNotEmpty() ? $qtyPurchases[0] : 0;

            $totalQty = (int)$request->prodQty + (int)$qtyPurchases;
            $availableQty = $maxQty - (int)$qtyPurchases;
            

            if($totalQty < $minQty) {
                $particular->update([
                    'unitPrice' => $request->prodPrice,
                    'unitMeasure' => $request->prodMeasure,
                    'qty' => $request->prodQty,
                    'revised_specs' => $request->prodDesc,
                    'updated_by' => Auth::id(),
                ]);
                return redirect()->back()->with(['message' => 'Successfully updated the particular. Product Code: ' . $request->prodCode]);
            } else if ($totalQty >= $minQty && $totalQty <= $maxQty) {
                $particular->update([
                    'unitPrice' => $request->prodPrice,
                    'unitMeasure' => $request->prodMeasure,
                    'qty' => $request->prodQty,
                    'revised_specs' => $request->prodDesc,
                    'updated_by' => Auth::id(),
                ]);
                return redirect()->back()->with(['message' => 'Successfully updated the particular. Note: Product Code No. ' . $request->prodCode . ' - Quantity is already over 70% of the set maximum quantity limit. Maximum Quantity: ' . $maxQty . ' | Available Quantity: ' . $availableQty]);
            } else {
                return redirect()->back()->with(['error' => 'Failed to update the Product Code: ' . $request->prodCode . ' - Maximum quantity has already been reached or your input exceeds the set maximum quantity limit. Maximum Quantity: ' . $maxQty . ' | Available Quantity: ' . $availableQty]);
            }
            
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return redirect()->back()->with(['error' => 'Failed to update the particular.Product Code: ' . $request->prodCode]);
        }
    }

    public function moveToTrash(PrParticular $prParticular)
    {
        $prodCode = $this->productService->getProductCode($prParticular->prod_id);
        try {
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

    public function restore($prParticular)
    {
        $particular = PrParticular::withTrashed()->find($prParticular);
        $prodCode = $this->productService->getProductCode($particular->prod_id);
        try {
            $particular->restore();
            return redirect()->back()->with(['message' => 'Successfully restored the particular from the trash. Product Code: ' . $prodCode]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return redirect()->back()->with(['error' => 'Failed to restore the particular from the trash. Product Code: ' . $prodCode]);
        }
    }

    public function approve(PrParticular $prParticular)
    {
        DB::beginTransaction();
        try {

            $prParticular->lockForUpdate;
            $prParticular->update([
                'status' => 'approved',
                'updated_by' => Auth::id(),
            ]);

            $countDraftedParticulars = PrTransaction::withCount(['prParticulars as draft_count' => function ($query) {
                $query->where('status', 'draft');
            }])->find($prParticular->pr_id);

            if($countDraftedParticulars->draft_count == 0) {
                $countDraftedParticulars->lockForUpdate;
                $countDraftedParticulars->update([
                    'pr_status' => 'approved',
                    'updated_by' => Auth::id(),
                ]);
            }

            DB::commit();
            return redirect()->back()->with(['message' => 'Successfully approved a product for Purchase Order. Product Code: ' . $prParticular->prod_id]);
        } catch (\Exception $e) {

            DB::rollBack();
            Log::error($e->getMessage());
            return redirect()->back()->with(['error' => 'Failed to approved the particular. Product Code: ' . $prParticular->prod_id]);
        }
    }

    public function destroy(PrParticular $prParticular)
    {
        //
    }
}
