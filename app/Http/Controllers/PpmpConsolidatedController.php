<?php

namespace App\Http\Controllers;

use App\Models\PpmpConsolidated;
use App\Models\PpmpTransaction;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PpmpConsolidatedController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function store(Request $request) 
    {
        $validatedData = $request->validate([
            'transId' => 'required|integer',
            'prodCode' => 'required|string|max:20',
            'firstQty' => 'required|integer',
            'secondQty' => 'nullable|integer',
            'user' => 'nullable|integer',
        ], [
            'transId.required' => 'Please provide a transaction ID.',
        ]);

        try {

            $productExist = Product::where('prod_newNo', $validatedData['prodCode'])
                ->where('prod_status', 'active')->first();

            if (!$productExist) {
                return back()->withInput()->withErrors([
                    'prodCode' => 'The Product No. '. $validatedData['prodCode'] . ' does not exist or has been inactive on product list!'
                ]);
            }

            $particularExist = PpmpConsolidated::where('trans_id', $validatedData['transId'])
                ->where('prod_id', $productExist->id)->first();

            if ($particularExist) {
                return back()->withInput()->withErrors([
                    'prodCode' => 'The Product No. '. $validatedData['prodCode'] . ' already exist on the list!'
                ]);
            } else {
                PpmpConsolidated::create([
                    'qty_first' => $validatedData['firstQty'],
                    'qty_second' => $validatedData['secondQty'] ? $validatedData['secondQty'] : 0,
                    'prod_id' => $productExist->id,
                    'price_id' => $this->productService->getLatestPriceIdentification($productExist->id),
                    'created_by' => $validatedData['user'],
                    'updated_by' => $validatedData['user'],
                    'trans_id' => $validatedData['transId'],
                ]);

                $transId = PpmpTransaction::findOrFail($validatedData['transId']);
                $transId->update(['updated_by' => $validatedData['user']]);

                return redirect()->back()->with(['message' => 'Product No. '. $validatedData['prodCode'] . ' has been successfully added!']);
            }
        } catch(\Exception $e) {
            Log::error("Adding new product failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'data' => $validatedData
            ]);

            return redirect()->back()->with(['error' => 'Adding new product failed. Please try again!']);
        }
    }

    public function update(Request $request, PpmpConsolidated $ppmpConsolidated)
    {
        try {
            $ppmpConsolidated->update([
                'qty_first' => (int)$request->firstQty, 
                'qty_second' => (int)$request->secondQty,
                'updated_by' => $request->user,
            ]);
            return redirect()->back()->with(['message' => 'Product No. '. $request->prodCode . ' updated successfully!']);
        } catch(\Exception $e) {
            Log::error("Adding new product failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'data' => $request->toArray()
            ]);

            return back()->with(['error' => 'Product No. '. $request->prodCode . ' updating failed!']);
        }
    }

    public function destroy(PpmpConsolidated $ppmpConsolidated)
    {
        $prodCode = $this->productService->getProductCode($ppmpConsolidated->prod_id);
        try {
            $ppmpConsolidated->update([
                'updated_by' => Auth::id(),
            ]);

            $ppmpConsolidated->delete();
            return redirect()->back()->with(['message' => 'Product No. '. $prodCode . ' successfully moved to trash!']);
        } catch(\Exception $e) {
            return redirect()->back()->with(['error' => 'Product No. '. $prodCode . ' failed to move to trash!']);
        }
    }

    public function inlineUpdate(Request $request, $id)
    {
        $particular = PpmpConsolidated::findOrFail($id);

        $field = $request->input('field');
        $value = $request->input('value');

        $fieldMap = [
            'procurement_mode'   => 'procurement_mode',
            'ppc'                => 'ppc',
            'start_pa'           => 'start_pa',
            'end_pa'             => 'end_pa',
            'expected_delivery'  => 'expected_delivery',
            'estimated_budget'   => 'estimated_budget',
            'supporting_doc'     => 'supporting_doc',
            'remarks'           => 'remarks',
        ];

        if (!isset($fieldMap[$field])) {
            return response()->json(['error' => 'Invalid field'], 422);
        }

        $particular->{$fieldMap[$field]} = $value;
        $particular->save();

        return response()->json(['success' => true]);
    }
}
