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
        $transactions = PpmpTransaction::select('ppmp_type', 'ppmp_year', 'ppmp_code')
        ->where(function($query) {
            $query->where('ppmp_type', 'consolidated');
            })
            ->orWhere(function($query) {
                    $query->where('ppmp_type', 'emergency');
                })
            ->get();

        $resulToPr = $transactions->groupBy('ppmp_type')->map(function ($group) {
            $years = $group->groupBy('ppmp_year')->map(function ($yearGroup) {
                return [
                    'ppmp_year' => $yearGroup->first()->ppmp_year,
                    'ppmpNo' => $yearGroup->pluck('ppmp_code')->all(),
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
        $transaction = $this->getConsolidatedTransactionWithParticulars($request->selectedppmpCode);
        $prOnPpmp = $this->getAllPrTransactionUnderPpmp($transaction, $request->semester);

        $priceAdjustment = (int)$transaction->price_adjustment;
        $qtyAdjustment = (float)$request->qtyAdjust / 100;

        if($request->semester == 'qty_first' && $prOnPpmp->isEmpty()){
            $result = $transaction->consolidated->map(function ($items) use ($transaction, $priceAdjustment, $qtyAdjustment) {
                $prodPrice = (float)$this->productService->getLatestPriceId($items->price_id) * $priceAdjustment;
                $prodPrice = $prodPrice != null ? (float) ceil($prodPrice) : 0;
                
                $qty = $items->qty_first * $qtyAdjustment;
                $firstAmount = $qty * $prodPrice;
    
                return [
                    'pId' => $items->id, 
                    'prodId' => $items->prod_id,
                    'prodCode' => $this->productService->getProductCode($items->prod_id),
                    'prodName' => $this->productService->getProductName($items->prod_id),
                    'prodUnit' => $this->productService->getProductUnit($items->prod_id),
                    'prodPrice' => number_format($prodPrice, 2,'.','.'),
                    'qty' => number_format($qty, 0, '.', ','),
                    'amount' => number_format($firstAmount, 2, '.', ','),
                ];
            });

            $sortResult = $result->sortBy('prodCode');
            return Inertia::render('Pr/MultiForm/StepTwo', [
                'toPr' => $sortResult,
            ]);
        }
    }

    public function stepThree(Request $request)
    {
        dd($request->toArray());
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

    private function getConsolidatedTransactionWithParticulars($request) {
        $result = PpmpTransaction::with('consolidated')->where('ppmp_code', $request)->first();
        return $result;
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

    private function getAllPrTransactionUnderPpmp($ppmp, $semester)
    {
        $ppmpList = PrTransaction::with(['ppmpController' => function ($query) use ($semester) {
            $query->where('semester', $semester);
        }])->get();

        return $ppmpList;
    }
}
