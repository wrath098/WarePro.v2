<?php

namespace App\Http\Controllers;

use App\Models\PpmpConsolidated;
use App\Models\PpmpParticular;
use App\Models\PpmpTransaction;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class PpmpParticularController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'param.transId' => 'required|integer',
            'param.transType' => 'required|string',
            'param.prodCode' => 'required|string|max:20',
            'param.firstQty' => 'required|integer',
            'param.secondQty' => 'nullable|integer',
            'param.user' => 'nullable|integer',
        ], [
            'param.transId.required' => 'Please provide a transaction ID.',
        ]); 

        $param = $validatedData['param'];

        try {
            $productExist = Product::where('prod_newNo', $param['prodCode'])
                ->where('prod_status', 'active')->first();

            if (!$productExist) {
                return back()->with(['error' => 'The Product No. '. $param['prodCode'] . ' does not exist or has been inactive on current product list.']);
            }

            $particularExist = strtolower($param['transType']) == 'individual'
                ? $this->validateProductOnParticular($param['transId'], $productExist->id)
                : $this->validateProductOnConsolidated($param['transId'], $productExist->id);

            if ($particularExist) {
                return back()->with(['error' => 'The Product No. '. $param['prodCode'] . ' already exist on the list.']);
            } else {

                $create = strtolower($param['transType']) == 'individual'
                    ? $this->createProductOnParticular($validatedData, $productExist->id)
                    : $this->createProductOnConsolidated($validatedData, $productExist->id);

                $transId = PpmpTransaction::findOrFail($param['transId']);
                $transId->update(['updated_by' => $param['user']]);

                return redirect()->back()->with('message', 'Product No. '. $param['prodCode'] . ' has been successfully added!');
            }
        } catch (\Exception $e) {
            Log::error("Adding new PPMP particular failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'data' => $validatedData
            ]);

            return back()->with(['error' => 'Adding Product Item failed!']);
        }
    }

    public function update(Request $request)
    {
        $validatedData = $request->validate([
            'partId' => 'required|integer',
            'prodCode' => 'required|string|max:20',
            'prodDesc' => 'nullable|string',
            'firstQty' => 'required|integer|min:1',
            'secondQty' => 'nullable|integer|min:0',
            'user' => 'nullable|integer',
            'transType' => 'required|string',
        ], [
            'partId.required' => 'Please provide a Particular ID.',
            'firstQty.min' => 'First quantity must be at least 1.',
        ]);

        try {

            $particular = strtolower($validatedData['transType']) == 'individual'
                ? PpmpParticular::findOrFail($validatedData['partId'])
                : PpmpConsolidated::findOrFail($validatedData['partId']);

            $particular->update([
                'qty_first' => $validatedData['firstQty'],
                'qty_second' => $validatedData['secondQty']
            ]);

            $transId = PpmpTransaction::findOrFail($particular['trans_id']);
            $transId->update(['updated_by' => $validatedData['user']]);

            return redirect()->back()->with(['message' => 'Product No. '. $validatedData['prodCode'] . ' has been successfully updated!']);

        } catch (\Exception $e) {
            Log::error("Updating PPMP particular failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'data' => $validatedData
            ]);

            return redirect()->back()->with(['error' => 'Updating Product No.'. $validatedData['prodCode'] . ' failed. Please try again!']);
        }
    }

    public function delete(Request $request)
    {
        $validatedData = $request->validate([
            'partId' => 'required|integer',
            'user' => 'nullable|integer',
            'transType' => 'required|string',
        ], [
            'pId.required' => 'Please provide a Particular ID.',
        ]);

        try {

            $particular = strtolower($validatedData['transType']) == 'individual'
                ? PpmpParticular::findOrFail($validatedData['partId'])
                : PpmpConsolidated::findOrFail($validatedData['partId']);

            $transId = PpmpTransaction::findOrFail($particular->trans_id);
            $transId->update(['updated_by' => $validatedData['user']]);

            $particular->forceDelete();

            return redirect()->back()->with(['message' => 'Product has been moved to trashed succecfully!']);
        } catch (\Exception $e) {
            Log::error("Removing PPMP particular failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'data' => $validatedData
            ]);

            return back()->with(['error' => 'Failed to delete the particular. Please try again!']);
        }
    }

    public function getOfficePpmpParticulars(Request $request)
    {
        try {
            if($request['officeId'] == 'others')
            {
                return response()->json(['data' => $this->getProductsAvailable()]);
            }

            $officePpmp = $this->officePpmpWithParticulars($request->officeId, $request->year);
            return response()->json(['data' => $officePpmp]);
        } catch(\Exception $e) {
            Log::error(['Get Office Ppmp Particulars | RIS Module : ', $e->getMessage()]);
            return response()->json(['data' => null]);
        }
    }

    private function officePpmpWithParticulars($officeId, $year)
    {   
        $officePpmp = PpmpTransaction::with('particulars')
            ->where('office_id', $officeId)
            ->where('ppmp_year', $year)
            ->get();

        $availableItems = $officePpmp->flatMap(function ($transactions) {
            return $transactions->particulars->map(function ($particular) {
                $remainingQty = ($particular->tresh_first_qty + $particular->tresh_second_qty) - $particular->released_qty;
 
                return [
                    'id' => $particular->id,
                    'treshFirstQty' => $particular->tresh_first_qty,
                    'treshSecondQty' => $particular->tresh_second_qty,
                    'releasedQty' => $particular->released_qty,
                    'remainingQty' => $remainingQty,
                    'prodId' => $particular->prod_id,
                    'prodInvId' => $this->getProductInventoryId($particular->prod_id),
                    'availableStock' => $this->getProductAvailableStock($particular->prod_id),
                    'prodStockNo' => $this->productService->getProductCode($particular->prod_id),
                    'prodDesc' => $this->productService->getProductName($particular->prod_id),
                    'prodUnit' => $this->productService->getProductUnit($particular->prod_id),
                ];
            });
        });

        $availableItems = $availableItems->sortBy('prodDesc')->values()->all();

        return $availableItems;
    }

    private function getProductsAvailable()
    {
        $query = ProductInventory::select('id', 'qty_on_stock', 'prod_id')
            ->with(['productInfo' => function($q) {
                $q->withTrashed()->select('id', 'prod_newNo', 'prod_desc', 'prod_unit');
            }])->get();

        $availableItems = $query->map(function($particular) {
            $productInfo = $particular->productInfo;
                return [
                    'id' => $particular->id,
                    'treshFirstQty' => 0,
                    'treshSecondQty' => 0,
                    'releasedQty' => 0,
                    'remainingQty' => $particular->qty_on_stock,
                    'prodId' => $particular->prod_id,
                    'prodInvId' => $this->getProductInventoryId($particular->prod_id),
                    'availableStock' => $particular->qty_on_stock,
                    'prodStockNo' => $productInfo->prod_newNo,
                    'prodDesc' => $productInfo->prod_desc,
                    'prodUnit' => $productInfo->prod_unit
                ];
            });

        $availableItems = $availableItems->sortBy('prodDesc')->values()->all();

        return $availableItems;
    }

    private function getProductAvailableStock($productId)
    {
        $productinventory = ProductInventory::where('prod_id', $productId)->first();
        return $productinventory->qty_on_stock ?? 0;
    }

    private function getProductInventoryId($productId)
    {
        $productinventory = ProductInventory::where('prod_id', $productId)->first();
        return $productinventory->id ?? 0;
    }

    private function validateProductOnParticular($transId, $prodId)
    {
        return PpmpParticular::where('trans_id', $transId)->where('prod_id', $prodId)->first();
    }

    private function validateProductOnConsolidated($transId, $prodId)
    {
        return PpmpConsolidated::where('trans_id', $transId)->where('prod_id', $prodId)->first();
    }

    private function createProductOnParticular($validatedData, $prodId)
    {
        $param = $validatedData['param'];

        PpmpParticular::create([
            'qty_first' => $param['firstQty'],
            'qty_second' => $param['secondQty'] ?? 0,
            'prod_id' => $prodId,
            'price_id' => $this->productService->getLatestPriceIdentification($prodId),
            'trans_id' => $param['transId'],
        ]);
    }

    private function createProductOnConsolidated($validatedData, $prodId)
    {
        $param = $validatedData['param'];

        PpmpConsolidated::create([
            'qty_first' => $param['firstQty'],
            'qty_second' => $param['secondQty'] ?? 0,
            'prod_id' => $prodId,
            'price_id' => $this->productService->getLatestPriceIdentification($prodId),
            'created_by' => $param['user'],
            'updated_by' => $param['user'],
            'trans_id' => $param['transId'],
        ]);
    }

}
