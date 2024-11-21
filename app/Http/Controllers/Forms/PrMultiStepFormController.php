<?php

namespace App\Http\Controllers\Forms;

use App\Http\Controllers\Controller;
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

        $purchaseRequestInfo = [
            'semester' => $request->semester,
            'desc' => $request->prDesc,
            'qty_adjustment' => $request->qtyAdjust,
            'transId' => $transaction->id,
            'user' => Auth::id(),
        ];

        if($request->semester == 'qty_first' && $prOnPpmp->isEmpty()){
            $result = $transaction->consolidated->map(function ($items) use ($transaction, $priceAdjustment, $qtyAdjustment) {
                $qty = 0;
                $prodPrice = (float)$this->productService->getLatestPriceId($items->price_id) * $priceAdjustment;
                $prodPrice = $prodPrice != null ? (float) ceil($prodPrice) : 0;

                $productQtyExemption = $this->productService->validateProductExcemption($items->prod_id, $transaction->ppmp_year);

                if($productQtyExemption) {
                    $qty = $items->qty_first;
                } else {
                    $qty = $items->qty_first * $qtyAdjustment;
                }
                
                $firstAmount = $qty * $prodPrice;
    
                return [
                    'pId' => $items->id, 
                    'prodId' => $items->prod_id,
                    'prodCode' => $this->productService->getProductCode($items->prod_id),
                    'prodName' => $this->productService->getProductName($items->prod_id),
                    'prodUnit' => $this->productService->getProductUnit($items->prod_id),
                    'prodPrice' => $prodPrice,
                    'qty' => $qty,
                    'amount' => number_format($firstAmount, 2, '.', ','),
                ];
            });

            $sortResult = $result->sortBy('prodCode');
            return Inertia::render('Pr/MultiForm/StepTwo', [
                'toPr' => $sortResult,
                'prInfo' => $purchaseRequestInfo,
            ]);
        }
    }

    public function stepThree(Request $request)
    {
        DB::beginTransaction();
        try {
            $selectedItems = $request->input('selectedItems');
            $prTransactionInfo = $request->input('prTransactionInfo');

            $createNewPurchaseRequest = $this->createNewPurchaseRequest($prTransactionInfo);

            foreach ($selectedItems as $item) {
                $particular = [
                    "pId" => $item['pId'],
                    "prodId" => $item['prodId'],
                    "prodName" => $item['prodName'],
                    "prodUnit" => $item['prodUnit'],
                    "prodPrice" => (float) $item['prodPrice'],
                    "qty" => (int)$item['qty'],
                    "prId" => $createNewPurchaseRequest->id,
                    "user" => $prTransactionInfo['user'],
                ];
                $this->createNewPurchaseRequestParticulars($particular);
            }

            DB::commit();
            return response()->json(['message' => 'Successfully executed the request.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json(['error' => 'An error occurred while processing your request.'], 500);
        }
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

    private function getConsolidatedTransactionWithParticulars($request) {
        $result = PpmpTransaction::with('consolidated')->where('ppmp_code', $request)->first();
        return $result;
    }

    private function createNewPurchaseRequest($request) {
        $request['qty_adjustment'] = (float)$request['qty_adjustment'] / 100;
        return PrTransaction::create([
            'pr_no' => now()->format('YmdHis'),
            'semester' => $request['semester'],
            'pr_desc' => $request['desc'],
            'qty_adjustment' => $request['qty_adjustment'],
            'trans_id' => $request['transId'],
            'created_by' => $request['user'],
            'updated_by' => $request['user'],
        ]);
    }

    private function createNewPurchaseRequestParticulars($request) {
        return PrParticular::create([
            'prod_id' => $request['prodId'],
            'unitPrice' => $request['prodPrice'],
            'unitMeasure' => $request['prodUnit'],
            'qty' => $request['qty'],
            'revised_specs' => $request['prodName'],
            'pr_id' => $request['prId'],
            'conpar_id' => $request['pId'],
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
