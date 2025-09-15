<?php

namespace App\Http\Controllers\Forms;

use App\Http\Controllers\Controller;
use App\Models\CapitalOutlay;
use App\Models\Fund;
use App\Models\PpmpConsolidated;
use App\Models\PpmpTransaction;
use App\Models\PrParticular;
use App\Models\PrTransaction;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
        #Fetch Transaction Types and cache in 1hr
        $transactions = Cache::remember('ppmp_types_step_one', 3600, function () {
            return PpmpTransaction::select('ppmp_type')
                ->where('ppmp_type', '!=', 'individual')
                ->whereNull('remarks')
                ->groupBy('ppmp_type')
                ->get()
                ->map(fn($q) => [
                    'ppmpType' => ucfirst($q->ppmp_type),
                ]);
        });

        return Inertia::render('Pr/MultiForm/StepOne', [
            'toPr' => $transactions,
        ]);
    }

    public function stepTwo(Request $request)
    {
        #Initialize
        $transactionCode = strtolower($request->input(['selectedppmpCode']));
        $selectedType = $request->input(['selectedType']);
        $selectedYear = $request->input(['selectedYear']);
        $semester = $request->input(['semester']);
        $pr_desc = $request->input(['prDesc']);
        $selectedAccounts = $request->input(['selectedAccounts']);
        $adjustment = $request->input(['qtyAdjust']);

        #Get transaction
        $validateTransaction = PpmpTransaction::where('ppmp_code', $transactionCode)
            ->where('ppmp_type', $selectedType)
            ->where('ppmp_year', $selectedYear)
            ->where('ppmp_status', PpmpTransaction::STATUS_APPROVED)
            ->first();

        #Validate transaction
        if(!$validateTransaction) {
            return back()->with('error', 'Transaction details not matched. Please verify and try again!');
        }

        #Prepare purchase request information
        if (!is_array($selectedAccounts)) {
            return back()->with('error', 'Please select at least one account class to process.');
        }

        $accounts = array_flip($selectedAccounts);
        $purchaseRequestInfo = [
            'semester' => $semester,
            'desc' => $pr_desc,
            'accountId' => $accounts,
            'transId' => $validateTransaction->id,
            'user' => Auth::id(),
        ];

        #Load particulars
        $accountProductIds = $this->getAllProducts_customType($accounts);
        $allowedParticulars = collect($accountProductIds)
            ->flatten(1)
            ->unique()
            ->values();
        
        #Process Emergency Purchase Request
        if($selectedType == 'Emergency') {
            $validateTransaction->load('consolidated');
            $particulars = $this->formatEmergencyParticulars($validateTransaction);

            $sortResult = $particulars->sortBy('prodCode');
            
            return Inertia::render('Pr/MultiForm/StepTwo', [
                'toPr' => $sortResult,
                'prInfo' => $purchaseRequestInfo,
            ]);
        }
        
        #Process Consolidation Purchase Request
        #Initialize
        $officePpmpIds = json_decode($validateTransaction->office_ppmp_ids, true); #Office ppmp ids
        $priceAdjustment = (float)$validateTransaction->price_adjustment; #Price Adjustment

        #Verify if application of final adjustment is true
        if(filter_var($adjustment, FILTER_VALIDATE_BOOLEAN)) {

            #Get all office ppmp transaction
            $ppmpTransactions = PpmpTransaction::whereHas('particulars', function ($query) use ($allowedParticulars) {
                    $query->whereIn('prod_id', $allowedParticulars->all());
                })
                ->with(['particulars' => function ($query) use ($allowedParticulars) {
                    $query->whereIn('prod_id', $allowedParticulars->all());
                }])
                ->findMany($officePpmpIds)
                ->keyBy('id');
           
            #Group transaction's particular by product id
            $groupedParticulars = $ppmpTransactions->flatMap(function ($transaction) {
                return $transaction->particulars->filter(function ($particular) {
                    return !is_null($particular->prod_id);
                });
            })->groupBy('prod_id');

            #Returns a new array
            $consolidatedParticulars = $groupedParticulars->map(function ($items, $prodId) {
                return [
                    'prodId' => $prodId,
                    'priceId' => $items->first()->price_id,
                    'firstSem' => $items->sum('tresh_first_qty'),
                    'secondSem' => $items->sum('tresh_second_qty'),
                ];
            })->values();
        } else {
            #Load Consolidated Particulars
            $validateTransaction->load(['consolidated' => function ($query) use ($allowedParticulars) {
                $query->whereIn('prod_id', $allowedParticulars->all());
            }]);

            #Returns a new array
            $consolidatedParticulars = $validateTransaction->consolidated->map(function($items) {
                return [
                    'prodId' => $items->prod_id,
                    'priceId' => $items->price_id,
                    'firstSem' => $items->qty_first,
                    'secondSem' => $items->qty_second,
                ];
            })->values();
        }

        #Get purchase request from the consolidation
        $prOnPpmp = $this->getAllPrTransactionUnderPpmp($validateTransaction, $semester);

        #Process PRs if 1st Semester and no available purchase request
        if($semester == 'qty_first' && $prOnPpmp->isEmpty()){

            #Format and Filter Particulars
            $result = $consolidatedParticulars->map(function($item) use ($priceAdjustment){
                $latestPrice = (float)($this->productService->getLatestPriceId($item['priceId']) ?? 0);
                $prodPrice = $latestPrice * $priceAdjustment;

                $firstAmount = (int)$item['firstSem'] * $prodPrice;

                #Product information
                $prodoctInfo = $this->productService->getProductInfo($item['prodId']);
    
                return [
                    'prodId' => $item['prodId'],
                    'prodCode' => $prodoctInfo['code'],
                    'prodName' => $prodoctInfo['description'],
                    'prodUnit' => $prodoctInfo['unit'],
                    'prodPrice' => $prodPrice,
                    'qty' => $item['firstSem'],
                    'amount' => number_format($firstAmount, 2, '.', ','),
                ];
            })->values()->sortBy('prodCode');

            #Validate particulars if empty
            if ($result->isEmpty()) {
                return redirect()->route('pr.form.step1')->with(['error' => 'No available items to procure!']);
            }

            return Inertia::render('Pr/MultiForm/StepTwo', [
                'toPr' => $result,
                'prInfo' => $purchaseRequestInfo,
            ]);
        
        #Process PRs if 1st Semester and purchase request exist
        } elseif ($request->semester == 'qty_first' && $prOnPpmp->isNotEmpty()) {

            #Format and Filter Particulars then use product id as array key
            $ppmpParticulars = $consolidatedParticulars->map(function ($item) use ($priceAdjustment) {
                $latestPrice = (float)($this->productService->getLatestPriceId($item['priceId']) ?? 0);
                $prodPrice = $latestPrice * $priceAdjustment;

                #Total Quantity
                $totalAmount = (int)$item['firstSem'] + (int)$item['secondSem'];

                return [
                    'prodId' => $item['prodId'],
                    'qty' => $item['firstSem'],
                    'totalQty' => $totalAmount, 
                    'prodPrice' => $prodPrice,
                ];
            })->keyBy('prodId');

            #Map and group PR particulars
            $prParticulars = $prOnPpmp->flatMap(function ($pr) {
                return $pr->prParticulars;
            })->groupBy('prod_id')->map(function ($items) {              
                $qty = (int) $items->sum('qty');

                return [
                    'prod_id' => $items->first()->prod_id,
                    'qty' => $qty,
                ];
            });

            #Map consolidated particulars
            $result = $ppmpParticulars->map(function ($particular) use ($prParticulars) {

                #Find particular id on pr particular then return the quantity from pr
                $productQtyOnPr = $prParticulars->get($particular['prodId'], ['qty' => 0])['qty'];
                
                #Return null if particular is 0
                if ($particular['qty'] <= 0) {
                    return null;
                }

                #Calculate remaining quantity particular on consolidated
                $remainingQty = $particular['qty'] - $productQtyOnPr;
                $amount = $remainingQty * $particular['prodPrice'];

                if ($remainingQty <= 0) {
                    return null;
                }

                #Product information
                $prodoctInfo = $this->productService->getProductInfo($particular['prodId']);

                return [
                    'prodId' => $particular['prodId'],
                    'prodCode' => $prodoctInfo['code'],
                    'prodName' => $prodoctInfo['description'],
                    'prodUnit' => $prodoctInfo['unit'],
                    'prodPrice' => $particular['prodPrice'],
                    'qty' => $remainingQty,
                    'amount' => number_format($amount, 2, '.', ','),
                ];
            })->filter()->values()->sortBy('prodCode');

            #Validate particulars if empty
            if ($result->isEmpty()) {
                return redirect()->route('pr.form.step1')->with(['error' => 'No available items to procure on 1st Semester!']);
            }

            return Inertia::render('Pr/MultiForm/StepTwo', [
                'toPr' => $result,
                'prInfo' => $purchaseRequestInfo,
            ]);

        #Process PRs if 2nd Semester
        } else {

            #Format and Filter Particulars then use product id as array key
            $ppmpParticulars = $consolidatedParticulars->map(function ($item) use ($priceAdjustment) {
                $latestPrice = (float)($this->productService->getLatestPriceId($item['priceId']) ?? 0);
                $prodPrice = $latestPrice * $priceAdjustment;

                #Total Quantity
                $totalAmount = (int)$item['firstSem'] + (int)$item['secondSem'];

                return [
                    'prodId' => $item['prodId'],
                    'qtyFirst' => $item['firstSem'],
                    'qtySecond' => $item['secondSem'],
                    'totalQty' => $totalAmount,
                    'prodPrice' => $prodPrice,
                ];
            })->keyBy('prodId');

            #Get purchase request from the consolidation
            $prOnPpmp = $this->getAllPrTransactionUnderPpmp($validateTransaction);

            #Map and group PR particulars
            $prParticulars = $prOnPpmp->flatMap(function ($pr) {
                return $pr->prParticulars;
            })->groupBy('prod_id')->map(function ($items) {              
                $qty = (int) $items->sum('qty');

                return [
                    'prod_id' => $items->first()->prod_id,
                    'qty' => $qty,
                ];
            });

            #Map consolidated particulars
            $result = $ppmpParticulars->map(function ($particular) use ($prParticulars) {

                #Find particular id on pr particular then return the quantity from pr
                $productQtyOnPr = $prParticulars->get($particular['prodId'], ['qty' => 0])['qty'];

                #Calculate remaining quantity particular on consolidated
                $remainingQty = (int)$particular['totalQty'] - (int)$productQtyOnPr;
                $amount = $remainingQty * $particular['prodPrice'];
                
                if ($remainingQty <= 0) {
                    return null;
                }

                #Product information
                $prodoctInfo = $this->productService->getProductInfo($particular['prodId']);

                return [
                    'prodId' => $particular['prodId'],
                    'prodCode' => $prodoctInfo['code'],
                    'prodName' => $prodoctInfo['description'],
                    'prodUnit' => $prodoctInfo['unit'],
                    'prodPrice' => $particular['prodPrice'],
                    'qty' => $remainingQty,
                    'amount' => number_format($amount, 2, '.', ','),
                ];
            })->filter()->values()->sortBy('prodCode');

            if ($result->isEmpty()) {
                return redirect()->route('pr.form.step1')->with(['error' => 'No available items to procure!']);
            }

            return Inertia::render('Pr/MultiForm/StepTwo', [
                'toPr' => $result,
                'prInfo' => $purchaseRequestInfo,
            ]);
        }
    }

    public function submit(Request $request)
    {
        #Initialize
        $userId = Auth::id();
        $selectedItems = $request->input('selectedItems');
        $prTransactionInfo = $request->input('prTransactionInfo');

        #Validate transaction id
        $transaction = PpmpTransaction::findOrFail($prTransactionInfo['transId']);
        if(!$transaction) {
            return redirect()->route('pr.form.step1')->with(['error' => 'Transaction ID invalid.!']);
        }

        #Validate selected items if null
        if(!$selectedItems) {
            return back()->with(['error' => 'No items were selected!']);
        }

        #Begin transaction
        DB::beginTransaction();
        
        try {

            #Validate transaction if emergency
            if($transaction->ppmp_type == 'emergency') {
                $emergencyTransactions = $this->getAllTransaction($transaction->ppmp_year, $transaction->ppmp_type);
                $calculatedData = $this->calculateTotalOfEmergencyPr($emergencyTransactions);
                $grandTotalOfPurchases = $calculatedData->sum(function ($item) {
                    return $item['transaction']['total_amount'];
                });

                $emergencyFund = $this->getTotalAmountOfEmergencyFund($transaction->ppmp_year);

                $totalAmountToPurchase = collect($selectedItems)->reduce(function ($carry, $item) {
                    return $carry + ((int) $item['qty'] * (float) $item['prodPrice']);
                }, 0);

                $sumOfGrandTotalAndCurrent = $grandTotalOfPurchases + $totalAmountToPurchase;
                
                $percantage = round(($sumOfGrandTotalAndCurrent / $emergencyFund) * 100, 2);

                if($percantage > 100) {
                    DB::rollBack();
                    return redirect()->back()->with(['error' => 'The Emergency Fund has reached its limit. Please adjust the quantity or remove some items, then try again.']);
                }

                $createNewPurchaseRequest = $this->createNewPurchaseRequest($prTransactionInfo);
                $createPrParticulars = $this->createNewPurchaseRequestParticulars($selectedItems, $createNewPurchaseRequest->id, $userId);
                
                $transaction->update(['remarks' => 'PR']);
                
                DB::commit();
                return redirect()->route('pr.display.transactions')->with(['message' => 'Successfully executed the request. You accumulated ' . $percantage . '% of the total amount of the alloted contingency fund.']);
            }            

            #Execute if transaction is conslidated
            #Store New Purchase Rrequest Transaction
            $newPr_onConsolidated = $this->createNewPurchaseRequest($prTransactionInfo);

            #Validate if success
            if($newPr_onConsolidated) {
                $flipAccountId = array_flip($prTransactionInfo['accountId']);
                $this->createNewPurchaseRequestParticulars($selectedItems, $newPr_onConsolidated->id, $userId);
                $newPr_onConsolidated->update(['pr_remarks' => json_encode($flipAccountId, true)]);
            }

            #Commit Transaction
            DB::commit();
            return redirect()->route('pr.display.transactions')->with(['message' => 'Successfully executed the request.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return redirect()->back()->with(['error' => 'An error occurred while processing your request. Please contact your system administrator.']);
        }
    }

    public function filterToPurchase(Request $request)
    {
        #Initialize
        $type = strtolower($request->input('type'));
        $year = $request->input('year');

        #Get transactions
        $transactions = PpmpTransaction::select('ppmp_type', 'ppmp_year', 'ppmp_code', 'description', 'ppmp_status', 'account_class_ids', 'final_qty_adjustment')
            ->when($type, function ($query) use ($type) {
                return $query->where('ppmp_type', $type);
            })
            ->when($year, function ($query) use ($year) {
                return $query->where('ppmp_year', $year);
            })
            ->where('ppmp_status', PpmpTransaction::STATUS_APPROVED)
            ->whereNull('remarks')
            ->get()
            ->map(function($transaction) {
                #Get account class name
                $accountClassIds = json_decode($transaction->account_class_ids, true);
                $accountClass = Fund::findMany($accountClassIds)->pluck('fund_name', 'id');

                #Get maximum adjustment
                $adjustment = json_decode($transaction->final_qty_adjustment, true);
                $convert = array_map('floatval', $adjustment);
                $maxValue = $convert ? max($convert) : 100;

                return [
                    'ppmp_type' => $transaction->ppmp_type,
                    'ppmp_code' => $transaction->ppmp_code,
                    'account_class' => $accountClass,
                    'adjustment' => $maxValue,
                ];
            });
        
        #Return if transaction is empty
        if ($transactions->isEmpty()) {
            return response()->json([
                'message' => 'No available/approved transaction found!'
            ]);
        }
        
        return response()->json($transactions);
    }

    private function createNewPurchaseRequest($request) 
    {
        return PrTransaction::create([
            'pr_no' => now()->format('YmdHis'),
            'semester' => $request['semester'] ?? null,
            'pr_desc' => $request['desc'] ?? 'emergency',
            'trans_id' => $request['transId'] ?? null,
            'created_by' => $request['user'] ?? null,
            'updated_by' => $request['user'] ?? null,
        ]);
    }

    private function createNewPurchaseRequestParticulars($selectedItems, $prId, $userId)
    {
        $particulars = [];

        foreach ($selectedItems as $item) {
            $particulars[] = [
                "prod_id" => $item['prodId'],
                "revised_specs" => $item['prodName'],
                "unitMeasure" => $item['prodUnit'],
                "unitPrice" => (float) $item['prodPrice'],
                "qty" => (int)$item['qty'],
                "pr_id" => $prId,
                "updated_by" => $userId,
            ];
        }

        PrParticular::insert($particulars);
    }

    private function getAllPrTransactionUnderPpmp($ppmp, $semester = null)
    {
        $listOfPr = $ppmp->load(['purchaseRequests' => function ($query) use ($semester) {
            $query->when($semester, function ($q) use ($semester) {
                $q->where('semester', $semester);
            })
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

    private function formatEmergencyParticulars($particulars) 
    {
        return $particulars->consolidated->map(function ($items) {
            $prodPrice = (float)$this->productService->getLatestPriceId($items->price_id);
            $prodPrice = $prodPrice != null ? (float) ceil($prodPrice) : 0;
            
            $firstAmount = $items->qty_first * $prodPrice;

            return [
                'pId' => $items->id, 
                'prodId' => $items->prod_id,
                'prodCode' => $this->productService->getProductCode($items->prod_id),
                'prodName' => $this->productService->getProductName($items->prod_id),
                'prodUnit' => $this->productService->getProductUnit($items->prod_id),
                'prodPrice' => $prodPrice,
                'qty' => $items->qty_first,
                'amount' => number_format($firstAmount, 2, '.', ','),
            ];
        });
    }

    private function getAllTransaction($year, $type)
    {
        return PpmpTransaction::with([
            'purchaseRequests' => function ($query) {
                $query->select(['id', 'trans_id'])
                    ->with(['prParticulars' => function ($query) {
                        $query->select(['id', 'pr_id', 'prod_id', 'qty', 'unitPrice']);
                    }]);
            }
        ])
        ->where('ppmp_year', $year)
        ->where('ppmp_type', $type)
        ->get(['id', 'ppmp_year', 'ppmp_type', 'ppmp_code', 'office_id']);
    }

    private function calculateTotalOfEmergencyPr($transaction)
    {
        return $transaction->map(function ($transaction) {
            $purchaseRequests = $transaction->purchaseRequests->map(function ($pr) {
                $particulars = $pr->prParticulars->map(function ($particular) {
                    $total = $particular->qty * $particular->unit_price;
                    
                    return [
                        'product_id' => $particular->prod_id,
                        'quantity' => $particular->qty,
                        'price' => $particular->unit_price,
                        'total' => $total,
                        'formatted_total' => number_format($total, 2)
                    ];
                });
                
                $prTotal = $particulars->sum('total');
                
                return [
                    'pr_id' => $pr->id,
                    'status' => $pr->status,
                    'particulars' => $particulars,
                    'pr_total' => $prTotal,
                    'formatted_pr_total' => number_format($prTotal, 2)
                ];
            });
            
            $transactionTotal = $purchaseRequests->sum('pr_total');
            
            return [
                'transaction' => [
                    'id' => $transaction->id,
                    'ppmp_code' => $transaction->ppmp_code,
                    'total_amount' => $transactionTotal,
                    'formatted_total' => number_format($transactionTotal, 2)
                ],
                'purchase_requests' => $purchaseRequests
            ];
        });
    }
    
    private function getTotalAmountOfEmergencyFund($year)
    {
        return CapitalOutlay::with(['allocations' => function($query) {
            $query->where('description', 'Contingency')
                ->select(['id', 'cap_id', 'description', 'amount']);;
        }])
        ->select(['id', 'year', 'amount'])
        ->where('year', $year)
        ->get()
        ->pipe(function($collection) {
            return $collection->sum(function($item) {
                return $item->allocations->sum('amount');
            });
        });
    }
}
