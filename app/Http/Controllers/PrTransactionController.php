<?php

namespace App\Http\Controllers;

use App\Models\PpmpTransaction;
use App\Models\PpmpConsolidated;
use App\Models\PrParticular;
use App\Models\PrTransaction;
use App\Services\ProductService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class PrTransactionController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(): Response
    {
        $descriptionMap = [
            'nc' => 'Non-Contract/Bidding',
            'dc' => 'Direct Contract',
            'psdbm' => 'PS-DBM',
        ];

        $pendingPr = PrTransaction::with('ppmpController', 'updater')
            ->where('pr_status', 'draft')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($pr) use ($descriptionMap) {
                $pr->semester = $pr->semester == 'qty_first' ? 'First Semester' : 'Second Semester';
                $pr->pr_desc = $descriptionMap[$pr->pr_desc] ?? null;
                $pr->qty_adjustment = $pr->qty_adjustment * 100;
                $pr->pr_status = ucfirst($pr->pr_status);
                $pr->formatted_created_at = $pr->created_at->format('m-d-Y');
                return $pr;
            });

        return Inertia::render('Pr/Index', [
            'pendingPr' => $pendingPr,
        ]);
    }

    public function showProcurementBasis() {
        $ppmpList = PpmpTransaction::with(['purchaseRequests', 'updater'])
                ->where(function($query) {
                    $query->where('ppmp_type', 'consolidated')
                        ->orWhere('ppmp_type', 'contingency');
                })
                ->orderBy('created_at', 'desc')
                ->get();

        $ppmpList = $ppmpList->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'code' => $transaction->ppmp_code,
                'ppmpYear' => $transaction->ppmp_year,
                'priceAdjust' => $transaction->price_adjustment ? ((float)$transaction->price_adjustment * 100) : 0,
                'qtyAdjust' => $transaction->qty_adjustment ? ((float)$transaction->qty_adjustment * 100) : 0,
                'version' => $transaction->ppmp_version ?? 'N/A',
                'pr' => $transaction->purchaseRequests->count(),
                'createdAt' => $transaction->created_at->format('Y-m-d H:i:s'),
                'updatedBy' => optional($transaction->updater)->name ?? 'Unknown',
            ];
        });

        return Inertia::render('Pr/ProcurementBasis', [
            'ppmpList' => $ppmpList,
        ]);
    }

    public function showAvailableToPurchase(PpmpTransaction $ppmpTransaction) 
    {
        $transaction = $ppmpTransaction->load(['consolidated', 'purchaseRequests.prParticulars']);

        $total = $transaction->purchaseRequests->map(function($pr) {
            return isset($pr->prParticulars) ? $pr->prParticulars->sum(function($particular) {
                return (float)$particular['unitPrice'] * (int)$particular['qty'];
            }) : 0;
        })->sum();

        $ppmp = [
            'ppmpCode' => $transaction->ppmp_code,
            'ppmpYear' => $transaction->ppmp_year,
            'totalAmount' => $total ? number_format($total, 2, '.', ',') : 0,
            'prCount' => $transaction->purchaseRequests->count(),
        ];

        $particulars = $transaction->consolidated->map(function ($particular) {
            $totalOnPr = $this->getPrUnderPpmpParticular($particular->id);
            $availableQty = ($particular->qty_first + $particular->qty_second) - $totalOnPr;

            return [
                'prodId' => $particular->id,
                'prodCode' => $this->productService->getProductCode($particular->prod_id),
                'prodName' => $this->productService->getProductName($particular->prod_id),
                'prodUnit' => $this->productService->getProductUnit($particular->prod_id),
                'firstQty' => $particular->qty_first,
                'secondQty' => $particular->qty_second,
                'onPrQty' => $totalOnPr,
                'availableQty' => $availableQty,
            ];
        });
        
        return Inertia::render('Pr/MonitorParticular', [
            'ppmp' => $ppmp,
            'transaction' => $transaction->purchaseRequests,
            'particulars' => $particulars,
        ]);
    }

    public function showParticulars(PrTransaction $prTransaction)
    {
        $descriptionMap = [
            'nc' => 'Non-Contract/Bidding',
            'dc' => 'Direct Contract',
            'psdbm' => 'PS-DBM',
        ];

        $prTransaction->load('prParticulars', 'ppmpController', 'updater');
        $prTransaction->semester = $prTransaction->semester === 'qty_first' ? 'First Semester' : 'Second Semester';        
        $prTransaction->pr_desc = $descriptionMap[$prTransaction->pr_desc] ?? null;

        $prTransaction->qty_adjustment = $prTransaction->qty_adjustment * 100;
        $prTransaction->pr_status = ucfirst($prTransaction->pr_status);

        $reformatParticular = $prTransaction->prParticulars->map(function ($particular) {
            $particular->prodCode = $this->productService->getProductCode($particular->prod_id);
            return $particular;
        });

        $trashParticulars = $prTransaction->load(['prParticulars' => function($query) {
            $query->onlyTrashed();
        }]);

        $resultTrashedParticular = $trashParticulars->prParticulars->map(function($particular) {
            $particular->prodCode = $this->productService->getProductCode($particular->prod_id);
            return $particular;
        });

        $prTransaction['totalItems'] = $reformatParticular->count();
        $grandTotal = $reformatParticular->sum(fn($particular) => ((float) $particular->unitPrice * $particular->qty));

        $prTransaction['formattedOverallPrice'] = number_format($grandTotal, 2, '.', ',');
        return Inertia::render('Pr/PendingParticular', [
            'pr' =>  $prTransaction,
            'particulars' => $reformatParticular,
            'trashed' => $resultTrashedParticular,
        ]);
    }

    public function approvedAll(PrTransaction $prTransaction) {
        DB::beginTransaction();

        try{
            $particulars = $this->getAllDraftedParticulars($prTransaction->id);
            if ($particulars->isNotEmpty()) {
                PrParticular::whereIn('id', $particulars->pluck('id'))
                    ->update(['status' => 'approved', 'updated_by' => Auth::id()]);
            }
    
            $prTransaction->lockForUpdate();
            $prTransaction->update(['pr_status' => 'approved', 'updated_by' => Auth::id()]);

            DB::commit();
            return redirect()->route('pr.display.transactions')
                ->with(['message' => "{$particulars->count()} items successfully approved for the selected Purchase Request."]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to approve PR transaction ID: {$prTransaction->id}. Error: " . $e->getMessage());
            return redirect()->back()->with(['error' => 'Failed to approve all the items. Please refer to your system administrator.']);
        }
    }

    public function failedAll(PrTransaction $prTransaction) {
        DB::beginTransaction();

        try{
            $prTransaction->loadCount(['prParticulars' => function ($query) {
                $query->where('status', 'approved');
            }]);
            
            $particulars = $this->getAllDraftedParticulars($prTransaction->id);

            if($prTransaction->pr_particulars_count == 0) {
                if ($particulars->isNotEmpty()) {
                    PrParticular::whereIn('id', $particulars->pluck('id'))->delete();
                }

                $prTransaction->lockForUpdate();
                $prTransaction->update(['updated_by' => Auth::id()]);
                $prTransaction->delete();

                DB::commit();
                return redirect()->route('pr.display.transactions')
                    ->with(['message' => 'Successfully removed the Purchase Request from the list.']);
            }

            if ($particulars->isNotEmpty()) {
                PrParticular::whereIn('id', $particulars->pluck('id'))->delete();
            }

            $prTransaction->lockForUpdate();
            $prTransaction->update(['pr_status' => 'approved', 'updated_by' => Auth::id()]);

            DB::commit();
            return redirect()->route('pr.display.transactions')
                ->with(['message' => 'Successfully marked the items as failed in the selected Purchase Request.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to mark PR transaction ID: {$prTransaction->id} as failed. Error: " . $e->getMessage());
            return redirect()->back()->with(['error' => 'Failed to mark the Purchase Request as failed. Please refer to your system administrator.']);
        }
    }

    private function getAllDraftedParticulars($transactioId) {
        return PrParticular::where('pr_id', $transactioId)
            ->where('status', 'draft')
            ->get();
    }

    private function getPrUnderPpmpParticular($ppmpParticularId)
    {
        $prList = PpmpConsolidated::findOrFail($ppmpParticularId);
        $pr = $prList->load(['purchaseRequest' => function ($query) {
            $query->where('status', '!=', 'failed')
                ->get();
        }]);

        $qtyOnPr = $pr->purchaseRequest->sum('qty');

        return $qtyOnPr ?? 0;
    }

}
