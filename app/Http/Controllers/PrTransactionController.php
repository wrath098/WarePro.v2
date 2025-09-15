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
use Illuminate\Support\Str;
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
        $descriptionMap = $this->procurementType();

        $pendingPr = PrTransaction::with('ppmpController', 'updater', 'prParticulars')
            ->where('pr_status', 'draft')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($pr) use ($descriptionMap) {
                $fundIds = json_decode($pr->pr_remarks ?? '[]');
                $fundNames = $this->getFundNames($fundIds);
                $prSemester = $pr->semester == 'qty_first' ? 'First Semester' : 'Second Semester';
                $prDescription = $descriptionMap[$pr->pr_desc] ?? null;

                $details = '<b>Mode: </b>'. $prDescription . '<br>' . '<b>Milestone: </b>'. $prSemester . '<br>';

                $totalAmount = $pr->prParticulars->sum(function ($item) {
                    return floatval($item->unitPrice) * intval($item->qty);
                });

                return [
                    'id' => $pr->id,
                    'prNo' => $pr->pr_no,
                    'prDescription' => $details ?? null,
                    'prStatus' => ucfirst($pr->pr_status),
                    'accountClass' => $fundNames,
                    'createdBy' => $pr->updater->name,
                    'createdAt' => $pr->created_at->format('M d, Y'),
                    'totalAmount' => number_format($totalAmount, 2)
                ];
            });

        return Inertia::render('Pr/Index', [
            'pendingPr' => $pendingPr,
        ]);
    }

    public function showProcurementBasis() 
    {
        #Get and Filter PPMP Transaction with PR
        $ppmpListWithRequests = PpmpTransaction::with(['purchaseRequests', 'updater'])
            ->whereIn('ppmp_type', ['consolidated', 'contingency'])
            ->whereHas('purchaseRequests')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        $ppmpPrNos = $ppmpListWithRequests->mapWithKeys(function ($ppmp) {
            return [$ppmp->id => $ppmp->purchaseRequests->pluck('pr_no')];
        });

        $prNumbersString = $ppmpPrNos->flatten()->implode(', ');

        $formattedList = $ppmpListWithRequests->map(function ($transaction) use ($ppmpPrNos, $prNumbersString) {
            $qtyAdjustment = (int) ((float) $transaction->qty_adjustment * 100);
            $treshAdjustment = (int) ((float) $transaction->tresh_adjustment * 100);
            $details = '<i><b>@</b>'. $qtyAdjustment . '% ' . 'Quantity Adjustment' .  '<br>' 
                    .'<b>@</b>' . $treshAdjustment . '% ' . 'Maximum Adjustment' . '<br>'
                    . ($transaction->remarks ? '<b>@</b>'. $transaction->remarks . '</i>': '</i>');

            return [
                'id' => $transaction->id,
                'code' => $transaction->ppmp_code,
                'type' => ucfirst($transaction->ppmp_type),
                'ppmpYear' => $transaction->ppmp_year,
                'prList' => $prNumbersString,
                'details' => $details,
                'pr' => $transaction->purchaseRequests->count(),
                'createdAt' => $transaction->updated_at->format('F d, Y'),
                'updatedBy' => optional($transaction->updater)->name ?? null,
            ];
        });

        return Inertia::render('Pr/ProcurementBasis', [
            'ppmpList' => $formattedList,
        ]);
    }

    public function showAvailableToPurchase(PpmpTransaction $ppmpTransaction) 
    {
        dd($ppmpTransaction->toArray());
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
            $totalQtyRequested = ($particular->qty_first + $particular->qty_second);

            $totalOnPr = $this->getPrUnderPpmpParticular($particular->id);
            $firstRemaining = $particular->qty_first >= $totalOnPr ? $particular->qty_first - $totalOnPr : 0;
            $secondRemaining = $firstRemaining > 0 ? $particular->qty_second : $totalQtyRequested - $totalOnPr;
            $availableQty = $totalQtyRequested - $totalOnPr;
            
            $description = $this->productService->getProductName($particular->prod_id);

            return [
                'prodId' => $particular->id,
                'prodCode' => $this->productService->getProductCode($particular->prod_id),
                'prodName' => Str::limit($description, 90, '...'),
                'prodUnit' => $this->productService->getProductUnit($particular->prod_id),
                'totalQtyRequested' => number_format($totalQtyRequested, 0, '.', ','),
                'firstQty' => number_format($particular->qty_first, 0, '.', ','),
                'secondQty' => number_format($particular->qty_second, 0, '.', ','),
                'firstRemaining' => number_format($firstRemaining, 0, '.', ','),
                'secondRemaining' => number_format($secondRemaining, 0, '.', ','),
                'availableQty' => number_format($availableQty, 0, '.', ','),
            ];
        });

        $countAvailableToPr = $particulars->filter(function ($item) {
            return $item['availableQty'] > 0;
        })->count();
        
        return Inertia::render('Pr/MonitorParticular', [
            'ppmp' => $ppmp,
            'transaction' => $transaction->purchaseRequests,
            'particulars' => $particulars,
            'countAvailableToPr' => $countAvailableToPr,
        ]);
    }

    public function showParticulars(PrTransaction $prTransaction)
    {
        $descriptionMap = $this->procurementType();

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

        $accountClassIds = json_decode($prTransaction->pr_remarks ?? '[]');
        $prTransaction->pr_remarks = $this->getFundNames($accountClassIds);

        $prTransaction->created_at_formatted = optional($prTransaction->created_at)->format('M d, Y');

        $prTransaction['formattedOverallPrice'] = number_format($grandTotal, 2, '.', ',');
        return Inertia::render('Pr/PendingParticular', [
            'pr' =>  $prTransaction,
            'particulars' => $reformatParticular,
            'trashed' => $resultTrashedParticular,
        ]);
    }
    
    public function showOnProgress()
    {
        $approved = 'approved';
        $prOnProgress = $this->getPrTransactionWithParticulars($approved);

        return Inertia::render('Pr/Inprogress', ['prOnProgress' => $prOnProgress]);
    }

    public function approvedAll(PrTransaction $prTransaction) 
    {
        #Begintransaction
        DB::beginTransaction();

        try{
            #Get PR particulars with 'draft' status
            $updatedCount = $prTransaction->prParticulars()
                ->where('status', 'draft')
                ->update([
                    'status' => 'approved',
                    'updated_by' => Auth::id(),
                ]);
            
            #Update PR Transaction
            $prTransaction->update([
                'pr_status' => 'approved', 
                'updated_by' => Auth::id()
            ]);

            #Commit
            DB::commit();
            return redirect()->route('pr.display.transactions')
                ->with(['message' => "$updatedCount item(s) successfully approved for the selected Purchase Request."]);

        } catch (\Exception $e) {
            #Rollback
            DB::rollBack();
            Log::error("Failed to approve PR transaction ID: {$prTransaction->pr_no}. Error: " . $e->getMessage());
            return redirect()->back()->with(['error' => 'Failed to approve all the items. Please refer to your system administrator.']);
        }
    }

    public function failedAll(PrTransaction $prTransaction)
    {
        #Begin transaction
        DB::beginTransaction();

        try{

            #Count approved pr particular
            $prTransaction->loadCount(['prParticulars as approved_particulars_count' => function ($query) {
                $query->where('status', 'approved');
            }]);
            
            #Get PR Particulars
            $prTransaction->load(['prParticulars' => function($query) {
                $query->withTrashed()
                    ->whereIN('status', ['draft', 'failed']);
            }]);

            #Initialize PR particular
            $particulars = $prTransaction->prParticulars;

            #Validate if PR transaction has no approved particular/s
            if($prTransaction->approved_particulars_count == 0) {

                #Validate PR particulars is not empty
                if ($particulars->isNotEmpty()) {

                    #Force delete particulars
                    PrParticular::whereIn('id', $particulars->pluck('id'))->forceDelete();
                }

                #Force delete transaction
                $prTransaction->forceDelete();

                #Commit
                DB::commit();
                return redirect()->route('pr.display.transactions')
                    ->with(['message' => 'Successfully removed the Purchase Request from the list.']);
            }

            #If PR Transaction has approved particular/s
            #Validate PR particulars is not empty
            if ($particulars->isNotEmpty()) {

                #Force delete particulars
                PrParticular::whereIn('id', $particulars->pluck('id'))->forceDelete();
            }

            #Update PR transaction to approved
            $prTransaction->update([
                'pr_status' => 'approved',
                'updated_by' => Auth::id()
            ]);

            #Commit
            DB::commit();
            return redirect()->route('pr.display.transactions')
                ->with(['message' => 'Transaction moved to purchase order. Unapproved items have been removed.']);
        } catch (\Exception $e) {

            #Rollback
            DB::rollBack();
            Log::error("Failed to mark PR transaction ID: {$prTransaction->id} as failed. Error: " . $e->getMessage());
            return redirect()->back()->with(['error' => 'Failed to mark the Purchase Request as failed. Please refer to your system administrator.']);
        }
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

    private function getPrTransaction($status)
    {
        return PrTransaction::where('pr_status', $status)->get();
    }

    private function getPrTransactionWithParticulars($status)
    {
        $query = $this->getPrTransaction($status);
        $query->load(['prParticulars', 'ppmpController', 'updater']);

        $procurementDescriptions = $this->procurementType();

        $reformatQuery = $query->map(function($transaction) use ($procurementDescriptions) {
            $countItem = $transaction->prParticulars->count();
            $semester = $transaction->semester === 'qty_first' ? 'First Semester' : 'Second Semester';
            return [
                'id' => $transaction->id,
                'prNo' => $transaction->pr_no,
                'ppmpNo' => $transaction->ppmpController->ppmp_code,
                'prType' => $procurementDescriptions[$transaction->pr_desc] ?? null,
                'qtyAdjustment' => (int)((float)$transaction->qty_adjustment * 100),
                'semester' => $semester,
                'updatedAt' => $transaction->updated_at->format('F d, Y'),
                'updatedBy' => $transaction->updater->name ?? '',
                'prStatus' => $transaction->pr_status,
                'noOfItems' => $countItem,
            ];
        });

        return $reformatQuery;
    }

    private function procurementType()
    {
        return [
            'nc' => 'For Bidding',
            'dc' => 'Direct Contract',
            'psdbm' => 'PS-DBM',
            'emergency' => 'Emergency',
        ];
    }
}
