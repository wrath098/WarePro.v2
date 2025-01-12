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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
        $currentYear = date('Y');
        
        $officePpmpExist = PpmpTransaction::with('requestee')
            ->where(function($query) use ($currentYear) {
                $query->where(function($query) {
                    $query->where('ppmp_type', 'individual')
                        ->orWhere('ppmp_type', 'contingency');
                })
                ->where('ppmp_status', 'draft')
                ->where('ppmp_version', 1)
                ->whereYear('created_at', $currentYear);
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($ppmp) {
                return [
                    'id' => $ppmp->id,
                    'ppmpCode' => $ppmp->ppmp_code,
                    'ppmpType' => ucfirst($ppmp->ppmp_type),
                    'basedPrice' => $ppmp->price_adjustment,
                    'officeId' => $ppmp->office_id,
                    'officeCode' => $ppmp->requestee->office_name ?? ''
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
                    return redirect()->back()->with(['error' => 'Office PPMP already exists!']);
                }

                DB::transaction(function () use ($validatedData, $request) {
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
                });
                return redirect()->route('import.ppmp.index')
                    ->with(['message' => 'PPMP creation was successful! You can now check the list to add products.']);
            } elseif ($validatedData['ppmpType'] == 'contingency') {
                return redirect()->back()
                ->with(['error' => 'This Feature is under construction. Please refer to your system administrator for this action.']);
            } else {
                return redirect()->back()
                ->with(['error' => '404 - Not Found!']);
            }
        } catch (\Exception $e) {
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
                ->with(['message' => 'Make a copy of PPMP was successful created! You may reload the browser to see the created copy of the PPMP.']);
        } catch (\Exception $e) {

            DB::rollBack();
            Log::error('Make a copy for PPMP error: ' . $e->getMessage());
            return redirect()->back()
                ->with(['error' => 'Make a copy of the PPMP failed. Please contact your system administrator.']);
        }
    }

    public function storeConsolidated(Request $request)
    {
        try {
            $ppmp = PpmpTransaction::where('ppmp_year', $request->selectedYear)
                ->where('ppmp_type', $request->selectedType)
                ->where('ppmp_version', $request->selectedVersion)
                ->where('ppmp_status', 'draft')
                ->firstOrFail();
            
            $data = [
                'ppmpType' => 'consolidated',
                'ppmpYear' => $ppmp->ppmp_year,
                'ppmpStatus' => $ppmp->ppmp_status, 
                'basePrice' => $ppmp->price_adjustment, 
                'qtyAdjust' => $ppmp->qty_adjustment,
                'version' => $ppmp->ppmp_version,
                'office' => null,
                'user' => Auth::id(),
            ];

            $existingConsoPpmp = $this->validateConsoPpmp($data);
            $data['newVersion'] = $existingConsoPpmp ? $existingConsoPpmp->ppmp_version + 1 : 1;

            DB::transaction(function () use ($data) {
                $createConsolidation = $this->createPpmpTransaction($data);                
                $individualPpmp = $this->getIndividualPpmpTransactionsWithParticulars($data);
                $groupParticulars = $individualPpmp->flatMap(function ($transaction) {
                    return $transaction->particulars;
                })->groupBy('prod_id')->map(function ($items) use ($createConsolidation, $data) {
                    $prodPrice = $this->productService->getLatestPriceIdentification($items->first()->prod_id);
                    
                    $qtyFirst = (int) $items->sum('qty_first');
                    $qtySecond = (int) $items->sum('qty_second');

                    PpmpConsolidated::create([
                        'qty_first' => $qtyFirst,
                        'qty_second' => $qtySecond,
                        'prod_id' => $items->first()->prod_id,
                        'price_id' => $prodPrice,
                        'trans_id' => $createConsolidation->id,
                        'created_by' => $data['user'],
                        'updated_by' => $data['user'],
                    ]);

                });
                $createConsolidation->update([
                    'price_adjustment' => $data['basePrice'],
                    'qty_adjustment' => $data['qtyAdjust'],
                    'ppmp_version' => $data['newVersion'],
                ]);
            });
    
            return redirect()->back()->with(['message' => 'Consolidated has been generated successfully!']);
        } catch (\Exception $e) {
            Log::error('Consolidation error: ' . $e->getMessage());
            return redirect()->back()
                ->with(['error' => 'Consolidated generation failed. Please contact your system administrator.']);
        }
    }

    public function storeAsFinal(Request $request, PpmpTransaction $ppmpTransaction)
    {
        DB::beginTransaction();

        $year = $ppmpTransaction->ppmp_year;
        $type = $ppmpTransaction->ppmp_type;
        $status = $ppmpTransaction->ppmp_status;
        $userId = $request->user;
        $recapitulation = [];
        $ppmpTransaction->load('consolidated');

        try {
            $existTransaction = PpmpTransaction::where('ppmp_year', $year)
                    ->where('ppmp_type', $type)
                    ->where('ppmp_status', 'approved')
                    ->first();

            if ($existTransaction) {
                return redirect()->back()->with([
                    'error' => 'Approved PPMP already exist! Transaction No.' . $existTransaction->ppmp_code
                ]);
            }

            
            $groupParticulars = $ppmpTransaction->consolidated->map(function ($items) use ($ppmpTransaction) {
                $prodPrice = (float)$this->productService->getLatestPriceId($items->price_id) * $ppmpTransaction->price_adjustment;
                $prodPrice = $prodPrice != null ? (float) ceil($prodPrice) : 0;
    
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
            $funds = $this->productService->getAllProduct_FundModel();

            foreach ($funds as $fund) {
            if ($fund->categories->isNotEmpty()) {

            $fundFirstTotal = 0; 
            $fundSecondTotal = 0;
            $fundTotal = 0;
                foreach ($fund->categories as $category) {
                    if ($category->items->isNotEmpty()) {
                       
                    $catFirstTotal = 0; 
                    $catSecondTotal = 0;
                    $catTotal = 0;
        
                        foreach ($category->items as $item) {
                            if ($item->products->isNotEmpty()) {  
                                foreach ($item->products as $product) {
                                    $matchedParticulars = $sortedParticulars->filter(function ($particular) use ($product) {
                                        return $particular['prodCode'] === $product->prod_newNo;
                                    });

                                    if ($matchedParticulars->isNotEmpty()) {
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
                            }         
                        }
                        $recapitulation[$fund->fund_name][] =  [
                            'name' => $category->cat_name,
                            'total' => $catTotal,
                            'firstSem' => $catFirstTotal,
                            'secondSem' => $catSecondTotal,
                        ];
                    }
                    
                    $fundFirstTotal += $catFirstTotal; 
                    $fundSecondTotal += $catSecondTotal;
                    $fundTotal += $catTotal;
                }
            }
            
            $capitalOutlay = $this->productService->getCapitalOutlay($ppmpTransaction->ppmp_year, $fund->id);
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

        foreach($recapitulation as $expenses => $fund) {
            $fundId = Fund::where('fund_name', $expenses)->value('id');
            $capitalId = CapitalOutlay::where('year', $year)->where('fund_id', $fundId)->value('id');
            foreach($fund as $category) {
                $this->createFundAllocation($category['name'], '1st',  $category['firstSem'], $capitalId);
                $this->createFundAllocation($category['name'], '2nd',  $category['secondSem'], $capitalId);
            }
        }
        //$ppmpTransaction->update(['ppmp_status' => 'approved', 'updated_by' => $userId]);
        DB::commit();
        return redirect()->route('conso.ppmp.type', ['type' => $type, 'status' => $status])->with('message', 'Proceeding to Approved PPMP successfully executed');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Proceed to Final PPMP error: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Proceeding to final ppmp failed. Please contact your system administrator.');
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

        $groupParticulars = $ppmpTransaction->consolidated->map(function ($items) use (&$totalAmount, $ppmpTransaction) {
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

        $sortedParticulars = $groupParticulars->sortBy('prodCode');

        $ppmpTransaction->transactions = $sortedParticulars->values();
        $ppmpTransaction->totalItems = $sortedParticulars->count();
        $ppmpTransaction->totalAmount = number_format($totalAmount, 2, '.', ',');

        return Inertia::render('Ppmp/Consolidated', ['ppmp' =>  $ppmpTransaction, 'user' => Auth::id(),]);
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
            ->orderBy('created_at', 'desc')
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
                'version' => $transaction->ppmp_version ?? 'N/A',
                'createdAt' => $transaction->created_at->format('Y-m-d H:i:s'),
                'updatedBy' => optional($transaction->updater)->name ?? 'Unknown',
            ];
        });

        $request['status'] = ucfirst($request['status']);
        $request['type'] = ucfirst($request['type']);
        return Inertia::render('Ppmp/DraftConsolidatedList', ['ppmp' => $request, 'transactions' => $transactions, 'individualList' => $result]);
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
            'tresh_first_qty' => $janQty,
            'tresh_second_qty' => $mayQty,
            'prod_id' => $isProductValid['prodId'],
            'price_id' => $isProductValid['priceId'],
            'trans_id' => $ppmpId,
        ]);
    }

    private function validateIndivPpmp(array $validatedData)
    {
        $officePpmpExist = PpmpTransaction::where('ppmp_year', (string) $validatedData['ppmpYear'])
            ->where('office_id', $validatedData['office'])
            ->where('ppmp_type', 'individual')
            ->where('ppmp_status', 'draft')
            ->exists();

        return $officePpmpExist;
    }

    private function validateConsoPpmp(array $validatedData)
    {
        $ppmpExist = PpmpTransaction::where('ppmp_type', 'consolidated')
            ->where('ppmp_year', $validatedData['ppmpYear'])
            ->where('ppmp_status', $validatedData['ppmpStatus'])
            ->orderBy('created_at', 'desc')
            ->first();

        return $ppmpExist;
    }

    private function getIndividualPpmpTransactionsWithParticulars($request) {
        $transactions = PpmpTransaction::with('particulars')
        ->where('ppmp_year', $request['ppmpYear'])
        ->where('ppmp_type', 'individual')
        ->where('ppmp_status', $request['ppmpStatus'])
        ->where('ppmp_version', $request['version'])
        ->get();

        return $transactions;
    }

    private function getConsoVersion(array $validatedData)
    {
        $ppmpExist = PpmpTransaction::where('ppmp_type', 'consolidated')
            ->where('ppmp_year', $validatedData['ppmpYear'])
            ->where('ppmp_status', $validatedData['ppmpStatus'])
            ->orderBy('created_at', 'desc')
            ->first();

        return $ppmpExist->ppmp_version ?? null;
    }
}
