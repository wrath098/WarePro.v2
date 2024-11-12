<?php

namespace App\Http\Controllers\Forms;

use App\Http\Controllers\Controller;
use App\Models\PpmpTransaction;
use App\Models\PrParticular;
use App\Models\PrTransaction;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PrMultiStepFormController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function stepOne(): Response
    {
        $transactions = PpmpTransaction::select('ppmp_type', 'ppmp_year')
        ->where(function($query) {
            $query->where('ppmp_type', 'consolidated')
                ->where('ppmp_status', 'approved');
            })
            ->orWhere(function($query) {
                    $query->where('ppmp_type', 'emergency');
                })
            ->get();

        
        $resulToPr = $transactions->groupBy('ppmp_type')->map(function ($group) {
            $years = $group->groupBy('ppmp_year')->map(function ($yearGroup) {
                return [
                    'ppmp_year' => $yearGroup->first()->ppmp_year,
                ];
            })->values()->all();
        
            return [
                'ppmp_type' => $group->first()->ppmp_type,
                'years' => $years
            ];
        })->values()->all();

        return Inertia::render('Pr/MultiForm/StepOne', [
            'toPr' => $resulToPr,
        ]);
    }

    public function stepTwo(Request $request)
    {
        $availableItems= [];
        $ppmpTransactions = $this->getIndividualWithItems($request->selectedYear);
        $consoTransaction = $this->getConsolidatedTransactionDetails($request);

        $priceAdjustment = $ppmpTransactions->first()->price_adjustment;
        $semester = $request->semester;
        $qtyAdjustment = $request->qtyAdjust / 100;
        $userId = Auth::id();
        $prod_id=1;
        dd($this->getAllProductQuantityFromPurchaseRequestSemesterBased($consoTransaction, $prod_id, $request->semester)->toArray());
        $purchaseRequestInformation = [
            'semester' => $semester,
            'qty_adjustment' => $qtyAdjustment,
            'trans_id' => $consoTransaction->id,
            'user' => $userId,
        ];
 
        $groupParticulars = $ppmpTransactions->flatMap(function ($transaction) {
            return $transaction->particulars;
        })->groupBy('prod_id')->map(function ($items) use ($consoTransaction, $priceAdjustment, $semester, $qtyAdjustment){
            $prodPrice = (float)$this->productService->getLatestPriceId($items->first()->price_id) * $priceAdjustment;
            $prodPrice = $prodPrice != null ? (float) ceil($prodPrice) : 0;

            $modifiedItems = $items->map(function ($particular) use ($semester, $qtyAdjustment) {
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

            if($productQtyForPr > 0 && $productQtyForPr <= $calcAvailability) {
                $availableItems[] = [
                    'prod_id' => $items->first()->prod_id,
                    'unitPrice' => (float)$prodPrice,
                    'unitMeasure' => $this->productService->getProductUnit($items->first()->prod_id),
                    'qty' => (int)$productQtyForPr,
                    'revised_specs' => $this->productService->getProductName($items->first()->prod_id),
                ];
            }
        });

        return Inertia::render('Pr/MultiForm/StepTwo', [
            'data' => $request->all()
        ]);
    }

    public function stepThree(Request $request)
    {
        return Inertia::render('Pr/MultiForm/StepThree', [
            'data' => $request->all()
        ]);
    }

    public function submit(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'address' => 'required|string',
        ]);

        return response()->json(['message', 'Form submitted successfully!']);
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

    private function getAllProductQuantityFromPurchaseRequestSemesterBased($ppmpTransaction, $id, $semester) {
        $ppmpTransaction->load(['purchaseRequests.prParticulars' => function($query) use ($id) {
            $query->where('prod_id', $id)
                ->whereIn('status', ['Pending', 'Partial', 'Completed']);
        }]);

        $quantities = $ppmpTransaction->purchaseRequests->flatMap(function ($purchaseRequest) {
            return $purchaseRequest->prParticulars->pluck('qty');
        })->sum();

        return $ppmpTransaction;
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
