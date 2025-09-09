<?php

namespace App\Http\Controllers;

use App\Models\CapitalOutlay;
use App\Models\Fund;
use App\Models\FundAllocation;
use App\Models\Office;
use App\Models\PpmpConsolidated;
use App\Models\PpmpParticular;
use App\Models\PpmpTransaction;
use App\Models\Product;
use App\Services\ProductService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Rap2hpoutre\FastExcel\FastExcel;

class PpmpTransactionController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(): Response
    {          
        $officePpmpExist = PpmpTransaction::whereIn('ppmp_type', ['individual', 'emergency'])
            ->with(['requestee', 'creator'])
            ->orderBy('created_at', 'desc')
            ->paginate(50)
            ->map(function ($ppmp) {
                return [
                    'id' => $ppmp->id,
                    'ppmpCode' => $ppmp->ppmp_code,
                    'ppmpType' => $ppmp->ppmp_type === 'individual' ? 'Office' : ucfirst($ppmp->ppmp_type),
                    'basedPrice' => ((float) $ppmp->price_adjustment * 100) . '%',
                    'officeId' => $ppmp->office_id,
                    'officeCode' => $ppmp->requestee->office_code ?? '',
                    'officeName' => $ppmp->requestee->office_name ?? '',
                    'creator' => $ppmp->creator->name ?? '',
                    'createdAt' => [
                        'display' => optional($ppmp->created_at)->format('M d, Y'),
                        'raw' => optional($ppmp->created_at)->toDateString(),
                    ],
                ];
            });
        
        $office = Office::where('office_status', 'active')
            ->orderBy('office_name')
            ->get()
            ->map(function ($office) {
                return [
                    'id' => $office->id,
                    'name' => $office->office_name,
                ];
            });

        return Inertia::render('Ppmp/Import', [
            'officePpmps' =>  $officePpmpExist, 
            'offices' => $office,
            'user' => Auth::id(),
        ]);
    }

    public function store(Request $request)#
    {
        DB::beginTransaction();

        $validatedData = $request->validate([
            'ppmpType' => 'required|string',
            'ppmpYear' => 'required|integer',
            'office' => 'required|integer',
            'ppmpSem' => 'nullable|string',
            'user' => 'required|integer',
            'file' => 'nullable|file|mimes:xls,xlsx',
        ]);

        try {
            if($validatedData['ppmpType'] == 'individual') {
                if ($this->validateIndivPpmp($validatedData)) {
                    DB::rollBack();
                    return back()->withInput()->withErrors([
                        'office' => 'Office PPMP already exists!'
                    ]);
                }

                $createPpmp = $this->createPpmpTransaction($validatedData);
                if ($request->hasFile('file')) {
                    $filePath = $this->handleFileUpload($request->file('file'));
                    $fullPath = storage_path('app/' . $filePath);
                    
                    $startRow = 0;
                    $currentRow = 0;

                    (new FastExcel)->import($fullPath, function ($line) use ($createPpmp, &$currentRow, $startRow) {
                        $currentRow++;

                        if ($currentRow < $startRow) {
                            return null;
                        }

                        return $this->processImportedLine($line, $createPpmp->id);
                    });
    
                    Storage::delete($filePath);
                }

                DB::commit();
                return redirect()->route('import.ppmp.index')
                    ->with('message', 'Successfully create PPMP! You can now check the list to add products.');
            } elseif ($validatedData['ppmpType'] == 'contingency') {
                
                $is_AppExist = $this->fetchApprovedConsolidatedPpmp($validatedData['ppmpYear'], 'consolidated');

                if (!$is_AppExist) {
                    DB::rollback();
                    return back()->with('error', 'No consolidated PPMP is available. Please check the year entered and try again.');
                }
                
                $ppmpData = array_merge($validatedData, ['ppmpType' => 'emergency']);
                $ppmp = $this->createPpmpTransaction($ppmpData);
                $ppmp->update(['ppmp_status' => 'approved']);

                DB::commit();

                return redirect()
                    ->route('import.ppmp.index')
                    ->with('message', 'Successfully created! You can now check the list to add products.');
            } else {

                DB::rollback();
                return back()->with('error', '404 - Not Found!');
            }
        } catch (\Exception $e) {

            DB::rollBack();
            Log::error("Creation of PPMP Transaction Failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'data' => $validatedData
            ]);
            return back()->with('error', 'Creation of PPMP Transaction. Please try again!');
        }
    }

    public function storeConsolidated(Request $request)#
    {
        DB::beginTransaction();

        try {
            $countUnavailableProduct = 0;

            #Validate if more than 1 Office PPMP is existed
            $queryTransaction = $this->getQueryTransaction($request);
            if (!$queryTransaction) {
                DB::rollBack();
                return back()->with(['error' => 'Request is incomplete. Please check the input fields and try again.']);
            }

            #Format Consolidation Data to create
            $data = $this->prepareConsolidationData($request);
            
            #Create Transaction
            $createConsolidation = $this->createPpmpTransaction($data);

            #Get Office PPMP based on the request
            $individualPpmp = $this->getIndividualPpmpTransactionsWithParticulars($data);

            #Store Office PPMP ids to $data
            $data['officePpmpIds'] = json_encode($individualPpmp->pluck('id'));

            #Process consolidate office ppmp particulars
            $this->processOriginalVersion($individualPpmp, $createConsolidation->id, $data, $countUnavailableProduct);

            #Update transaction details/information
            $createConsolidation->update([
                'account_class_ids' => $data['accountClass'],
                'office_ppmp_ids' => $data['officePpmpIds'],
            ]);

            DB::commit();
            return redirect()->route('conso.ppmp.type', ['type' => 'consolidated', 'status' => 'draft'])
                    ->with('message', 'Consolidated has been generated successfully!' . ($countUnavailableProduct > 0 ? ' ' . $countUnavailableProduct . ' products were not consolidated due to unavailability on the current product list.' : ''));
        } catch (\Exception $e) {

            DB::rollBack();
            Log::error("Consolidating PPMP Failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'data' => $request
            ]);

            return back()->with(['error' => 'Consolidating PPMP Failed. Please try again!']);
        }
    }

    public function storeCopy(Request $request)#
    { 
        DB::beginTransaction();
        
        try {
            #Initialize User ID
            $userId = Auth::id();

            #Get Transaction
            $transaction = PpmpTransaction::with('consolidated')->findOrFail($request->ppmpId);

            #Format data for new transaction
            $transactionDetails = [
                'ppmpType' => $transaction->ppmp_type,
                'ppmpYear' => $transaction->ppmp_year,
                'office' => null,
                'user' => $userId,
            ];

            #Create new transaction
            $newTransaction = $this->createPpmpTransaction($transactionDetails);
            
            #Create new transaction's particular in bulk
            $consolidatedData = $transaction->consolidated->map(function ($particular) use ($newTransaction, $userId) {
                return [
                    'qty_first'   => $particular->qty_first,
                    'qty_second'  => $particular->qty_second,
                    'prod_id'    => $particular->prod_id,
                    'price_id'    => $particular->price_id,
                    'trans_id'    => $newTransaction->id,
                    'created_by'  => $userId,
                    'updated_by'  => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();
            PpmpConsolidated::insert($consolidatedData);
            
            $newTransaction->update([
                'description' => $request->ppmpDesc,
                'account_class_ids' => $transaction->account_class_ids,
                'office_ppmp_ids' => $transaction->office_ppmp_ids,
                'init_qty_adjustment' => $transaction->init_qty_adjustment,
                'remarks' => 'Duplicate copy of Transaction No. ' . $transaction->ppmp_code,
            ]);

            DB::commit();
            return redirect()->route('conso.ppmp.type', ['type' => 'consolidated' , 'status' => 'draft'])
                ->with(['message' => 'A copy of Consolidated PPMP was successful created!']);
        } catch (\Exception $e) {

            DB::rollBack();
            Log::error("PPMP copy failed - User: " . Auth::user()->name, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Failed to copy PPMP. Please try again.');
        }
    }

    public function storeAsFinal(Request $request, PpmpTransaction $ppmpTransaction)
    {
        $userId = $request->user;
        $recapitulation = [];
        
        #Validate Consolidation if approved transaction is already exist
        $isApprovedExist = $this->validateApprovedConsolidation($ppmpTransaction);
        if($isApprovedExist) {
            return back()->with('error', 'Approved Consolidated Transaction is already exist. Please verify and try again.');
        }

        #Office PPMP Transaction IDs
        $officePpmpIds = json_decode($ppmpTransaction->office_ppmp_ids, true);
        if(!$officePpmpIds) {
            return back()->with('error', 'No Office PPMP Transaction found. Please verify and try again.');
        }

        #Validate Adjustment if exist
        if(!$ppmpTransaction->init_qty_adjustment && !$ppmpTransaction->final_qty_adjustment) {
            return back()->with('error', 'No adjustment found. Please verify and try again.');
        }

        DB::beginTransaction();

        try {
            #Sort and Format Consolidated Particulars
            $sortedParticulars = $this->formattedAndSortedParticulars($ppmpTransaction);

            #Get all product
            $funds = $this->productService->getAllProduct_FundModel();

            #Validate proposed budget if exist
            $accountClassIds = json_decode($ppmpTransaction->account_class_ids, true);
            $existingRecords = CapitalOutlay::whereIn('fund_id', $accountClassIds)
                ->where('year', $ppmpTransaction->ppmp_year)
                ->pluck('fund_id')
                ->toArray();

            #Disburse proposed budget to account class and categories    
            if($existingRecords) {
                $this->recapitulation($sortedParticulars, $funds, $recapitulation, $ppmpTransaction->ppmp_year);
                $this->processFundAllocations($recapitulation, $ppmpTransaction->ppmp_year);
            }

            #Adjustment Json Decode
            $initAdjustment = json_decode($ppmpTransaction->init_qty_adjustment, true);
            $finalAdjustment = json_decode($ppmpTransaction->final_qty_adjustment, true);

            $this->updateOfficePpmpAdjustmentAndThreshold($officePpmpIds, $accountClassIds, $initAdjustment, $finalAdjustment);

            PpmpTransaction::whereIn('id', $officePpmpIds)->update([
                'init_qty_adjustment'   => $ppmpTransaction->init_qty_adjustment,
                'final_qty_adjustment' => $ppmpTransaction->final_qty_adjustment,
                'ppmp_status' => 'approved',
                'updated_by' => $userId,
            ]);

            $ppmpTransaction->update(['ppmp_status' => 'approved', 'updated_by' => $userId]);
            DB::commit();
            return redirect()->route('conso.ppmp.type', ['type' => 'consolidated', 'status' => 'approved'])->with('message', 'Proceeding to Approved PPMP successfully executed');

        } catch (\Exception $e) {

            DB::rollBack();
            Log::error("Finalization of APP/ Consolidated PPMP Failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'data' => $ppmpTransaction->toArray()
            ]);
            return redirect()->back()->with(['error' => 'Finalization of Consolidated PPMP Failed. Please try again!']);
        }
    }

    public function showIndividualPpmp(PpmpTransaction $ppmpTransaction)
    {
        $ppmpTransaction->load('particulars', 'consolidated', 'requestee');

        $availableProducts = $ppmpTransaction->ppmp_type === 'individual'
            ? $this->getActiveProductList()
            : $this->getActiveProductList();

        $ppmpParticulars = $ppmpTransaction->ppmp_type === 'individual'
            ? $this->formatParticulars($ppmpTransaction)
            : $this->formatConsolidated($ppmpTransaction);

        $totalItems = $ppmpParticulars->count();

        $grandTotal = $ppmpParticulars->sum(function ($item) {
            return ((int) $item['firstQty'] + (int) $item['secondQty']) * (float) $item['prodPrice'];
        });

        $formattedOverallPrice = number_format($grandTotal, 2, '.', ',');
        $createdAt = $ppmpTransaction->created_at->format('M d, Y');

        return Inertia::render('Ppmp/Individual', [
            'ppmp' =>  $ppmpTransaction,
            'ppmpParticulars' => $ppmpParticulars,
            'totalItems' => $totalItems,
            'formattedOverallPrice' => $formattedOverallPrice,
            'products' => $availableProducts,
            'createdAt' => $createdAt,
            'user' => Auth::id()
        ]);
    }

    public function showConsolidatedPpmp(PpmpTransaction $ppmpTransaction)
    {
        $totalAmount = 0;

        #Get transaction particulars
        $ppmpTransaction->load('updater', 'consolidated');
        $ppmpTransaction->ppmp_type = ucfirst($ppmpTransaction->ppmp_type);

        #Format transaction account class and count office ppmp
        $accountClassIds = json_decode($ppmpTransaction->account_class_ids ?? '[]');
        $funds = Fund::whereIn('id', $accountClassIds)->get()->pluck('fund_name');
        $ppmpTransaction->account_class_ids = $funds->isNotEmpty() ? $funds->implode(', ') : 'N/a';

        $officePpmpIds = json_decode($ppmpTransaction->office_ppmp_ids, true);
        $ppmpTransaction->office_ppmp_ids = is_array($officePpmpIds) ? count($officePpmpIds) : 0;
        
        #Count trashed particulars
        $countTrashedItems = $ppmpTransaction->consolidated()->onlyTrashed()->count();

        #Get active account class
        $accountClass = $this->productService->getActiveFunds();

        #Format transaction particulars
        $groupParticulars = $ppmpTransaction->consolidated->map(function ($items) use (&$totalAmount, $ppmpTransaction) {
            $prodPrice = (float)$this->productService->getLatestPriceId($items->price_id) * $ppmpTransaction->price_adjustment;
            
            #RETURN IT BACK IF PRICE SHOULD BE ROUND UP ALL FLOAT PRICES
            #$prodPrice = $prodPrice != null ? (float) ceil($prodPrice) : 0;
            
            $firstAmount = $items->qty_first * $prodPrice;
            $secondAmount = $items->qty_second * $prodPrice;

            $qty = $items->qty_first + $items->qty_second;
            $amount = $firstAmount + $secondAmount;
            $totalAmount += $amount;
            $prodDesc = $this->productService->getProductName($items->prod_id);
            $limitedDescription = Str::limit($prodDesc, 90, '...');

            return [
                'pId' => $items->id, 
                'prodId' => $items->prod_id,
                'prodCode' => $this->productService->getProductCode($items->prod_id),
                'prodName' => $limitedDescription,
                'prodUnit' => $this->productService->getProductUnit($items->prod_id),
                'prodPrice' => number_format($prodPrice, 2,'.','.'),
                'qtyFirst' => number_format($items->qty_first, 0, '.', ','),
                'qtySecond' => number_format($items->qty_second, 0, '.', ','),
                'totalQty' => number_format($qty, 0, '.', ','),
                'amount' => number_format($amount, 2, '.', ','),
            ];
        });

        $sortedParticulars = $groupParticulars->sortBy('prodCode');
        $ppmpTransaction->init_qty_adjustment = $ppmpTransaction->init_qty_adjustment;
        $ppmpTransaction->formatted_created = $ppmpTransaction->created_at->format('M d, Y');
        $ppmpTransaction->transactions = $sortedParticulars->values();
        $ppmpTransaction->totalItems = $sortedParticulars->count();
        $ppmpTransaction->totalAmount = number_format($totalAmount, 2, '.', ',');

        return Inertia::render('Ppmp/Consolidated', [
            'ppmp' =>  $ppmpTransaction,
            'countTrashed' => $countTrashedItems,
            'accountClass' => $accountClass,
            'user' => Auth::id(),
        ]);
    }

    public function showIndividualPpmp_Type()
    {       
        $ppmpTransactions = PpmpTransaction::with('requestee', 'updater')
            ->whereIn('ppmp_type', ['individual', 'emergency'])
            ->orderBy('ppmp_code', 'desc')
            ->get()
            ->map(fn($q) => [
                'id' => $q->id,
                'ppmpCode' => $q->ppmp_code,
                'ppmpYear' => $q->ppmp_year,
                'ppmpStatus' => ucfirst($q->ppmp_status),
                'ppmpType' => $q->ppmp_type,
                'officeName' => $q->requestee->office_name,
                'officeCode' => $q->requestee->office_code,
                'dateCreated' => $q->created_at->format('M d, Y'),
                'updatedBy' => $q->updater->name,
                'initAdjustment' => $q->init_qty_adjustment ?? null,
                'finalAdjustment' => $q->final_qty_adjustment ?? null,
                'priceAdjustment' => $q->price_adjustment,
            ]);

        return Inertia::render('Ppmp/PpmpList', ['ppmpTransaction' =>  $ppmpTransactions]);
    }

    public function showConsolidatedPpmp_Type(Request $request): Response
    {
        #Get Transactions
        $individualList = PpmpTransaction::select('ppmp_type', 'ppmp_year')
            ->where('ppmp_type', 'individual')
            ->where('ppmp_status', 'draft')
            ->get();

        #Format Transaction
        $result = $individualList->groupBy('ppmp_type')->map(function ($group) {
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
        
        #Get Account Classess
        $accountClass = $this->productService->getActiveFunds()->pluck('fund_name', 'id');
        
        #Get latest 20 Consolidion Transaction
        $transactions = PpmpTransaction::with('updater')
            ->where('ppmp_type', $request->type)
            ->where('ppmp_status', $request->status)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        #Format Transaction
        $transactions = $transactions->map(function ($transaction) {
            $accountClassIds = json_decode($transaction->account_class_ids ?? '[]');
            $funds = Fund::whereIn('id', $accountClassIds)->get()->pluck('fund_name');
            $funds = $funds->isNotEmpty() ? $funds->implode(', ') : 'N/a';
            $priceAdjustment = $transaction->price_adjustment ? ((float)$transaction->price_adjustment * 100) : 0;
            $details = '<b>Account Classifications: </b>'. $funds .'<br>'
                    . '<b>@</b>'. $priceAdjustment . '% Price Adjustment' . '<br>'
                    . ($transaction->remarks ? '<b>@</b>'. $transaction->remarks : '');

            return [
                'id' => $transaction->id,
                'code' => $transaction->ppmp_code,
                'ppmpYear' => $transaction->ppmp_year,
                'description' => $transaction->description,
                'details' => $details,
                'createdAt' => $transaction->created_at->format('F d, Y'),
                'updatedBy' => optional($transaction->updater)->name ?? 'Unknown',
            ];
        });

        $request['status'] = ucfirst($request['status']);
        $request['type'] = ucfirst($request['type']);
        return Inertia::render('Ppmp/DraftConsolidatedList', [
            'ppmp' => $request, 
            'transactions' => $transactions, 
            'individualList' => $result,
            'accountClass' => $accountClass,
        ]);
    }

    public function showOfficeListWithNoPpmp(Request $request)
    {
        $ppmpType = $request->ppmpType;
        $ppmpYear = $request->ppmpYear;

        if (!in_array($ppmpType, ['individual', 'contingency'])) {
            return response()->json(['data' => []]);
        }

        $officeList = $ppmpType === 'individual' ? $this->getOfficesWithNoPpmp($ppmpType, $ppmpYear) : $this->fetchOfficeList();

        $filterFn = $ppmpType === 'individual' 
            ? fn($office) => !Str::contains(Str::lower($office['name']), 'secondary')
            : fn($office) => Str::contains(Str::lower($office['name']), 'secondary');

        $filteredOffices = $officeList->filter($filterFn)->values();

        return response()->json(['data' => $filteredOffices]);
    }

    public function updateConsolidatedDescription(Request $request)
    {
        DB::beginTransaction();
        
        $request->validate([
            'ppmpId' => 'required|integer|exists:ppmp_transactions,id',
            'ppmpDesc' => 'required|string|max:255',
        ]);

        try {
            $ppmpTransaction = PpmpTransaction::findOrFail($request->ppmpId);

            $ppmpTransaction->description = $request->ppmpDesc;
            $ppmpTransaction->updated_by = Auth::id();
            $ppmpTransaction->save();

            DB::commit();
            return redirect()->back()
                        ->with('success', 'Successfully updated the PPMP.');
        } catch(\Exception $e) {

            DB::rollBack();
            Log::error("Creation of New Product Failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'data' => $request->toArray()
            ]);
            return back()->with('error', 'Updating of PPMP Failed. Please try again!');
        }
    }

    public function updateInitialAdjustment(Request $request)
    {
        #Initialize
        $consoPpmpId = $request->input('ppmpId');
        $adjustmentType = $request->input('adjustmentType');
        $groupData = $request->input('initAdjustment'); #For Group
        $customData = $request->input('customInitAdjustment') #For Custom
            ? array_filter($request->input('customInitAdjustment'), function($value) {
                return !is_null($value);
            })
            : null;

        #Begin DB Transaction
        DB::beginTransaction();

        try {

            #Validate Transaction 
            $consoTransactionInfo = PpmpTransaction::find($consoPpmpId);
            if(!$consoTransactionInfo) {
                DB::rollBack();
                return back()->with('error', 'Consolidated PPMP Id not found. Please select valid transaction and try again.');
            }

            #Validate Consolidation if approved transaction is already exist
            $isApprovedExist = $this->validateApprovedConsolidation($consoTransactionInfo);
            if($isApprovedExist) {
                DB::rollBack();
                return back()->with('error', 'Updating Failed. Approved Consolidated Transaction is already exist!');
            }

            #Office PPMP Transaction IDs
            $officePpmpIds = json_decode($consoTransactionInfo->office_ppmp_ids);
            if(!$officePpmpIds) {
                DB::rollBack();
                return back()->with('error', 'No Office PPMP Transaction found. Please verify and try again.');
            }

            #Perform adjustment
            if($adjustmentType == 'grouped') {
                $this->all_initOfficePpmpAdjustment($officePpmpIds, $groupData, $consoTransactionInfo->id);

                $consoTransactionInfo->update([
                    'init_qty_adjustment' => json_encode(['all' => $groupData]),
                    'updated_by' => Auth::id()
                ]);
            } else {
                $allProductsId = $this->getAllProducts_customType($customData);
                $this->custom_initOfficePpmpAdjustment($allProductsId, $officePpmpIds, $customData, $consoTransactionInfo->id);

                $consoTransactionInfo->update([
                    'init_qty_adjustment' => json_encode($customData),
                    'updated_by' => Auth::id()
                ]);
            }

            DB::commit();
            return redirect()->back()->with('message', 'Initial Quantity Adjustment successfully updated.');
        }catch (\Exception $e) {
            DB::rollBack();
            Log::error(['Update Consolidated Initial Adjustment' => $e->getMessage()]);
        }
    }

    private function getAllProducts_customType($customAdjustment)
    {
        $allProducts = collect();
        foreach($customAdjustment as $accountId => $value) {
                $fund = Fund::with([
                'categories' => function ($q) {
                    $q->where('cat_status', 'active')->select('id', 'fund_id');
                },
                'categories.items' => function ($q) {
                    $q->where('item_status', 'active')->select('id', 'cat_id', );
                },
                'categories.items.products' => function ($q) {
                    $q->where('prod_status', 'active')->select('id', 'item_id');
                }
            ])
            ->where('id', $accountId)
            ->where('fund_status', 'active')
            ->first(['id', 'fund_name']);

            $productIds = collect();
            if ($fund) {
                foreach ($fund->categories as $category) {
                    foreach ($category->items as $item) {
                        foreach ($item->products as $product) {
                            $productIds->push($product->id);
                        }
                    }
                }
                $allProducts->put($accountId, $productIds->unique()->values());
            }
        }

        return $allProducts;
    }

    private function custom_initOfficePpmpAdjustment($products, $officePpmps, $customData, $updatingTransactionId)
    {
        #Get all office ppmp transaction
        $ppmpTransactions = PpmpTransaction::with('particulars')->findMany($officePpmps)->keyBy('id');

        #Account Class Loop
        foreach ($products as $index => $productIds) {
            $customValue = $customData[$index] ?? 100;
            $adjustment = (float)$customValue / 100;

            #Products under an account class
            foreach ($productIds as $prodId) {
                $consoFirstQty = 0;
                $consoSecondQty = 0;
                
                #Grouped transaction's particular
                $matchedParticulars = $ppmpTransactions->flatMap(function ($transaction) use ($prodId) {
                    return $transaction->particulars->filter(function ($particular) use ($prodId) {
                        return $particular->prod_id == $prodId;
                    });
                });

                #Update each transaction particulars 
                if($matchedParticulars) {
                    $isProductExempted = $this->productService->validateProductExcemption($prodId);
                    $matchedParticulars->map(function ($items) use ($adjustment, $isProductExempted, &$consoFirstQty, &$consoSecondQty){
                        $adjustedFirstQty = $this->calculateAdjustedQty($items->qty_first, $adjustment, $isProductExempted);
                        $adjustedSecondQty = $this->calculateAdjustedQty($items->qty_second, $adjustment, $isProductExempted);
                        
                        $consoFirstQty += $adjustedFirstQty;
                        $consoSecondQty += $adjustedSecondQty;
                        
                        $items->update([
                            'adjusted_firstQty' => $adjustedFirstQty,
                            'adjusted_secondQty' => $adjustedSecondQty,
                        ]);
                    });
                }
                
                #Validate and update consolidated particular
                $consoParticularInfo = PpmpConsolidated::where('trans_id', $updatingTransactionId)->where('prod_id', $prodId)->first();
                if($consoParticularInfo) {
                    $consoParticularInfo->update([
                        'qty_first' => $consoFirstQty,
                        'qty_second' => $consoSecondQty,
                        'updated_by' => Auth::id(),
                    ]);
                }
            }
        }
    }

    private function all_initOfficePpmpAdjustment($officePpmps, $adjustmentPercentage, $updatingTransactionId)
    {
        #Get all office ppmp transaction
        $ppmpTransactions = PpmpTransaction::with('particulars')->findMany($officePpmps)->keyBy('id');
        $adjustment = (float)$adjustmentPercentage / 100;

        #Updated all initials quantity adjustment
        foreach ($ppmpTransactions as $transaction) {
            $transaction->init_qty_adjustment = ['all' => (int)$adjustmentPercentage];
            $transaction->save();
        }

        #Group transactions by product Id
        $groupedParticulars = $ppmpTransactions->flatMap(function ($transaction) {
            return $transaction->particulars->filter(function ($particular) {
                return !is_null($particular->prod_id);
            });
        })->groupBy('prod_id');

        $groupedParticulars->map(function($transactions) use ($adjustment, $updatingTransactionId) {
            #Validate excemption
            $isProductExempted = $this->productService->validateProductExcemption($transactions->first()->prod_id);
            $consoFirstQty = 0;
            $consoSecondQty = 0;

            #Update each transaction particulars 
            foreach ($transactions as $transaction) {
                $adjustedFirstQty = $this->calculateAdjustedQty($transaction->qty_first, $adjustment, $isProductExempted);
                $adjustedSecondQty = $this->calculateAdjustedQty($transaction->qty_second, $adjustment, $isProductExempted);

                $consoFirstQty += $adjustedFirstQty;
                $consoSecondQty += $adjustedSecondQty;

                $transaction->update([
                    'adjusted_firstQty' => $adjustedFirstQty,
                    'adjusted_secondQty' => $adjustedSecondQty,
                ]);
            }

            #Validate and update consolidated particular
            $consoParticularInfo = PpmpConsolidated::where('trans_id', $updatingTransactionId)->where('prod_id', $transactions->first()->prod_id)->first();
            if($consoParticularInfo) {
                $consoParticularInfo->update([
                    'qty_first' => $consoFirstQty,
                    'qty_second' => $consoSecondQty,
                    'updated_by' => Auth::id(),
                ]);
            }
        });
    }

    public function updateFinalAdjustment(Request $request)
    {
        #Initialize
        $consoPpmpId = $request->input('ppmpId');
        $adjustmentType = $request->input('adjustmentType');
        $groupData = $request->input('initAdjustment'); #For Group
        $customData = $request->input('customInitAdjustment') #For Custom
            ? array_filter($request->input('customInitAdjustment'), function($value) {
                return !is_null($value);
            })
            : null;
        
        #Begin DB Transaction
        DB::beginTransaction();
        
        try {
            #Validate Transaction 
            $consoTransactionInfo = PpmpTransaction::find($consoPpmpId);
            if(!$consoTransactionInfo) {
                DB::rollBack();
                return back()->with('error', 'Consolidated PPMP Id not found. Please select valid transaction and try again.');
            }

            #Validate Initial Quantity Adjustment
            if(!$consoTransactionInfo->init_qty_adjustment) {
                DB::rollBack();
                return back()->with('error', 'Updating Failed. Please set the Initial Quantity Adjustment before updating the Final Quantity Adjustmen!');
            }

            #Office PPMP Transaction IDs
            $officePpmpIds = json_decode($consoTransactionInfo->office_ppmp_ids);

            #Perform adjustment
            if($adjustmentType == 'grouped') {
                $this->all_finalOfficePpmpAdjustment($officePpmpIds, $groupData, $consoTransactionInfo->id);

                $consoTransactionInfo->update([
                    'final_qty_adjustment' => json_encode(['all' => $groupData]),
                    'updated_by' => Auth::id()
                ]);
            } else {
                $allProductsId = $this->getAllProducts_customType($customData);
                $this->custom_finalOfficePpmpAdjustment($allProductsId, $officePpmpIds, $customData, $consoTransactionInfo->id);

                $consoTransactionInfo->update([
                    'final_qty_adjustment' => json_encode($customData),
                    'updated_by' => Auth::id()
                ]);
            }

            DB::commit();
            return redirect()->back()->with('message', 'Initial Quantity Adjustment successfully updated.');
        }catch (\Exception $e) {
            DB::rollBack();
            Log::error(['Update Consolidated Initial Adjustment' => $e->getMessage()]);
            return redirect()->back()->with('error', 'An unexpected error occurred. Please try again.');
        }
    }

    private function all_finalOfficePpmpAdjustment($officePpmps, $adjustmentPercentage, $updatingTransactionId)
    {
        #Get all office ppmp transaction
        $ppmpTransactions = PpmpTransaction::with('particulars')->findMany($officePpmps)->keyBy('id');
        $adjustment = (float)$adjustmentPercentage / 100;

        #Updated all initials quantity adjustment
        foreach ($ppmpTransactions as $transaction) {
            $transaction->final_qty_adjustment = ['all' => (int)$adjustmentPercentage];
            $transaction->save();
        }

        #Group transactions by product Id
        $groupedParticulars = $ppmpTransactions->flatMap(function ($transaction) {
            return $transaction->particulars->filter(function ($particular) {
                return !is_null($particular->prod_id);
            });
        })->groupBy('prod_id');

        $groupedParticulars->map(function($transactions) use ($adjustment) {
            #Validate excemption
            $isProductExempted = $this->productService->validateProductExcemption($transactions->first()->prod_id);
            $consoFirstQty = 0;
            $consoSecondQty = 0;

            #Update each transaction particulars 
            foreach ($transactions as $transaction) {
                $adjustedFirstQty = $this->calculateAdjustedQty($transaction->adjusted_firstQty, $adjustment, $isProductExempted);
                $adjustedSecondQty = $this->calculateAdjustedQty($transaction->adjusted_secondQty, $adjustment, $isProductExempted);

                $consoFirstQty += $adjustedFirstQty;
                $consoSecondQty += $adjustedSecondQty;

                $transaction->update([
                    'tresh_first_qty' => $adjustedFirstQty,
                    'tresh_second_qty' => $adjustedSecondQty,
                ]);
            }
        });
    }

    private function custom_finalOfficePpmpAdjustment($products, $officePpmps, $customData, $updatingTransactionId)
    {
        #Get all office ppmp transaction
        $ppmpTransactions = PpmpTransaction::with('particulars')->findMany($officePpmps)->keyBy('id');

        #Account Class Loop
        foreach ($products as $index => $productIds) {
            $customValue = $customData[$index] ?? 100;
            $adjustment = (float)$customValue / 100;

            #Products under an account class
            foreach ($productIds as $prodId) {
                $consoFirstQty = 0;
                $consoSecondQty = 0;
                
                #Grouped transaction's particular
                $matchedParticulars = $ppmpTransactions->flatMap(function ($transaction) use ($prodId) {
                    return $transaction->particulars->filter(function ($particular) use ($prodId) {
                        return $particular->prod_id == $prodId;
                    });
                });

                #Update each transaction particulars 
                if($matchedParticulars) {
                    $isProductExempted = $this->productService->validateProductExcemption($prodId);
                    $matchedParticulars->map(function ($items) use ($adjustment, $isProductExempted, &$consoFirstQty, &$consoSecondQty){
                        $adjustedFirstQty = $this->calculateAdjustedQty($items->adjusted_firstQty, $adjustment, $isProductExempted);
                        $adjustedSecondQty = $this->calculateAdjustedQty($items->adjusted_secondQty, $adjustment, $isProductExempted);
                        
                        $consoFirstQty += $adjustedFirstQty;
                        $consoSecondQty += $adjustedSecondQty;
                        
                        $items->update([
                            'tresh_first_qty' => $adjustedFirstQty,
                            'tresh_second_qty' => $adjustedSecondQty,
                        ]);
                    });
                }
            }
        }
    }

    public function destroy(Request $request)
    {
        DB::beginTransaction();

        try {
            $ppmpTransaction = PpmpTransaction::with('particulars')
                ->where('id', $request->input('ppmpId'))
                ->first();
            
            if($ppmpTransaction->ppmp_type == 'individual'){
                $validateExistence = PpmpTransaction::where('ppmp_year', $ppmpTransaction->ppmp_year)
                    ->where('ppmp_type', 'consolidated')
                    ->where('ppmp_status', 'approved')
                    ->exists();
                
                if (!$validateExistence) {
                    $ppmpTransaction->particulars()->forceDelete();
                    $ppmpTransaction->forceDelete();

                    DB::commit();
                    return redirect()->back()
                        ->with('message', 'PPMP deletion was successful.');
                } else {

                    DB::rollback();
                    return redirect()->back()
                        ->with('error', 'Unable to delete the PPMP. Contact your system administrator with this matter!');
                }
            } elseif ($ppmpTransaction->ppmp_type == 'consolidated') {
                $consolidation = $ppmpTransaction->load([
                    'purchaseRequests', 
                    'consolidated' => function($q){
                        $q->withTrashed();
                    }
                ]);
                
                if($consolidation->purchaseRequests->isNotEmpty()) {
                    DB::rollback();
                    return redirect()->back()
                        ->with('error', 'Unable to delete the PPMP. Purchase Request/s was already been created on this transaction!');
                }

                foreach($consolidation->consolidated as $consoParticular) {
                   $consoParticular->forceDelete();
                }
                
                $consolidation->forceDelete();

                DB::commit();
                return redirect()->route('conso.ppmp.type', ['type' => 'consolidated' , 'status' => 'draft'])
                    ->with('message', 'PPMP deletion was successful.');
            } else {

                $ppmpTransaction->forceDelete();

                DB::commit();
                return redirect()->back()
                        ->with('message', 'Emergency type has been deleted successful.');
            }
            
        } catch (\Exception $e) {

            DB::rollBack();
            Log::error("Deletion of PPMP Failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'data' => $request
            ]);
            return back()->with('error', 'PPMP deletion failed. Please try again!');
        }
    }

    private function createPpmpTransaction(array $validatedData)#
    {
        return PpmpTransaction::create([
            'ppmp_code' => now()->format('YmdHis'),
            'ppmp_type' => $validatedData['ppmpType'],
            'ppmp_year' => $validatedData['ppmpYear'],
            'office_id' => $validatedData['office'],
            'created_by' => $validatedData['user'],
            'updated_by' => $validatedData['user'],
        ]);
    }

    private function createFundAllocation($desc, $sem,  $amount, $fundId)
    {
        FundAllocation::create([
            'description' => $desc,
            'semester' => $sem,
            'amount' => $amount,
            'cap_id' => $fundId,
        ]);
    }
    
    private function handleFileUpload($file)
    {
        return $file->storeAs('uploads', $file->getClientOriginalName());
    }

    private function processImportedLine($line, $ppmpId)
    {
        $newStock = $line['New_Stock_No'] ?? null;
        $code = $line['Old_Sotck_No'] ?? null;
        $janQty = is_numeric($line['Mar']) ? $line['Mar'] : 0;
        $mayQty = is_numeric($line['Aug']) ? $line['Aug'] : 0;
        $totalQuantity = intval($janQty) + intval($mayQty);

        # New Stock Pattern Comparison
        # !preg_match("/^\d{2}-\d{2}-\d{2,4}$/", $newStock) 
        if (!preg_match("/^\d{2}-\d{2}-\d{2,4}$/", $newStock) || $totalQuantity === 0) {
            return null;
        }
        
        $id = $newStock ?  $newStock : $code;
        $isProductValid = $this->productService->validateProduct($id);

        if (!$isProductValid) {
            return null;
        }

        PpmpParticular::create([
            'qty_first' => $janQty,
            'qty_second' => $mayQty,
            'adjusted_firstQty' => $janQty,
            'adjusted_secondQty' => $mayQty,
            'tresh_first_qty' => $janQty,
            'tresh_second_qty' => $mayQty,
            'prod_id' => $isProductValid['prodId'],
            'price_id' => $isProductValid['priceId'],
            'trans_id' => $ppmpId,
        ]);
    }

    private function validateIndivPpmp(array $validatedData)
    {
        return PpmpTransaction::where('ppmp_year', (string) $validatedData['ppmpYear'])
            ->where('office_id', $validatedData['office'])
            ->where('ppmp_type', 'individual')
            ->where('ppmp_status', 'draft')
            ->exists();
    }

    private function validateApprovedConsolidation($validatedData)
    {
        return PpmpTransaction::where('ppmp_type', $validatedData->ppmp_type)
            ->where('ppmp_year', $validatedData->ppmp_year)
            ->where('ppmp_status', 'approved')
            ->where('account_class_ids', $validatedData->account_class_ids)
            ->exists();
    }

    private function getIndividualPpmpTransactionsWithParticulars($request)
    {
        #Account class ids
        $accountClassIds = json_decode($request['accountClass'], true);

        #Flip account class ids as indexes
        $flipped = array_flip($accountClassIds);

        #Get product ids
        $accountsItems = $this->getAllProducts_customType($flipped);

        #Flat array
        $allowedParticulars = collect($accountsItems)
            ->flatten(1)
            ->unique()
            ->values();

        
        return PpmpTransaction::where('ppmp_year', $request['ppmpYear'])
            ->where('ppmp_type', 'individual')
            ->where('ppmp_status', $request['ppmpStatus'])
            ->whereHas('particulars', function ($query) use ($allowedParticulars) {
                $query->whereIn('prod_id', $allowedParticulars->all());
            })
            ->with(['particulars' => function ($query) use ($allowedParticulars) {
                $query->whereIn('prod_id', $allowedParticulars->all());
            }])
            ->get();
    }

    private function fetchOfficeWithPpmp($ppmpType, $year)
    {
        return PpmpTransaction::where('ppmp_year', $year)
                            ->where('ppmp_type', $ppmpType)
                            ->get();
    }

    private function fetchOfficeList()
    {
        return Office::where('office_status', 'active')
            ->orderBy('office_name', 'asc')
            ->get()
            ->map(fn($office) => [
                'id' => $office->id,
                'name' => $office->office_name,
            ]);
    }

    private function fetchApprovedConsolidatedPpmp($year, $type)
    {
        return PpmpTransaction::where('ppmp_year', $year)
            ->where('ppmp_type', $type)
            ->where('ppmp_status', 'approved')
            ->first();
    }

    private function getOfficesWithNoPpmp($ppmpType, $year)
    {
        $ppmpTransactions = $this->fetchOfficeWithPpmp($ppmpType, $year);
        $officeList = $this->fetchOfficeList();

        $officesWithPpmp = $ppmpTransactions->pluck('office_id')->toArray();

        $officesWithoutPpmp = $officeList->filter(function ($office) use ($officesWithPpmp) {
            return !in_array($office['id'], $officesWithPpmp);
        });

        return $officesWithoutPpmp;
    }
    
    private function calculateAdjustedQty($qty, $adjustment, $isExempted)
    {
        if (!$isExempted && $qty > 1) {
            return floor((int)$qty * (float)$adjustment);
        }

        return (int)$qty;
    }

    private function getQueryTransaction($request)#
    {
        if (!empty($request->selectedAccounts)) {
            return PpmpTransaction::where('ppmp_year', $request->selectedYear)
                ->where('ppmp_type', $request->selectedType)
                ->where('ppmp_status', 'draft')
                ->first();
        }
        return null;
    }

    private function prepareConsolidationData($request)#
    {
        return [
            'ppmpType' => 'consolidated',
            'ppmpYear' => $request->selectedYear,
            'ppmpStatus' => 'draft',
            'basePrice' => (float)$request->priceAdjust / 100,
            'accountClass' => json_encode($request->selectedAccounts),
            'office' => null,
            'user' => Auth::id(),
        ];
    }

    private function processOriginalVersion($individualPpmp, $consoTransactionId, $data, &$countUnavailableProduct)#
    {
        #Group transaction's particular by product id
        $groupParticulars = $individualPpmp->flatMap(function ($transaction) {
            return $transaction->particulars;
        })->groupBy('prod_id');

        #Execute store consolidation method
        $this->saveConsolidatedParticulars($groupParticulars, $consoTransactionId, $data, $countUnavailableProduct);
    }

    private function saveConsolidatedParticulars($groupParticulars, $consoTransactionId, $data, &$countUnavailableProduct)#
    {
        #Map Particular Items
        $groupParticulars->map(function ($items) use ($consoTransactionId, $data, &$countUnavailableProduct) {
            #Verify Item if active
            $isProductFound = $this->productService->verifyProductIfActive($items->first()->prod_id);
            if (!$isProductFound) {
                $countUnavailableProduct++;
                return null;
            }

            #Get latest product price
            $prodPriceId = $this->productService->getLatestPriceIdentification($items->first()->prod_id);

            #Add all quantity
            $qtyFirst = (int) $items->sum('qty_first');
            $qtySecond = (int) $items->sum('qty_second');
            
            #Create consolidated's particular
            PpmpConsolidated::create([
                'qty_first' => $qtyFirst,
                'qty_second' => $qtySecond,
                'prod_id' => $items->first()->prod_id,
                'price_id' => $prodPriceId,
                'trans_id' => $consoTransactionId,
                'created_by' => $data['user'],
                'updated_by' => $data['user'],
            ]);
        });
    }

    private function formattedAndSortedParticulars($ppmpTransaction)
    {
        $ppmpTransaction->load('consolidated');
        $groupParticulars = $ppmpTransaction->consolidated->map(function ($items) use ($ppmpTransaction) {
            $prodPrice = (float)$this->productService->getLatestPriceId($items->price_id) * (float)$ppmpTransaction->price_adjustment;

            $qtyFirst = (int) $items->qty_first;
            $qtySecond = (int) $items->qty_second;

            return [
                'prodId' => $items->prod_id,
                'prodCode' => $this->productService->getProductCode($items->prod_id),
                'prodName' => $this->productService->getProductName($items->prod_id),
                'prodUnit' => $this->productService->getProductUnit($items->prod_id),
                'prodPrice' => $prodPrice,
                'qtyFirst' => $qtyFirst,
                'qtySecond' => $qtySecond,
            ];
        });
        
        $sortedParticulars = $groupParticulars->sortBy('prodCode');

        return $sortedParticulars;
    }

    private function recapitulation($sortedParticulars, $funds, &$recapitulation, $year)
    {
        foreach ($funds as $fund) {

            if ($fund->categories->isEmpty()) {
                continue;
            }

            $fundFirstTotal = 0; 
            $fundSecondTotal = 0;
            $fundTotal = 0;

            foreach ($fund->categories as $category) {

                if ($category->items->isEmpty()) {
                    continue;
                }

                $catFirstTotal = 0; 
                $catSecondTotal = 0;
                $catTotal = 0;
    
                foreach ($category->items as $item) {
                    $this->processItemProducts($item, $sortedParticulars, $catFirstTotal, $catSecondTotal, $catTotal);
                }

                $recapitulation[$fund->fund_name][] =  $this->formatCategoryData($category->cat_name, $catTotal, $catFirstTotal, $catSecondTotal);
                
                $fundFirstTotal += $catFirstTotal; 
                $fundSecondTotal += $catSecondTotal;
                $fundTotal += $catTotal;
            }

            $this->addContingencyToRecapitulation($fund, $fundTotal, $recapitulation, $year);
        }
            
        return $recapitulation;
    }

    private function processItemProducts($item, $sortedParticulars, &$catFirstTotal, &$catSecondTotal, &$catTotal)
    {
        if ($item->products->isEmpty()) {
            return;
        }

        foreach ($item->products as $product) {
            $matchedParticulars = $sortedParticulars->where('prodCode', $product->prod_newNo);

            if ($matchedParticulars->isEmpty()) {
                continue;
            }

            foreach ($matchedParticulars as $particular) {
                $firstQtyAmount =  $particular['qtyFirst'] * (float) $particular['prodPrice'];
                $secondQtyAmount =  $particular['qtySecond'] * (float) $particular['prodPrice'];
                $prodQtyAmount = $firstQtyAmount + $secondQtyAmount;
                
                $catFirstTotal += $firstQtyAmount; 
                $catSecondTotal += $secondQtyAmount;
                $catTotal += $prodQtyAmount;
            }
        }
    }

    private function formatCategoryData($categoryName, $catTotal, $catFirstTotal, $catSecondTotal)
    {
        return [
            'name' => $categoryName,
            'total' => $catTotal,
            'firstSem' => $catFirstTotal,
            'secondSem' => $catSecondTotal,
        ];
    }

    private function addContingencyToRecapitulation($fund, $fundTotal, &$recapitulation, $year)
    {
        $capitalOutlay = $this->productService->getCapitalOutlay($year, $fund->id);

        if ($capitalOutlay <= 0) {
            throw new \Exception("No budget allotted to {$fund->fund_name}! Please check and try again!");
        }

        $contingency = $capitalOutlay - $fundTotal;
        $wholeNumber = floor($contingency);
        $cents = $contingency - $wholeNumber;
        $halfWholeNumber = floor($wholeNumber / 2);

        $contingencyFirst = $wholeNumber - $halfWholeNumber;
        $contingencySecond = $halfWholeNumber + $cents;

        $recapitulation[$fund->fund_name][] = [
            'name' => 'Contingency',
            'total' => $contingency,
            'firstSem' => $contingencyFirst,
            'secondSem' => $contingencySecond,
        ];
    }

    private function processFundAllocations($recapitulation, $year)
    {
        foreach($recapitulation as $expenses => $fund) {
            $fundId = Fund::where('fund_name', $expenses)->value('id');
            $capitalId = CapitalOutlay::where('year', $year)->where('fund_id', $fundId)->value('id');
            foreach($fund as $category) {
                $this->createFundAllocation($category['name'], '1st',  $category['firstSem'], $capitalId);
                $this->createFundAllocation($category['name'], '2nd',  $category['secondSem'], $capitalId);
            }
        }
    }

    private function updateOfficePpmpAdjustmentAndThreshold($officePpmpIds, $accountClassIds, $initAdjustment, $finalAdjustment)
    {
        #Get all office ppmp transaction
        $ppmpTransactions = PpmpTransaction::with('particulars')->findMany($officePpmpIds)->keyBy('id');

        #Flip account class ids as indexes
        $flipped = array_flip($accountClassIds);

        #Get product ids
        $accountsItems = $this->getAllProducts_customType($flipped);

        #Validate Inital Adjustment
        $isInitSingleAdjustment = count($initAdjustment) === 1;
        $singleInitValue = reset($initAdjustment);

        #Validate Final Adjustment
        $isFinalSingleAdjustment = count($finalAdjustment) === 1;
        $singleFinalValue = reset($finalAdjustment);

        #Transform Ppmp Transaction's particular into associative array
        $allParticulars = $ppmpTransactions->flatMap(fn($tx) => $tx->particulars);

        #Group particulars by id
        $particularsByProdId = $allParticulars->groupBy('prod_id');

        foreach($accountsItems as $account => $ids) {
            $initialValue = $isInitSingleAdjustment ? $singleInitValue : ($initAdjustment[$account] ?? null);
            $finalValue = $isFinalSingleAdjustment ? $singleFinalValue : ($finalAdjustment[$account] ?? null);
            
            foreach($ids as $prodId) {
                $groupedParticulars = $particularsByProdId[$prodId] ?? collect();

                $groupedParticulars->each(function($particular) use ($initialValue, $finalValue) {
                    $isProductExempted = $this->productService->validateProductExcemption($particular->prod_id);
                    $firstQtyInitial = $this->calculateAdjustedQty($particular->qty_first, $initialValue, $isProductExempted);
                    $secondQtyInitial = $this->calculateAdjustedQty($particular->qty_second, $initialValue, $isProductExempted);

                    $firstQtyFinal = $this->calculateAdjustedQty($firstQtyInitial, $finalValue, $isProductExempted);
                    $secondQtyFinal = $this->calculateAdjustedQty($secondQtyInitial, $finalValue, $isProductExempted);

                    $particular->update([
                        'adjusted_firstQty' => $firstQtyInitial,
                        'adjusted_secondQty' => $secondQtyInitial,
                        'tresh_first_qty' => $firstQtyFinal,
                        'tresh_second_qty' => $secondQtyFinal , 
                    ]);
                });
            }
        }
    }

    private function mapProductsToFundIds()
    {
        $funds = $this->productService->getAllProduct_FundModel();

        $map = collect();

        foreach ($funds as $fund) {
            foreach ($fund->categories as $category) {
                foreach ($category->items as $item) {
                    foreach ($item->products as $product) {
                        $map->put($product->id, $fund->id);
                    }
                }
            }
        }

        return $map;
    }

    private function getActiveProductList()
    {
        return Product::where('prod_status', 'active')
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'code' => $item->prod_newNo,
                'desc' => $item->prod_desc,
                'unit' => $item->prod_unit,
            ]);
    }

    private function formatParticulars(PpmpTransaction $ppmpTransaction)
    {
        return $ppmpTransaction->particulars->map(function ($particular) {
            return [
                'id' => $particular->id,
                'firstQty' => $particular->qty_first,
                'secondQty' => $particular->qty_second,
                'prodCode' => $this->productService->getProductCode($particular->prod_id),
                'prodName' => $this->productService->getProductName($particular->prod_id),
                'prodUnit' => $this->productService->getProductUnit($particular->prod_id),
                'prodPrice' => $this->productService->getLatestPriceId($particular->price_id),
            ];
        })->sortBy('prodCode')->values();
    }

    private function formatConsolidated(PpmpTransaction $ppmpTransaction)
    {
        return $ppmpTransaction->consolidated->map(function ($particular) {
            return [
                'id' => $particular->id,
                'firstQty' => $particular->qty_first,
                'secondQty' => $particular->qty_second,
                'prodCode' => $this->productService->getProductCode($particular->prod_id),
                'prodName' => $this->productService->getProductName($particular->prod_id),
                'prodUnit' => $this->productService->getProductUnit($particular->prod_id),
                'prodPrice' => $this->productService->getLatestPriceId($particular->price_id),
            ];
        })->sortBy('prodCode')->values();
    }
}
