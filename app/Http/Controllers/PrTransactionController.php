<?php

namespace App\Http\Controllers;

use App\Models\PpmpTransaction;
use App\Models\PrParticular;
use App\Models\PrTransaction;
use App\Services\ProductService;
use Illuminate\Http\Request;
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
        $pendingPr = PrTransaction::with('ppmpController', 'updater')
            ->where('pr_status', 'draft')
            ->get()
            ->map(function ($pr) {
                $pr->semester = $pr->semester == 'qty_first' ? 'First Semester' : 'Second Semester';
                if ($pr->pr_desc == 'nc') {
                    $pr->pr_desc = 'Non-Contract';
                } elseif ($pr->pr_desc == 'dc') {
                    $pr->pr_desc = 'Direct Contract';
                } elseif ($pr->pr_desc == 'psdbm') {
                    $pr->pr_desc = 'PS-DBM';
                } else {
                    $pr->pr_desc = null;
                }

                $pr->qty_adjustment = $pr->qty_adjustment * 100;
                return $pr;
            });

        return Inertia::render('Pr/Index', [
            'pendingPr' => $pendingPr,
        ]);
    }

    public function store(Request $request)
    { 
        DB::beginTransaction();
        Log::info($request->all());

        try {
            $ppmpTransactions = $this->getIndividualWithItems($request->selectedYear);
            $consoTransaction = $this->getConsolidatedTransactionDetails($request);

            $priceAdjustment = $ppmpTransactions->first()->price_adjustment;
            $semester = $request->semester;
            $qtyAdjustment = $request->qtyAdjust / 100;
            $userId = Auth::id();

            $purchaseRequestInformation = [
                'semester' => $semester,
                'qty_adjustment' => $qtyAdjustment,
                'trans_id' => $consoTransaction->id,
                'user' => $userId,
            ];

            $createPrTransact = $this->createNewPurchaseRequest($purchaseRequestInformation);
    
            $groupParticulars = $ppmpTransactions->flatMap(function ($transaction) use ($consoTransaction) {
                return $transaction->particulars;
            })->groupBy('prod_id')->map(function ($items) use ($consoTransaction, $priceAdjustment, $semester, $qtyAdjustment, $createPrTransact, $userId){
                $prodPrice = (float)$this->productService->getLatestPriceId($items->first()->price_id) * $priceAdjustment;
                $prodPrice = $prodPrice != null ? (float) ceil($prodPrice) : 0;

                $modifiedItems = $items->map(function ($particular) use ($semester, $priceAdjustment, $qtyAdjustment, $createPrTransact, $userId) {
                    $isExempted = $this->productService->validateProductExcemption($particular->prod_id, $particular->transaction->ppmp_year);
                    $qty = $semester === 'qty_first' ? (int) $particular->qty_first : (int) $particular->qty_second;
            
                    $modifiedQtyFirst = !$isExempted && $qty > 1
                                        ? floor($qty * $qtyAdjustment)
                                        : $qty;
            
                    $particular->modifiedQuantity = $modifiedQtyFirst;
            
                    return $particular;
                });
                $productQtyForPr = $items->sum('modifiedQuantity');
                $totalQuantityOnPurchases = $this->getAllProductQuantityFromPurchaseRequest($consoTransaction, $items->first()->prod_id);
                $totalQuantityAvailableToPurchase = $this->getAllProductQuantityFromPpmp($consoTransaction, $items->first()->prod_id);

                $calcAvailability = $totalQuantityAvailableToPurchase - $totalQuantityOnPurchases;

                if ($productQtyForPr > 0 && $productQtyForPr <= $calcAvailability) {
                    PrParticular::create([
                        'prod_id' => $items->first()->prod_id,
                        'unitPrice' => (float)$prodPrice,
                        'unitMeasure' => $this->productService->getProductUnit($items->first()->prod_id),
                        'qty' => (int)$productQtyForPr,
                        'revised_specs' => $this->productService->getProductName($items->first()->prod_id),
                        'pr_id' => $createPrTransact->id,
                        'updated_by' => $userId,
                    ]);
                }
            });

            DB::commit();
            return response()->json(['message' => 'Successfully Created.'], 200);
    } catch (\Exception $e) {
        DB::rollBack();
            Log::error($e->getMessage());
            return response()->json(['error' => 'An error occurred while processing your request.'], 500);
        }
    }

    public function showParticulars(PrTransaction $prTransaction)
    {
        $descriptionMap = [
            'nc' => 'Non-Contract',
            'dc' => 'Direct Contract',
            'psdbm' => 'PS-DBM',
        ];

        $prTransaction->load('prParticulars', 'ppmpController', 'updater');
        $prTransaction->semester = $prTransaction->semester === 'qty_first' ? 'First Semester' : 'Second Semester';        
        $prTransaction->pr_desc = $descriptionMap[$prTransaction->pr_desc] ?? null;

        $prTransaction->qty_adjustment = $prTransaction->qty_adjustment * 100;

        $reformatParticular = $prTransaction->prParticulars->map(function ($particular) {
            $particular->prodCode = $this->productService->getProductCode($particular->prod_id);
            return $particular;
        });

        $prTransaction['totalItems'] = $reformatParticular->count();
        $grandTotal = $reformatParticular->sum(fn($particular) => ((float) $particular->unitPrice * $particular->qty));

        $prTransaction['formattedOverallPrice'] = number_format($grandTotal, 2, '.', ',');
        return Inertia::render('Pr/PendingParticular', [
            'pr' =>  $prTransaction,
            'particulars' => $reformatParticular,
        ]);
    }

    private function getIndividualWithItems($year) {
        $results = PpmpTransaction::with('particulars')
                ->where('ppmp_status', 'approved')
                ->where('ppmp_type', 'individual')
                ->where('ppmp_year', $year)
                ->get();
        return $results ?? '';
    }

    private function getConsolidatedTransactionDetails($request) {
        $result = PpmpTransaction::where('ppmp_status', 'approved')
                ->where('ppmp_type', $request->selectedType)
                ->where('ppmp_year', $request->selectedYear)
                ->first();
        return $result ?? '';
    }

    private function createNewPurchaseRequest($request) {
        return PrTransaction::create([
            'pr_no' => now()->format('YmdHis'),
            'semester' => $request['semester'],
            'qty_adjustment' => $request['qty_adjustment'],
            'trans_id' => $request['trans_id'],
            'created_by' => $request['user'],
            'updated_by' => $request['user'],
        ]);
    }

    private function getAllProductQuantityFromPurchaseRequest($ppmpTransaction, $id) {
        $ppmpTransaction->load(['purchaseRequests.prParticulars' => function($query) use ($id) {
            $query->where('prod_id', $id)
                ->whereIn('status', ['Pending', 'Partial', 'Completed']);
        }]);

        $quantities = $ppmpTransaction->purchaseRequests->flatMap(function ($purchaseRequest) {
            return $purchaseRequest->prParticulars->pluck('qty');
        })->sum();

        return $quantities;
    }

    private function getAllProductQuantityFromPpmp($ppmpTransaction, $id) {
        $officeRequests = PpmpTransaction::with(['particulars' => function($query) use ($id) {
                    $query->where('prod_id', $id);
                }])
                ->where('ppmp_status', 'approved')
                ->where('ppmp_type', 'individual')
                ->where('ppmp_year', $ppmpTransaction->ppmp_year)
                ->get();

        $quantities = $officeRequests->flatMap(function ($offices) use ($ppmpTransaction) {
            return $offices->particulars->map(function ($particular) use ($ppmpTransaction) {
                $isExempted = $this->productService->validateProductExcemption($particular->prod_id, $ppmpTransaction->ppmp_year);
                $firstQty = !$isExempted && $particular->qty_first > 1
                            ? floor($particular->qty_first * 0.70)
                            : $particular->qty_first;
                $secondQty = !$isExempted && $particular->qty_second > 1
                            ? floor($particular->qty_second * 0.70)
                            : $particular->qty_second;
                return [$firstQty, $secondQty];
            });
        });

        $totalQuantity = $quantities->flatten()->sum();

        return $totalQuantity;
    }
}
