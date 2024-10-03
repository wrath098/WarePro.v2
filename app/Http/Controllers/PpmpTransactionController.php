<?php

namespace App\Http\Controllers;

use App\Models\Office;
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
    public function index(): Response
    {   
        $office = Office::where('office_status', 'active')->get();
        return Inertia::render('Ppmp/Import', ['offices' => $office]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'ppmpType' => 'required|string',
            'basePrice' => 'required|numeric',
            'ppmpYear' => 'required|integer',
            'office' => 'required|integer',
            'file' => 'required|file|mimes:xls,xlsx',
        ]);

        try {
            $startRow = 1;
            $currentRow = 0;
            $productInExcel = [];

            DB::transaction(function () use (&$currentRow, $startRow, &$productInExcel, $validatedData, $request) {
                if (!$request->hasFile('file')) {
                    return null;
                }

                $fileInfo = $request->file('file');
                $filePath = $fileInfo->storeAs('uploads', $fileInfo->getClientOriginalName());
                $fullPath = storage_path('app/' . $filePath);

                (new FastExcel)->import($fullPath, 
                    function ($line) use (&$currentRow, $startRow, $validatedData, &$productInExcel){
    
                        if ($currentRow < $startRow) {
                            $currentRow++;
                            return null;
                        }
    
                        $newStock = $line['New_Stock_No'] ?? null;
                        $code = $line['Old_Sotck_No'] ?? null;
                        $janQty = $line['Jan'] ?? null;
                        $mayQty = $line['May'] ?? null;
                        $totalQuantity = intval($janQty ) + intval($mayQty);
    
                        # New Stock Pattern Comparison
                        # !preg_match("/^\d{2}-\d{2}-\d{2,4}$/", $newStock) 
                        if (!preg_match("/^\d{4}$/", $code) || $totalQuantity === 0) {
                            return null;
                        }
    
                        $id = $newStock != null ? $newStock : $code;
                        $onlist = $this->productService->validateProduct($id);
                        if(!$onlist) {
                            return null;
                        }
    
                        $productInExcel[] = [
                            'ppmpCode' => date('YmdHis'),
                            'ppmpType' => $validatedData['ppmpType'],
                            'priceAdjustment' => ($validatedData['basePrice'] / 100) + 1,
                            'remarks'=> $validatedData['ppmpYear'],
                            'office'=> $validatedData['office'],
                            'janQty' => $janQty,
                            'mayQty' => $mayQty,
                            'prodId' => $onlist['prodId'],
                            'priceId' => $onlist['priceId'],
                        ];

                        $currentRow++;
                })->chunk(250);

                if (Storage::exists($filePath)) {
                    Storage::delete($filePath); 
                }
            });
            return response()->json($productInExcel);
        } catch (\Exception $e) {
            Log::error('File upload error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to process the file: ' . $e->getMessage()], 500);
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
}
