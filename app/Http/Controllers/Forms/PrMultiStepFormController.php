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
        dd($request->toArray());
        $transaction = $this->getConsolidatedTransactionWithParticulars($request->selectedppmpCode);
        $result = $transaction->consolidated->map(function ($items) use (&$totalAmount, $ppmpTransaction) {
            $prodPrice = (float)$this->productService->getLatestPriceId($items->price_id) * $ppmpTransaction->price_adjustment;
            $prodPrice = $prodPrice != null ? (float) ceil($prodPrice) : 0;
            
            $firstAmount = $items->qty_first * $prodPrice;
            $secondAmount = $items->qty_second * $prodPrice;

            $qty = $items->qty_first + $items->qty_second;
            $amount = $firstAmount + $secondAmount;
            $totalAmount += $amount;

            return [
                'pId' => $items->id, 
                'prodId' => $items->prod_id,
                'prodCode' => $this->productService->getProductCode($items->prod_id),
                'prodName' => $this->productService->getProductName($items->prod_id),
                'prodUnit' => $this->productService->getProductUnit($items->prod_id),
                'prodPrice' => number_format($prodPrice, 2,'.','.'),
                'qtyFirst' => number_format($items->qty_first, 0, '.', ','),
                'qtySecond' => number_format($items->qty_second, 0, '.', ','),
                'totalQty' => number_format($qty, 0, '.', ','),
                'amount' => number_format($amount, 2, '.', ','),
            ];
        });
        dd($data->toArray());  
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

    private function getConsolidatedTransactionWithParticulars($request) {
        $result = PpmpTransaction::with('consolidated')->where('ppmp_code', $request)->get();
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
