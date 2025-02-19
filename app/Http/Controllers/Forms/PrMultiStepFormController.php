<?php

namespace App\Http\Controllers\Forms;

use App\Http\Controllers\Controller;
use App\Models\PpmpConsolidated;
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

                if($productQtyExemption && $items->qty_first < 2) {
                    $qty = $items->qty_first;
                } else {
                    $qty = floor($items->qty_first * $qtyAdjustment);
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

            if ($result->isEmpty()) {
                return redirect()->route('pr.form.step1')->with(['error' => 'No available items to procure!']);
            }

            $sortResult = $result->sortBy('prodCode');
            return Inertia::render('Pr/MultiForm/StepTwo', [
                'toPr' => $sortResult,
                'prInfo' => $purchaseRequestInfo,
            ]);
        } elseif ($request->semester == 'qty_first' && $prOnPpmp->isNotEmpty()) {
            $basedParticulars = $transaction->consolidated->map(function ($particular) use ($priceAdjustment) {
                $prodPrice = (float)$this->productService->getLatestPriceId($particular->price_id) * $priceAdjustment;
                $prodPrice = $prodPrice != null ? (float) ceil($prodPrice) : 0;
                return [
                    'pId' => $particular->id,
                    'prod_id' => $particular->prod_id,
                    'qty' => $particular->qty_first,
                    'totalQty' => $particular->qty_first + $particular->qty_second, 
                    'prodPrice' => $prodPrice,
                ];
            });

            $groupParticulars = $prOnPpmp->flatMap(function ($pr) {
                return $pr->prParticulars;
            })->groupBy('prod_id')->map(function ($items) {              
                $qty = (int) $items->sum('qty');

                return [
                    'prod_id' => $items->first()->prod_id,
                    'qty' => $qty,
                ];
            });

            $basedParticulars = $basedParticulars->keyBy('prod_id');

            $result = $basedParticulars->map(function ($group) use ($groupParticulars, $transaction, $qtyAdjustment) {
                $groupQty = $groupParticulars->get($group['prod_id'], ['qty' => 0])['qty'];
                $qty = 0;

                if ($group['qty'] <= 0) {
                    return null;
                } else {
                    $productQtyExemption = $this->productService->validateProductExcemption($group['prod_id'], $transaction->ppmp_year);
                    if($productQtyExemption && $group['qty'] < 2) {
                        $qty = $group['qty'];
                    } else {
                        $qty = floor($group['qty'] * $qtyAdjustment);
                    }
                }

                $remainingQty = $qty - $groupQty;
                $amount = $remainingQty * $group['prodPrice'];

                $productQtyOnPr = $this->getPrUnderPpmpParticular($group['pId']);
                $overAllAvailableQty = $group['totalQty'] - $productQtyOnPr;

                if ($remainingQty <= 0 || $overAllAvailableQty <= 0) {
                    return null;
                }

                return [
                    'pId' => $group['pId'],
                    'prodId' => $group['prod_id'],
                    'prodCode' => $this->productService->getProductCode($group['prod_id']),
                    'prodName' => $this->productService->getProductName($group['prod_id']),
                    'prodUnit' => $this->productService->getProductUnit($group['prod_id']),
                    'prodPrice' => $group['prodPrice'],
                    'qty' => $remainingQty,
                    'amount' => number_format($amount, 2, '.', ','),
                ];
            })->filter()->values();

            if ($result->isEmpty()) {
                return redirect()->route('pr.form.step1')->with(['error' => 'No available items to procure!']);
            }

            $sortResult = $result->sortBy('prodCode');

            return Inertia::render('Pr/MultiForm/StepTwo', [
                'toPr' => $sortResult,
                'prInfo' => $purchaseRequestInfo,
            ]);
        } else {
            $basedParticulars = $transaction->consolidated->map(function ($particular) use ($priceAdjustment) {
                $prodPrice = (float)$this->productService->getLatestPriceId($particular->price_id) * $priceAdjustment;
                $prodPrice = $prodPrice != null ? (float) ceil($prodPrice) : 0;
                return [
                    'pId' => $particular->id,
                    'prod_id' => $particular->prod_id,
                    'qtyFirst' => $particular->qty_first,
                    'qtySecond' => $particular->qty_second,
                    'totalQty' => $particular->qty_first + $particular->qty_second,
                    'prodPrice' => $prodPrice,
                ];
            });

            $result = $basedParticulars->map(function ($group) use  ($transaction, $qtyAdjustment) {
                $qty = 0;
                $productQtyExemption = $this->productService->validateProductExcemption($group['prod_id'], $transaction->ppmp_year);
                $productQtyOnPr = $this->getPrUnderPpmpParticular($group['pId']);

                if ($group['totalQty'] <= 0) {
                    return null;
                } else {                    
                    if($productQtyExemption || $group['totalQty'] < 2) {
                        $qty = $group['totalQty'];
                    } else {
                        $qty = floor($group['qtyFirst'] * $qtyAdjustment) + floor($group['qtySecond'] * $qtyAdjustment);
                    }
                }

                $remainingQty = (int) ($qty - $productQtyOnPr);
                $amount = $remainingQty * $group['prodPrice'];
                
                if ($remainingQty <= 0) {
                    return null;
                }

                return [
                    'pId' => $group['pId'],
                    'prodId' => $group['prod_id'],
                    'prodCode' => $this->productService->getProductCode($group['prod_id']),
                    'prodName' => $this->productService->getProductName($group['prod_id']),
                    'prodUnit' => $this->productService->getProductUnit($group['prod_id']),
                    'prodPrice' => $group['prodPrice'],
                    'qty' => $remainingQty,
                    'amount' => number_format($amount, 2, '.', ','),
                ];
            })->filter()->values();

            if ($result->isEmpty()) {
                return redirect()->route('pr.form.step1')->with(['error' => 'No available items to procure!']);
            }

            $sortResult = $result->sortBy('prodCode');

            return Inertia::render('Pr/MultiForm/StepTwo', [
                'toPr' => $sortResult,
                'prInfo' => $purchaseRequestInfo,
            ]);
        }
    }

    public function submit(Request $request)
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
            return redirect()->route('pr.display.transactions')->with(['message' => 'Successfully executed the request.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return redirect()->back()->with(['error' => 'An error occurred while processing your request. Please contact your system administrator.']);
        }
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

    private function getAllPrTransactionUnderPpmp($ppmp, $semester)
    {
        $listOfPr = $ppmp->load(['purchaseRequests' => function ($query) use ($semester) {
            $query->where('semester', $semester)
                ->where('pr_status', '!=', 'failed')
                    ->with(['prParticulars' => function ($q) {
                        $q->where('status', '!=', 'failed');
                    }]);      
        }]);

        return $listOfPr->purchaseRequests;
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
