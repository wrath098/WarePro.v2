<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\PpmpParticular;
use App\Models\PpmpTransaction;
use App\Services\ProductService;
use Illuminate\Http\Request;
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
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'ppmpType' => 'required|string',
            'basePrice' => 'nullable|numeric',
            'ppmpYear' => 'required|integer',
            'office' => 'required|integer',
            'file' => 'nullable|file|mimes:xls,xlsx',
        ]);
        $validatedData['ppmpStatus'] = 'draft';

        try {

            if ($this->validatePpmp($validatedData)) {
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

        return Inertia::render('Ppmp/Individual', ['ppmp' =>  $ppmpTransaction, 'ppmpParticulars' => $ppmpParticulars]);
    }

    public function showIndividualPpmp_Draft(Request $request): Response
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
            'price_adjustment' => $validatedData['basePrice'] ? ($validatedData['basePrice'] / 100) + 1 : 1,
            'ppmp_remarks'=> $validatedData['ppmpYear'],
            'office_id'=> $validatedData['office'],
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
            'trans_indiv' => $ppmpId,
        ]);
    }

    private function validatePpmp(array $validatedData)
    {
        $officePpmpExist = PpmpTransaction::where('ppmp_type', 'individual')
            ->where('office_id', $validatedData['office'])
            ->where('ppmp_status', $validatedData['ppmpStatus'])
            ->where('ppmp_remarks', (string) $validatedData['ppmpYear'])
            ->exists();

        return $officePpmpExist;
    }
}
