<?php

namespace App\Http\Controllers;

use App\Models\PpmpParticular;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
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
        try {
            $validatedData = $request->validate([
                'transId' => 'required|integer',
                'prodCode' => 'required|string|max:20',
                'firstQty' => 'required|integer|min:1',
                'secondQty' => 'nullable|integer|min:0',
            ], [
                'transId.required' => 'Please provide a transaction ID.',
                'firstQty.min' => 'First quantity must be at least 1.',
            ]);

            $productExist = Product::where('prod_newNo', $validatedData['prodCode'])
                ->where('prod_status', 'active')->first();
            if (!$productExist) {
                return redirect()->back()->with(['error' => 'The Product No. '. $validatedData['prodCode'] . ' does not exist or has been inactive on product list.']);
            }

            $particularExist = PpmpParticular::where('trans_indiv', $validatedData['transId'])
                ->where('prod_id', $productExist->id)->first();
            if ($particularExist) {
                return redirect()->back()->with(['error' => 'The Product No. '. $validatedData['prodCode'] . ' already exist on the list.']);
            } else {
                PpmpParticular::create([
                    'qty_first' => $validatedData['firstQty'],
                    'qty_second' => $validatedData['secondQty'] ? $validatedData['secondQty'] : 0,
                    'prod_id' => $productExist->id,
                    'price_id' => $this->productService->getLatestPriceIdentification($productExist->id),
                    'trans_indiv' => $validatedData['transId'],
                ]);

                return redirect()->back()->with(['message' => 'Product No. '. $validatedData['prodCode'] . ' has been successfully added!']);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return redirect()->back()->with(['error' => 'Adding Product Item failed!']);
        }
    }

    public function update(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'partId' => 'required|integer',
                'prodCode' => 'required|string|max:20',
                'prodDesc' => 'nullable|string',
                'firstQty' => 'required|integer|min:1',
                'secondQty' => 'nullable|integer|min:0',
            ], [
                'partId.required' => 'Please provide a Particular ID.',
                'firstQty.min' => 'First quantity must be at least 1.',
            ]);

            $particularExist = PpmpParticular::findOrFail($validatedData['partId']);
            $particularExist->update([
                'qty_first' => $validatedData['firstQty'],
                'qty_second' => $validatedData['secondQty']
            ]);

            return redirect()->back()->with(['message' => 'Product No. '. $validatedData['prodCode'] . ' has been successfully updated!']);

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return redirect()->back()->with(['error' => $e->getMessage()]);
        }
    }

    public function delete(PpmpParticular $ppmpParticular)
    {
        try {
            $ppmpParticular->delete();
            return redirect()->back()->with(['message' => 'Product has been successfully deleted!']);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return redirect()->back()->with(['error' => 'Failed to delete the particular.']);
        }
    }
}
