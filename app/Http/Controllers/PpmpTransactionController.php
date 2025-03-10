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
        $currentYear = Carbon::now()->year;
        
        $officePpmpExist = PpmpTransaction::where(function($query) use ($currentYear) {
                $query->where(function($query) {
                    $query->where('ppmp_type', 'individual')
                        ->orWhere('ppmp_type', 'contingency');
                })
                ->where('ppmp_status', 'draft')
                ->where('ppmp_version', 1)
                ->whereYear('created_at', $currentYear);
            })
            ->with(['requestee', 'creator'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($ppmp) {
                return [
                    'id' => $ppmp->id,
                    'ppmpCode' => $ppmp->ppmp_code,
                    'ppmpType' => ucfirst($ppmp->ppmp_type),
                    'basedPrice' => ((float) $ppmp->price_adjustment * 100) . '%',
                    'officeId' => $ppmp->office_id,
                    'officeCode' => $ppmp->requestee->office_name ?? '',
                    'creator' => $ppmp->creator->name ?? ''
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
            'user' => 'required|integer',
            'file' => 'nullable|file|mimes:xls,xlsx',
        ]);

        try {
            if($validatedData['ppmpType'] == 'individual') {
                if ($this->validateIndivPpmp($validatedData)) {
                    DB::rollBack();
                    return redirect()->back()->with(['error' => 'Office PPMP already exists!']);
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
                    ->with(['message' => 'PPMP creation was successful! You can now check the list to add products.']);
            } elseif ($validatedData['ppmpType'] == 'contingency') {

                DB::rollback();
                return redirect()->back()
                ->with(['error' => 'Contingency creation is not yet available. Please refer to your system administrator for this action.']);
            } else {

                DB::rollback();
                return redirect()->back()
                ->with(['error' => '404 - Not Found!']);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('File create ppmp error: ' . $e->getMessage());
            return redirect()->back()
                ->with(['error' => 'PPMP creation was failed. Please contact your system administrator.']);
        }
    }

    public function storeCopy(Request $request)
    { 
        DB::beginTransaction();
        try {
            $percentage = (float)$request->input('qtyAdjust') / 100;
            $transactions = PpmpTransaction::with('particulars')
                ->where('ppmp_year', $request->input('selectedYear'))
                ->where('ppmp_type', $request->input('selectedType'))
                ->where('ppmp_status', 'draft')
                ->where('ppmp_version', 1)
                ->get();

            if(!$transactions) {
                DB::rollBack();
                return redirect()->back()->with([
                    'error' => 'No drafted PPMP Found!'  
                ]);
            }

            $ppmpExist = PpmpTransaction::where('ppmp_year', $request->input('selectedYear'))
                ->where('ppmp_type', $request->input('selectedType'))
                ->where('ppmp_status', 'approved')
                ->whereNotNull('tresh_adjustment')
                ->exists();

            if($ppmpExist) {
                DB::rollBack();
                return redirect()->back()->with([
                    'error' => 'Quantity Adjustment for the Individual PPMP has already been set!'
                ]);
            }

            foreach ($transactions as $transaction) {
                foreach ($transaction->particulars as $particular) {
                    $isExempted = $this->productService->validateProductExcemption($particular->prod_id, $transaction->ppmp_year);
                    $modifiedQtyFirst = !$isExempted && $particular->qty_first > 1 ? floor((int)$particular->qty_first * $percentage) : $particular->qty_first;
                    $modifiedQtySecond = !$isExempted && $particular->qty_second > 1 ? floor((int)$particular->qty_second * $percentage) : $particular->qty_second;

                    $particular->update(['tresh_first_qty' => $modifiedQtyFirst, 'tresh_second_qty' => $modifiedQtySecond]);
                    $transaction->update(['tresh_adjustment' => $percentage, 'updated_by' => Auth::id()]);
                }
            }

            DB::commit();
            return redirect()->route('indiv.ppmp.type', ['type' => 'individual' , 'status' => 'draft'])
                ->with(['message' => 'Acopy of PPMP was successful created! You may reload the browser to see the created copy of the PPMP.']);
        } catch (\Exception $e) {

            DB::rollBack();
            Log::error('Make a copy for PPMP error: ' . $e->getMessage());
            return redirect()->back()
                ->with(['error' => 'A copy of the PPMP generated failed. Please contact your system administrator.']);
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
                return redirect()->back()->with(['error' => 'Request is incomplete. Please try again.']);
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
            Log::error('Consolidation error: ' . $e->getMessage());
            return redirect()->back()
                ->with(['error' => 'Consolidated generation failed. Please contact your system administrator.']);
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
            Log::error('Proceed to Final PPMP error: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function showIndividualPpmp(PpmpTransaction $ppmpTransaction)
    {
        $ppmpTransaction->load('particulars', 'requestee');
        $list = Product::where('prod_status', 'active')
                ->get()
                ->map(fn($item) => [
                    'id' => $item->id,
                    'code' => $item->prod_newNo,
                    'desc' => $item->prod_desc,
                    'unit' => $item->prod_unit,
                ]);
        
        $ppmpParticulars = $ppmpTransaction->particulars->map(fn($particular) => [
            'id' => $particular->id,
            'firstQty' => $particular->qty_first,
            'secondQty' => $particular->qty_second,
            'prodCode' => $this->productService->getProductCode($particular->prod_id),
            'prodName' => $this->productService->getProductName($particular->prod_id),
            'prodUnit' => $this->productService->getProductUnit($particular->prod_id),
            'prodPrice' => $this->productService->getLatestPriceId($particular->price_id),
        ])->sortBy('prodCode');

        $ppmpTransaction['totalItems'] = $ppmpParticulars->count();
        $grandTotal = $ppmpParticulars->sum(fn($particular) => (((int) $particular['firstQty'] + (int) $particular['secondQty']) * (float) $particular['prodPrice']));

        $ppmpTransaction['formattedOverallPrice'] = number_format($grandTotal, 2, '.', ',');

        return Inertia::render('Ppmp/Individual', ['ppmp' =>  $ppmpTransaction, 'ppmpParticulars' => $ppmpParticulars, 'products' => $list, 'user' => Auth::id(),]);
    }

    public function showConsolidatedPpmp(PpmpTransaction $ppmpTransaction) {
        $totalAmount = 0;
        $ppmpTransaction->load('updater', 'consolidated');
        $ppmpTransaction->ppmp_type = ucfirst($ppmpTransaction->ppmp_type);
        $countTrashedItems = $ppmpTransaction->consolidated()->onlyTrashed()->count();

        $groupParticulars = $ppmpTransaction->consolidated->map(function ($items) use (&$totalAmount, $ppmpTransaction) {
            $prodPrice = (float)$this->productService->getLatestPriceId($items->price_id) * $ppmpTransaction->price_adjustment;
            $prodPrice = $prodPrice != null ? (float) ceil($prodPrice) : 0;
            
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
            'user' => Auth::id(),
        ]);
    }

    public function showIndividualPpmp_Type(Request $request): Response
    {
        $transactions = PpmpTransaction::select('ppmp_type', 'ppmp_year', 'ppmp_version')
            ->where(function($query) {
                $query->where('ppmp_type', 'emergency')
                      ->orWhere('ppmp_type', 'individual');
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
            ->where('ppmp_type', $request->type)
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
            return [
                'id' => $transaction->id,
                'code' => $transaction->ppmp_code,
                'ppmpYear' => $transaction->ppmp_year,
                'priceAdjust' => $transaction->price_adjustment ? ((float)$transaction->price_adjustment * 100) : 0,
                'qtyAdjust' => $transaction->qty_adjustment ? ((float)$transaction->qty_adjustment * 100) : 0,
                'threshold' => $transaction->tresh_adjustment ? ((float)$transaction->tresh_adjustment * 100) : 0,
                'createdAt' => $transaction->created_at->format('F d, Y'),
                'updatedBy' => optional($transaction->updater)->name ?? 'Unknown',
            ];
        });

        $request['status'] = ucfirst($request['status']);
        $request['type'] = ucfirst($request['type']);
        return Inertia::render('Ppmp/DraftConsolidatedList', ['ppmp' => $request, 'transactions' => $transactions, 'individualList' => $result]);
    }

    public function showOfficeListWithNoPpmp(Request $request) {
        $result = [];
        if ($request->ppmpType == 'individual') {
            $result = $this->getOfficesWithNoPpmp($request->ppmpType, $request->ppmpYear);
        } elseif ($request->ppmpType == 'contingency') {
            $result = $this->fetchOfficeList();
        } else {
            $result = [];
        }
        return response()->json(['data' => $result]);
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
                        ->with(['message' => 'PPMP deletion was successful.']);
                } else {

                    DB::rollback();
                    return redirect()->back()
                        ->with(['error' => 'Unable to delete the PPMP. Contact your system administrator with this matter!']);
                }
            } elseif ($ppmpTransaction->ppmp_type == 'consolidated') {
                $ppmpTransaction = PpmpTransaction::with('consolidated', 'purchaseRequests')
                    ->where('id', $request->input('ppmpId'))
                    ->first();
            
                if($ppmpTransaction->purchaseRequests->isNotEmpty()) {
                    DB::rollback();
                    return redirect()->back()
                        ->with(['error' => 'Unable to delete the PPMP. Purchase Request/s was already been created on this transaction!']);
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
                    ->with(['message' => 'PPMP deletion was successful.']);
            } else {

                DB::rollback();
                    return redirect()->back()
                        ->with(['error' => 'This action is under construction!']);
            }
            
        } catch (\Exception $e) {

            DB::rollback();
            Log::error('File delete ppmp error: ' . $e->getMessage());
            return redirect()->back()
                ->with(['error' => 'PPMP deletion failed. Please contact your system administrator']);
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
        $janQty = is_numeric($line['Jan']) ? $line['Jan'] : 0;
        $mayQty = is_numeric($line['May']) ? $line['May'] : 0;
        $totalQuantity = intval($janQty) + intval($mayQty);

         # New Stock Pattern Comparison
        # !preg_match("/^\d{2}-\d{2}-\d{2,4}$/", $newStock) 
        if (!preg_match("/^\d{4}$/", $code) || $totalQuantity === 0) {
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
            return floor((int)$qty * (float)$adjustment);
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
            $prodPrice = $prodPrice != null ? (float) ceil($prodPrice) : 0;
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
}
