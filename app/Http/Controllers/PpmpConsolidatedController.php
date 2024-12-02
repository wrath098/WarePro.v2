<?php

namespace App\Http\Controllers;

use App\Models\PpmpConsolidated;
use App\Models\PpmpTransaction;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PpmpConsolidatedController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function store(Request $request) {
        try {
            $validatedData = $request->validate([
                'transId' => 'required|integer',
                'prodCode' => 'required|string|max:20',
                'firstQty' => 'required|integer',
                'secondQty' => 'nullable|integer',
                'user' => 'nullable|integer',
            ], [
                'transId.required' => 'Please provide a transaction ID.',
            ]);

            $productExist = Product::where('prod_newNo', $validatedData['prodCode'])
                ->where('prod_status', 'active')->first();
            if (!$productExist) {
                return redirect()->back()->with(['error' => 'The Product No. '. $validatedData['prodCode'] . ' does not exist or has been inactive on product list.']);
            }

            $particularExist = PpmpConsolidated::where('trans_id', $validatedData['transId'])
                ->where('prod_id', $productExist->id)->first();
            if ($particularExist) {
                return redirect()->back()->with(['error' => 'The Product No. '. $validatedData['prodCode'] . ' already exist on the list.']);
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
            return redirect()->back()->with(['error' => 'Product No. '. $request->prodCode . ' updating failed!']);
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
            return redirect()->back()->with(['error' => 'Product No. '. $request->prodCode . ' updating failed!']);
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
}
