<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\PpmpParticular;
use App\Models\PpmpTransaction;
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

    public function index(Request $request): Response
    {   
        $currentYear = date('Y');
        $search = $request->input('search');

        $officePpmpExist = PpmpTransaction::query()
            ->with('requestee')
            ->where(function($query) use ($currentYear) {
                $query->where(function($query) {
                    $query->where('ppmp_type', 'individual')
                        ->orWhere('ppmp_type', 'contingency');
                })
                ->where('ppmp_status', 'draft')
                ->whereYear('created_at', $currentYear);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('ppmp_code', 'like', '%' . $search . '%')
                    ->orWhereHas('requestee', function ($q) use ($search) {
                        $q->where('office_code', 'like', '%' . $search . '%');
                    });
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->through(fn($ppmp) => [
                'id' => $ppmp->id,
                'ppmpCode' => $ppmp->ppmp_code,
                'ppmpType' => ucfirst($ppmp->ppmp_type),
                'basedPrice' => $ppmp->price_adjustment,
                'officeId' => $ppmp->office_id,
                'officeCode' => $ppmp->requestee->office_code,
            ]);

        $office = Office::where('office_status', 'active')->get();

        return Inertia::render('Ppmp/Import', [
            'officePpmps' =>  $officePpmpExist, 
            'filters' => $request->only(['search']), 
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
        $validatedData['basePrice'] = 1;
        $validatedData['qtyAdjust'] = 1;
        $validatedData['ppmpStatus'] = 'draft';

        try {

            if ($this->validateIndivPpmp($validatedData)) {
                return redirect()->back()->with(['error' => 'Office PPMP already exists!']);
            }

            $createPpmp = $this->createPpmpTransaction($validatedData);

            DB::transaction(function () use ($createPpmp, $request) {
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
            return redirect()->back()
                ->with(['message' => 'PPMP creation was successful! You can now check the list to add products.']);
        } catch (\Exception $e) {
            Log::error('File create ppmp error: ' . $e->getMessage());
            return redirect()->back()
                ->with(['error' => 'PPMP creation was failed. Please contact your system administrator.']);
        }
    }

    public function storeConsolidated(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'ppmpYear' => 'required|string',
                'basePrice' => 'required|integer|min:100|max:120',
                'qtyAdjust' => 'required|integer|min:50|max:100',
                'ppmpVersion' => 'required|integer',
                'ppmpType' => 'required|string',
                'ppmpStatus' => 'required|string',
            ]);

            $validatedData['basePrice'] = $validatedData['basePrice'] / 100;
            $validatedData['qtyAdjust'] = $validatedData['qtyAdjust'] / 100;
            $validatedData['office'] = null;
            $validatedData['user'] = Auth::id();

            $ppmpExist = $this->validateConsoPpmp($validatedData);

            if($ppmpExist) {
                return response()->json(['error' => 'Generation of consolidated data failed. A consolidation data already exist with transaction No. ' . $ppmpExist->ppmp_code]);
            }

            $validatedData['ppmpType'] = 'consolidated';
            $createPpmp = $this->createPpmpTransaction($validatedData);

            return response()->json(['message' => 'Consolidated PPMP created successfully.', 'data' => $createPpmp], 201);

        } catch(\Exception $e) {
            Log::error('Error creating consolidated PPMP: ' . $e->getMessage(), [
                'request' => $request->all(),
                'user_id' => Auth::id(),
            ]);
            return redirect()->back()->with([
                'error' => 'Consolidated PPMP creation failed. Please contact your system administrator.'
            ]);
        }
    }

    public function showIndividualPpmp(PpmpTransaction $ppmpTransaction)
    {
        $ppmpTransaction->load('particulars', 'requestee');
        
        $ppmpParticulars = $ppmpTransaction->particulars->map(fn($particular) => [
            'id' => $particular->id,
            'firstQty' => $particular->qty_first,
            'secondQty' => $particular->qty_second,
            'prodCode' => $this->productService->getProductCode($particular->prod_id),
            'prodName' => $this->productService->getProductName($particular->prod_id),
            'prodUnit' => $this->productService->getProductUnit($particular->prod_id),
            'prodPrice' => number_format(round($this->productService->getLatestPriceId($particular->price_id) * $ppmpTransaction->price_adjustment), 2, '.', ','),
        ])->sortBy('prodCode');

        $ppmpTransaction['totalItems'] = $ppmpParticulars->count();
        $overallPrice = $ppmpParticulars->sum(fn($particular) => (($particular['firstQty'] + $particular['secondQty']) * (int) $particular['prodPrice']));

        $ppmpTransaction['formattedOverallPrice'] = number_format(round($overallPrice, 2), 2, '.', ',');

        return Inertia::render('Ppmp/Individual', ['ppmp' =>  $ppmpTransaction, 'ppmpParticulars' => $ppmpParticulars, 'user' => Auth::id(),]);
    }

    public function showIndividualPpmp_Type(Request $request): Response
    {
        $ppmpTransactions = PpmpTransaction::with('requestee', 'updater')
            ->where('ppmp_type', $request->type)
            ->where('ppmp_status', $request->status)
            ->orderBy('created_at', 'desc')
            ->get();

        $request['status'] = ucfirst($request['status']);
        $request['type'] = ucfirst($request['type']);

        return Inertia::render('Ppmp/PpmpList', ['ppmpTransaction' =>  $ppmpTransactions, 'ppmp' =>  $request]);
    }

    public function showConsolidatedPpmp_Type(Request $request): Response
    {
        $years = PpmpTransaction::select('ppmp_year')
            ->where('ppmp_type', 'individual')
            ->where('ppmp_status', 'draft')
            ->groupBy('ppmp_year')
            ->get();

        foreach($years as $year) {
            $versions = PpmpTransaction::select('ppmp_version')
                ->where('ppmp_year', $year->ppmp_year)
                ->where('ppmp_type', 'individual')
                ->where('ppmp_status', 'draft')
                ->groupBy('ppmp_version')
                ->get();

                $year->versions = $versions; 
        }
        
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
                    'priceAdjust' => (float) $transaction->price_adjustment ?? 0,
                    'qtyAdjust' => (float) $transaction->qty_adjustment ?? 0,
                    'version' => $transaction->ppmp_version ?? 'N/A',
                    'updatedBy' => optional($transaction->updater)->name ?? 'Unknown',
                ];
            });

        return Inertia::render('Ppmp/ConsolidatedPpmpList', ['transactions' => $transactions, 'years' => $years]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        try {
            $ppmpTransaction = PpmpTransaction::with('particulars')->where('id', $request->input('ppmpId'))->first();
            if ($ppmpTransaction) {
                $ppmpTransaction->delete();
                return redirect()->back()
                    ->with(['message' => 'PPMP deletion was successful.']);
            } else {
                return redirect()->back()
                    ->with(['error' => 'PPMP not found.']);
            }
        } catch (\Exception $e) {
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
            'price_adjustment' => $validatedData['basePrice'],
            'qty_adjustment' => $validatedData['qtyAdjust'],
            'ppmp_year'=> $validatedData['ppmpYear'],
            'office_id'=> $validatedData['office'],
            'created_by'=> $validatedData['user'],
            'updated_by'=> $validatedData['user'],
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
            'prod_id' => $isProductValid['prodId'],
            'price_id' => $isProductValid['priceId'],
            'trans_id' => $ppmpId,
        ]);
    }

    private function validateIndivPpmp(array $validatedData)
    {
        $officePpmpExist = PpmpTransaction::where('ppmp_type', 'individual')
            ->where('office_id', $validatedData['office'])
            ->where('ppmp_status', $validatedData['ppmpStatus'])
            ->where('ppmp_year', (string) $validatedData['ppmpYear'])
            ->exists();

        return $officePpmpExist;
    }

    private function validateConsoPpmp(array $validatedData)
    {
        $ppmpExist = PpmpTransaction::where('ppmp_type', 'consolidated')
            ->where('ppmp_year', $validatedData['ppmpYear'])
            ->where('ppmp_status', $validatedData['ppmpStatus'])
            ->where('price_adjustment', $validatedData['basePrice'])
            ->where('qty_adjustment', $validatedData['qtyAdjust'])
            ->where('ppmp_status', $validatedData['ppmpStatus'])
            ->first();

        return $ppmpExist;
    }
}
