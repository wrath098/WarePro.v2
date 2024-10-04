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
    /**
     * Display a listing of the resource.
     */
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

        return Inertia::render('Ppmp/Import', ['officePpmps' =>  $officePpmpExist, 'filters' => $request->only(['search']), 'offices' => $office]);
    }

    /**
     * Store a newly created resource in storage.
     */
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
                return response()->json(['error' => 'Office PPMP already exists!'], 400);
            }

            $createPpmp = $this->createPpmpTransaction($validatedData);

            DB::transaction(function () use ($createPpmp, $request) {
                if ($request->hasFile('file')) {
                    $filePath = $this->handleFileUpload($request->file('file'));
    
                    (new FastExcel)->import($filePath, function ($line) use ($createPpmp) {
                        return $this->processImportedLine($line, $createPpmp->id);
                    });
    
                    Storage::delete($filePath);
                }
            });

            return response()->json(['message' => 'PPMP creation was successful! You can now check the list to add products.']);
        } catch (\Exception $e) {
            Log::error('File create ppmp error: ' . $e->getMessage());
            return response()->json(['message' => 'PPMP creation was failed. Please contact your system administrator'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PpmpTransaction $ppmpTransaction)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PpmpTransaction $ppmpTransaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PpmpTransaction $ppmpTransaction)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PpmpTransaction $ppmpTransaction)
    {
        //
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
        $janQty = $line['Jan'] ?? 0;
        $mayQty = $line['May'] ?? 0;
        $totalQuantity = intval($janQty) + intval($mayQty);

        # New Stock Pattern Comparison
        # !preg_match("/^\d{2}-\d{2}-\d{2,4}$/", $newStock) 
        if (!preg_match("/^\d{4}$/", $code) || $totalQuantity === 0) {
            return null;
        }

        $id = $newStock ?? $code;
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

    private function validatePpmp(array $validatedData)
    {
        $officePpmpExist = PpmpTransaction::where('ppmp_type', $validatedData['ppmpType'])
            ->where('office_id', $validatedData['office'])
            ->where('ppmp_status', $validatedData['ppmpStatus'])
            ->where('ppmp_remarks', (string) $validatedData['ppmpYear'])
            ->exists();

        return $officePpmpExist;
    }
}
