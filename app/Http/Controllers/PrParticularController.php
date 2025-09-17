<?php

namespace App\Http\Controllers;

use App\Models\PpmpConsolidated;
use App\Models\PpmpTransaction;
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
        #Validate PR particular id
        $particular = PrParticular::find($request->partId);
        if(!$particular) {
            return redirect()->back()->with(['error' => 'Particular ID no found!']);
        }

        #Prepare item quantity on PR
        $pr = PrTransaction::find($particular->pr_id);
        $ppmpTransaction = PpmpTransaction::with([
            'consolidated' => function($item) use ($particular) {
                $item->where('prod_id', $particular->prod_id);
            },
            'purchaseRequests' => function($query) use ($particular) {
                $query->where('pr_status', '!=', 'failed')
                    ->with(['prParticulars' => function ($q) use ($particular) {
                        $q->where('status', '!=', 'failed')
                        ->where('prod_id', $particular->prod_id);
                    }]);
            }
        ])->find($pr->trans_id);

        #Calculate product quantity on pr
        $totalQtyonPr = collect($ppmpTransaction->purchaseRequests)->flatMap(function ($pr) use ($particular) {
            return collect($pr->prParticulars)->filter(function ($item) use ($particular) {
                return $item->id !== $particular->id;
            });
        })->sum('qty');

        #Calculate quantity on ppmp
        $consolidatedItem = collect($ppmpTransaction->consolidated)->first();
        $ppmpFirstQty = (int)$consolidatedItem->qty_first ?? 0;
        $ppmpSecondQty = (int)$consolidatedItem->qty_second ?? 0;
        $totalPpmpQty = $ppmpFirstQty + $ppmpSecondQty;

        #Validate PR particular quantity
        $newQtyOnPr = $totalQtyonPr + $request->prodQty;
        $validateInput = $totalPpmpQty - $newQtyOnPr;
        if($validateInput < 0) {
            $qtyRemaining = $totalPpmpQty - $totalQtyonPr;
            return redirect()->back()->with(['error' => 'Please reduce the quantity for item code# ' . $request->prodCode . '. Only '. $qtyRemaining .' units remain available based on the consolidated PPMP.']);
        }

        DB::beginTransaction();

        try {
            
            #Update Particular
            $particular->update([
                'unitPrice' => (float)$request->prodPrice,
                'unitMeasure' => $request->prodMeasure,
                'qty' => (int)$request->prodQty,
                'revised_specs' => $request->prodDesc,
                'updated_by' => Auth::id(),
            ]);

            #Commit if no error
            DB::commit();
            return redirect()->back()->with(['message' => 'Successfully updated item code# ' . $request->prodCode]);
            
        } catch (\Exception $e) {

            #Rollback if error
            DB::rollBack();
            Log::error($e->getMessage());
            return redirect()->back()->with(['error' => 'Failed to update item code# ' . $request->prodCode]);
        }
    }

    public function moveToTrash(PrParticular $prParticular)
    {
        DB::beginTransaction();
        try {

            #Update then delete
            $prParticular->update([
                'updated_by' => Auth::id(),
            ]);
            $prParticular->delete();

            #Commit
            DB::commit();
            return redirect()->back()->with(['message' => $prParticular->revised_specs . ' move to the trash successfully.']);
        } catch (\Exception $e) {
            
            #Rollback
            DB::rollback();
            Log::error($e->getMessage());
            return redirect()->back()->with(['error' => $prParticular->revised_specs . ' moving to trash failed.']);
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
        #Begin Transaction
        DB::beginTransaction();
        try {

            #Lock table then update
            $prParticular->lockForUpdate;
            $prParticular->update([
                'status' => 'approved',
                'updated_by' => Auth::id(),
            ]);

            #Count remaining particulars under the purchase request
            $countDraftedParticulars = PrTransaction::withCount(['prParticulars as draft_count' => function ($query) {
                $query->where('status', 'draft');
            }])->find($prParticular->pr_id);

            #Validate draft_count if 0
            if($countDraftedParticulars->draft_count == 0) {
                $countDraftedParticulars->lockForUpdate;
                $countDraftedParticulars->update([
                    'pr_status' => 'approved',
                    'updated_by' => Auth::id(),
                ]);
            }

            #Commit
            DB::commit();
            return redirect()->back()->with(['message' => $prParticular->revised_specs . ' approved successfully.']);
        } catch (\Exception $e) {

            #Rollback
            DB::rollBack();
            Log::error($e->getMessage());
            return redirect()->back()->with(['error' => $prParticular->revised_specs . 'approval failed.']);
        }
    }

    public function destroy(PrParticular $prParticular)
    {
        //
    }
}
