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
            ->where('ppmp_version', 1)
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

    public function store(Request $request)
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

    public function storeCopy(Request $request)
    { 
        DB::beginTransaction();
        
        try {
            $userId = Auth::id();
            $transaction = PpmpTransaction::with('consolidated')->findOrFail($request->ppmpId);

            $transactionDetails = [
                'ppmpType' => $transaction->ppmp_type,
                'ppmpYear' => $transaction->ppmp_year,
                'office' => null,
                'user' => $userId,
            ];

            $newTransaction = $this->createPpmpTransaction($transactionDetails);

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

    public function storeConsolidated(Request $request)
    {
        DB::beginTransaction();

        try {
            $countUnavailableProduct = 0;

            $queryTransaction = $this->getQueryTransaction($request);
            if (!$queryTransaction) {
                DB::rollBack();
                return back()->with(['error' => 'Request is incomplete. Please try again.']);
            }

            $data = $this->prepareConsolidationData($request, $queryTransaction);
            $existingConsoPpmp = $this->validateConsoPpmp($data);
            $data['newVersion'] = $existingConsoPpmp ? $existingConsoPpmp->ppmp_version + 1 : 1;

            $createConsolidation = $this->createPpmpTransaction($data);
            $individualPpmp = $this->getIndividualPpmpTransactionsWithParticulars($data);

            if ($request->selectedVersion == "original") {
                $this->processOriginalVersion($individualPpmp, $createConsolidation, $data, $countUnavailableProduct);
            } elseif ($request->selectedVersion == "adjustment") {
                $this->processAdjustmentVersion($individualPpmp, $createConsolidation, $data, $countUnavailableProduct);
            }

            $this->finalizeConsolidation($createConsolidation, $data);

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

    public function storeAsFinal(Request $request, PpmpTransaction $ppmpTransaction)
    {
        DB::beginTransaction();
        
        $officePpmpStatus = 'individual';
        $year = $ppmpTransaction->ppmp_year;
        $type = $ppmpTransaction->ppmp_type;
        $status = $ppmpTransaction->ppmp_status;
        $qtyAdjustment = $ppmpTransaction->qty_adjustment;
        $qtyThreshold = $ppmpTransaction->tresh_adjustment;
        $userId = $request->user;
        $recapitulation = [];

        $officePpmp = $this->fetchOfficeWithPpmp($officePpmpStatus, $year);

        try {

            $isTransactionExist = $this->fetchApprovedConsolidatedPpmp($year, $type);

            if ($isTransactionExist) {
                DB::rollBack();
                return redirect()->back()->with([
                    'error' => 'Approved PPMP already exist with transaction No.' . $isTransactionExist->ppmp_code
                ]);
            }

            $sortedParticulars = $this->formattedAndSortedParticulars($ppmpTransaction);
            $funds = $this->productService->getAllProduct_FundModel();

            $this->recapitulation($sortedParticulars, $funds, $recapitulation, $year);
            $this->processFundAllocations($recapitulation, $year);
            $this->updateOfficePpmpAdjustmentAndThreshold($officePpmp, $userId, $qtyAdjustment, $qtyThreshold);

            $ppmpTransaction->update(['ppmp_status' => 'approved', 'updated_by' => $userId]);
            DB::commit();
            return redirect()->route('conso.ppmp.type', ['type' => $type, 'status' => $status])->with('message', 'Proceeding to Approved PPMP successfully executed');

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
            // UNCOMMENT IF EMERGENCY PRODUCT LIST IS WITHIN CONSOLIDATION ONLY
            // : $this->getConsolidatedProductList($ppmpTransaction);
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
        $ppmpTransaction->load('updater', 'consolidated');
        $ppmpTransaction->ppmp_type = ucfirst($ppmpTransaction->ppmp_type);
        $countTrashedItems = $ppmpTransaction->consolidated()->onlyTrashed()->count();
        $accountClass = $this->productService->getActiveFunds();

        $groupParticulars = $ppmpTransaction->consolidated->map(function ($items) use (&$totalAmount, $ppmpTransaction) {
            $prodPrice = (float)$this->productService->getLatestPriceId($items->price_id) * $ppmpTransaction->price_adjustment;
            #RETURN IT BACK IF PRICE SHOULD BE ROUND UP ALL FLOAT PRICES
            //$prodPrice = $prodPrice != null ? (float) ceil($prodPrice) : 0;
            
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

    public function showIndividualPpmp_Type(Request $request): Response
    {
        $transactions = PpmpTransaction::select('ppmp_type', 'ppmp_year', 'ppmp_version')
            ->where(function($query) {
                $query->whereIn('ppmp_type', ['individual', 'emergency']);
            })
            ->where('ppmp_status', 'draft')
            ->get();
        
        $result = $transactions->groupBy('ppmp_type')->map(function ($group) {
            $years = $group->groupBy('ppmp_year')->map(function ($yearGroup) {
                return [
                    'ppmp_year' => $yearGroup->first()->ppmp_year,
                    //'versions' => $yearGroup->pluck('ppmp_version')->unique()
                ];
            })->values()->all();
        
            return [
                'ppmp_type' => $group->first()->ppmp_type,
                'years' => $years
            ];
        })->values()->all();
        
        $ppmpTransactions = PpmpTransaction::with('requestee', 'updater')
            ->whereIn('ppmp_type', ['individual', 'emergency'])
            ->where('ppmp_status', $request->status)
            ->orderBy('ppmp_code', 'desc')
            ->get();

        $request['status'] = ucfirst($request['status']);
        $request['type'] = ucfirst($request['type']);

        return Inertia::render('Ppmp/PpmpList', ['ppmpTransaction' =>  $ppmpTransactions, 'ppmp' =>  $request, 'types' => $result]);
    }

    public function showConsolidatedPpmp_Type(Request $request): Response
    {
        $individualList = PpmpTransaction::select('ppmp_type', 'ppmp_year', 'ppmp_version')
            ->where('ppmp_type', 'individual')
            ->where('ppmp_status', 'draft')
            ->get();

        $result = $individualList->groupBy('ppmp_type')->map(function ($group) {
                $years = $group->groupBy('ppmp_year')->map(function ($yearGroup) {
                    return [
                        'ppmp_year' => $yearGroup->first()->ppmp_year,
                        'versions' => $yearGroup->pluck('ppmp_version')->unique()
                    ];
                })->values()->all();
            
                return [
                    'ppmp_type' => $group->first()->ppmp_type,
                    'years' => $years
                ];
            })->values()->all();
        
        $transactions = PpmpTransaction::with('updater')
            ->where('ppmp_type', $request->type)
            ->where('ppmp_status', $request->status)
            ->orderBy('created_at', 'desc')
            ->get();

        $transactions = $transactions->map(function ($transaction) {
            $priceAdjustment = $transaction->price_adjustment ? ((float)$transaction->price_adjustment * 100) : 0;
            $qtyAdjust = $transaction->qty_adjustment ? ((float)$transaction->qty_adjustment * 100) : 0;
            $threshold = $transaction->tresh_adjustment ? ((float)$transaction->tresh_adjustment * 100) : 0;
            $details = '<i><b>@</b>'. $priceAdjustment . '% ' . 'Price Adjustment' . '<br>'
                    .'<b>@</b>'. $qtyAdjust . '% ' . 'Quantity Adjustment' .  '<br>' 
                    .'<b>@</b>' . $threshold . '% ' . 'Maximum Adjustment' . '<br>'
                    . ($transaction->remarks ? '<b>@</b>'. $transaction->remarks . '</i>': '</i>');

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
        return Inertia::render('Ppmp/DraftConsolidatedList', ['ppmp' => $request, 'transactions' => $transactions, 'individualList' => $result]);
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
        $consoPpmpId = $request->input('ppmpId');
        $adjustmentType = $request->input('adjustmentType');
        $customData = $request->input('customInitAdjustment') 
            ? array_filter($request->input('customInitAdjustment'), function($value) {
                return !is_null($value);
            })
            : null;

        DB::beginTransaction();

        try {
            $consoTransactionInfo = PpmpTransaction::find($consoPpmpId);

            if(!$consoTransactionInfo) {
                DB::rollBack();
                return back()->with('error', 'Consolidated PPMP Id not found. Please select valid transaction and try again.');
            }

            $allProductsId = $adjustmentType == 'grouped' 
                ? $this->getAllProducts_groupedType() 
                : $this->getAllProducts_customType($customData);

            $officePpmpIds = json_decode($consoTransactionInfo->office_ppmp_ids);

            $this->initOfficePpmpAdjustment($allProductsId, $officePpmpIds, $customData, $consoTransactionInfo->id);

            $consoTransactionInfo->update([
                'baseline_adjustment_type' => 'custom',
                'init_qty_adjustment' => json_encode($customData),
                'updated_by' => Auth::id()
            ]);

            DB::commit();

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
                    $q->where('cat_status', 'active')->select('id', 'fund_id', 'cat_code', 'cat_name');
                },
                'categories.items' => function ($q) {
                    $q->where('item_status', 'active')->select('id', 'cat_id', 'item_code', 'item_name');
                },
                'categories.items.products' => function ($q) {
                    $q->where('prod_status', 'active')->select('id', 'item_id', 'prod_newNo', 'prod_desc', 'prod_unit', 'prod_oldNo');
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

    private function getAllProducts_groupedType()
    {
        $funds = $this->productService->getActiveProduct_FundModel();

        $productIds = $funds->flatMap(function ($fund) {
            return $fund->categories->flatMap(function ($category) {
                return $category->items->flatMap(function ($item) {
                    return $item->products->pluck('id');
                });
            });
        })->unique()->values();

        return $productIds;
    }

    private function initOfficePpmpAdjustment($products, $officePpmps, $customData, $updatingTransactionId)
    {
        $ppmpTransactions = PpmpTransaction::with('particulars')->findMany($officePpmps)->keyBy('id');

        foreach ($products as $index => $productIds) {
            $customValue = $customData[$index] ?? 100;
            $adjustment = (float)$customValue / 100;

            foreach ($productIds as $prodId) {
                $consoFirstQty = 0;
                $consoSecondQty = 0;
                $matchedParticulars = $ppmpTransactions->flatMap(function ($transaction) use ($prodId) {
                    return $transaction->particulars->filter(function ($particular) use ($prodId) {
                        return $particular->prod_id == $prodId;
                    });
                });

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
                $ppmpTransaction = PpmpTransaction::with('consolidated', 'purchaseRequests')
                    ->where('id', $request->input('ppmpId'))
                    ->first();
            
                if($ppmpTransaction->purchaseRequests->isNotEmpty()) {
                    DB::rollback();
                    return redirect()->back()
                        ->with('error', 'Unable to delete the PPMP. Purchase Request/s was already been created on this transaction!');
                }
                
                if ($ppmpTransaction->consolidated instanceof Collection) {
                    foreach ($ppmpTransaction->consolidated as $consolidatedItem) {
                        $consolidatedItem->forceDelete();
                    }
                } else {
                    $ppmpTransaction->consolidated->forceDelete();
                }
                
                $ppmpTransaction->forceDelete();

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

    private function createPpmpTransaction(array $validatedData)
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

    private function validateConsoPpmp(array $validatedData)
    {
        return PpmpTransaction::where('ppmp_type', 'consolidated')
            ->where('ppmp_year', $validatedData['ppmpYear'])
            ->where('ppmp_status', $validatedData['ppmpStatus'])
            ->orderBy('created_at', 'desc')
            ->first();
    }

    private function getIndividualPpmpTransactionsWithParticulars($request)
    {
        return PpmpTransaction::with('particulars')
            ->where('ppmp_year', $request['ppmpYear'])
            ->where('ppmp_type', 'individual')
            ->where('ppmp_status', $request['ppmpStatus'])
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

    private function updateIndividualPpmp($transactions, $adjustment)
    {
        foreach ($transactions as $transaction) {
            foreach ($transaction->particulars as $particular) {
                $isProductExempted = $this->productService->validateProductExcemption($particular->prod_id);
                $adjustFirstQty = $this->calculateAdjustedQty($particular->qty_first, $adjustment, $isProductExempted);
                $adjustSecondQty = $this->calculateAdjustedQty($particular->qty_second, $adjustment, $isProductExempted);
    
                $particular->update([
                    'adjusted_firstQty' => $adjustFirstQty,
                    'adjusted_secondQty' => $adjustSecondQty,
                ]);
            }
            $transaction->update(['qty_adjustment' => $adjustment, 'updated_by' => Auth::id()]);
        }
    }
    
    private function calculateAdjustedQty($qty, $adjustment, $isExempted)
    {
        if (!$isExempted && $qty > 1) {
            return floor((int)$qty * $adjustment);
        }

        return (int)$qty;
    }

    private function getQueryTransaction($request)
    {
        if ($request->selectedVersion == "original" || $request->selectedVersion == "adjustment") {
            return PpmpTransaction::where('ppmp_year', $request->selectedYear)
                ->where('ppmp_type', $request->selectedType)
                ->where('ppmp_status', 'draft')
                ->first();
        }
        return null;
    }

    private function prepareConsolidationData($request, $queryTransaction)
    {
        return [
            'ppmpType' => 'consolidated',
            'ppmpYear' => $request->selectedYear,
            'ppmpStatus' => 'draft',
            'basePrice' => $request->selectedVersion == "original" ? $queryTransaction->price_adjustment : ((float)$request->priceAdjust / 100),
            'qtyAdjust' => $request->selectedVersion == "original" ? 1.00 : ((float)$request->qtyAdjust / 100),
            'threshold' => ((float)$request->threshold / 100),
            'office' => null,
            'user' => Auth::id(),
        ];
    }

    private function processOriginalVersion($individualPpmp, $createConsolidation, $data, &$countUnavailableProduct)
    {
        $groupParticulars = $individualPpmp->flatMap(function ($transaction) {
            return $transaction->particulars;
        })->groupBy('prod_id');

        $this->saveConsolidatedParticulars($groupParticulars, $createConsolidation, $data, $countUnavailableProduct);
    }

    private function processAdjustmentVersion($individualPpmp, $createConsolidation, $data, &$countUnavailableProduct)
    {
        $this->updateIndividualPpmp($individualPpmp, $data['qtyAdjust'], $data['ppmpYear']);
        $updatedIndividualPpmp = $this->getIndividualPpmpTransactionsWithParticulars($data);
        $groupParticulars = $updatedIndividualPpmp->flatMap(function ($transaction) {
            return $transaction->particulars;
        })->groupBy('prod_id');

        $this->saveConsolidatedParticulars($groupParticulars, $createConsolidation, $data, $countUnavailableProduct);
    }

    private function saveConsolidatedParticulars($groupParticulars, $createConsolidation, $data, &$countUnavailableProduct)
    {
        $groupParticulars->map(function ($items) use ($createConsolidation, $data, &$countUnavailableProduct) {
            $isProductFound = $this->productService->verifyProductIfActive($items->first()->prod_id);
            if (!$isProductFound) {
                $countUnavailableProduct++;
                return null;
            }

            $prodPriceId = $this->productService->getLatestPriceIdentification($items->first()->prod_id);

            if ($data['qtyAdjust'] == 1)
            {
                $qtyFirst = (int) $items->sum('qty_first');
                $qtySecond = (int) $items->sum('qty_second');
            } else {
                $qtyFirst = (int) $items->sum('adjusted_firstQty');
                $qtySecond = (int) $items->sum('adjusted_secondQty');
            }
            
            PpmpConsolidated::create([
                'qty_first' => $qtyFirst,
                'qty_second' => $qtySecond,
                'prod_id' => $items->first()->prod_id,
                'price_id' => $prodPriceId,
                'trans_id' => $createConsolidation->id,
                'created_by' => $data['user'],
                'updated_by' => $data['user'],
            ]);
        });
    }

    private function finalizeConsolidation($createConsolidation, $data)
    {
        $createConsolidation->update([
            'price_adjustment' => $data['basePrice'],
            'qty_adjustment' => $data['qtyAdjust'],
            'tresh_adjustment' => $data['threshold'],
            'ppmp_version' => $data['newVersion'],
        ]);
    }

    private function formattedAndSortedParticulars($ppmpTransaction) {
        $ppmpTransaction->load('consolidated');
        $groupParticulars = $ppmpTransaction->consolidated->map(function ($items) use ($ppmpTransaction) {
            $prodPrice = (float)$this->productService->getLatestPrice($items->prod_id) * (float)$ppmpTransaction->price_adjustment;
            #RETURN IT BACK IF PRICE SHOULD BE ROUND UP ALL FLOAT PRICES
            //$prodPrice = $prodPrice != null ? (float) ceil($prodPrice) : 0;
            $latestPriceId = $this->productService->getLatestPriceIdentification($items->prod_id);

            if ($items->price_id !== $latestPriceId) {
                $items->update(['price_id' => $latestPriceId]);
            }

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

    private function updateOfficePpmpAdjustmentAndThreshold($officePpmp, $userId, float $qtyAdjustment, float $qtyThreshold)
    {
        foreach ($officePpmp as $ppmp) {
            foreach ($ppmp->particulars as $particular) {
                $isProductExempted = $this->productService->validateProductExcemption($particular->prod_id);
                $adjustedFirstQty = $this->calculateAdjustedQty($particular->qty_first, $qtyAdjustment, $isProductExempted);
                $adjustedSecondQty = $this->calculateAdjustedQty($particular->qty_second, $qtyAdjustment, $isProductExempted);

                $thresholdFirstQty = $this->calculateAdjustedQty($particular->qty_first, $qtyThreshold, $isProductExempted);
                $thresholdSecondQty = $this->calculateAdjustedQty($particular->qty_second, $qtyThreshold, $isProductExempted);

                $particular->update([
                    'adjusted_firstQty' => $adjustedFirstQty,
                    'adjusted_secondQty' => $adjustedSecondQty,
                    'tresh_first_qty' => $thresholdFirstQty,
                    'tresh_second_qty' => $thresholdSecondQty , 
                ]);
            }

            $ppmp->update([
                'qty_adjustment' => $qtyAdjustment,
                'tresh_adjustment' => $qtyThreshold,
                'ppmp_status' => 'approved',
                'updated_by' => $userId , 
            ]);
        }
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

    private function getConsolidatedProductList(PpmpTransaction $ppmpTransaction)
    {
        $appParticulars = PpmpTransaction::where('ppmp_type', 'consolidated')
            ->where('ppmp_year', $ppmpTransaction->ppmp_year)
            ->where('ppmp_status', $ppmpTransaction->ppmp_status)
            ->with('consolidated')
            ->first();

        if (!$appParticulars) {
            return collect();
        }

        return $appParticulars->consolidated->map(function ($item) {
            return [
                'id' => $item->prod_id,
                'code' => $this->productService->getProductCode($item->prod_id),
                'desc' => $this->productService->getProductName($item->prod_id),
                'unit' => $this->productService->getProductUnit($item->prod_id),
            ];
        });
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
