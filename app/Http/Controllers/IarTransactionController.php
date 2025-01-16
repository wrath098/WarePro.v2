<?php

namespace App\Http\Controllers;

use App\Models\IarParticular;
use App\Models\IarTransaction;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\ProductInventoryTransaction;
use App\Models\ProductPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class IarTransactionController extends Controller
{
    public function index()
    {
        $lists = IarTransaction::where('status', 'pending')
            ->orderBy('sdi_iar_id', 'desc')
            ->get();

        $lists = $lists->map(fn($iar) => [
            'id' => $iar->id,
            'airId' => $iar->sdi_iar_id,
            'poId' => $iar->po_no,
            'supplier' => $iar->supplier,
            'date' => $iar->date,
            'status' => ucfirst($iar->status),
        ]);

        return Inertia::render('Iar/Index', ['iar' => $lists]);
    }

    public function fetchIarParticular(Request $request)
    {   
        $iarId = $request->input('iar');
        $iarTransaction = IarTransaction::findOrFail($iarId);

        $particulars = $iarTransaction->load(['iarParticulars' => function($query) {
            $query->where('status', 'pending');
        }]);
        $particulars = $particulars->iarParticulars->map(fn($item) => [
            'pId' => $item->id,
            'itemNo' => $item->item_no,
            'stockNo' => $item->stock_no,
            'unit' => ucfirst($item->unit),
            'specs' => $item->description,
            'quantity' => $item->qty,
            'price' => $item->price,
            'status' => $item->status,
            'cost' => number_format(($item->qty * $item->price), 2,'.', ','),
        ]);

        return Inertia::render('Iar/Particular', ['iar' => $iarTransaction,'particulars' => $particulars]);
    }

    public function acceptIarParticular(Request $request)
    {   
        DB::beginTransaction();

        try {
            $particular = IarParticular::findOrFail($request->pid);
            $product = $this->validateProduct($particular->stock_no);
            $userId = Auth::id();

            if(!$product) {
                throw new \Exception('Product Item/Stock No. not found!');
            }

            $data = [
                'type' => 'purchase',
                'qty' => $particular->qty,
                'refNo' => $particular->id,
                'prodId' => $product->id,
                'price' => $product->price,
                'user' => $userId,
            ];

            $this->createInventoryTransaction($data);
            $this->updateProductInventory($data);
            $this->updateProductPrice($data);
            $particular->update(['status' => 'completed', 'updated_by' => $userId]);

            $iarTransaction = IarTransaction::findOrFail($particular->air_id);
            $iarTransaction = $iarTransaction->load(['iarParticulars' => function($query) {
                $query->where('status', 'pending');
            }]);

            if($iarTransaction->iarParticulars->isNotEmpty()) {
                DB::commit();
                return redirect()->back()->with(['message' => 'Item No.' . $particular->item_no . ' was added to the Product Inventory!!']);
            }

            $iarTransaction->update(['status' => 'completed', 'updated_by' => $userId]);
            DB::commit();
            return redirect()->route('iar')->with(['message' => 'Transaction no. ' . $iarTransaction->sdi_iar_id . ' has been removed from the list due to no pending particulars.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error during transaction: " . $e->getMessage());
            return redirect()->back()->with(['error' => $e->getMessage()]);
        }
    }

    public function acceptIarParticularAll(Request $request)
    {
        DB::beginTransaction();

        try {
            foreach ($request->particulars as $particular) {
                $product = $this->validateProduct($particular->stock_no);

                if(!$product) {
                    throw new \Exception('Product Item/Stock No. not found!');
                }

            };
        } catch(\Exception $e) {

        }
    }

    public function updateIarParticular(Request $request)
    {
        DB::beginTransaction();

        try {
            $particular = IarParticular::findOrFail($request->pid);
            $userId = Auth::id();

            $product = $this->validateProduct($request->stockNo);

            if(!$product) {
                throw new \Exception('Product Item not found!');
            }

            $particular->update(['stock_no' => $request->stockNo, 'updated_by' => $userId]);

            DB::commit();
            return redirect()->back()->with(['message' => 'Item No.' . $particular->item_no . ' successfully updated the stock no.!!']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error during transaction: " . $e->getMessage());
            return redirect()->back()->with(['error' => $e->getMessage()]);
        }
    }

    public function rejectIarParticular(Request $request)
    {
        DB::beginTransaction();
        
        try {
            $particular = IarParticular::findOrFail($request->pid);
            $userId = Auth::id();

            $particular->update(['status' => 'failed', 'updated_by' => $userId]);

            $iarTransaction = IarTransaction::findOrFail($particular->air_id);
            $iarTransaction = $iarTransaction->load(['iarParticulars' => function($query) {
                $query->where('status', 'pending');
            }]);
            
            if($iarTransaction->iarParticulars->isNotEmpty()) {
                DB::commit();
                return redirect()->back()->with(['message' => 'Item No.' . $particular->item_no . ' Added to the Product Inventory!!']);
            }

            $iarTransaction->update(['status' => 'completed', 'updated_by' => $userId]);
            DB::commit();
            return redirect()->route('iar')->with(['message' => 'Transaction no. ' . $iarTransaction->sdi_iar_id . ' has been removed from the list due to no pending particulars.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error during transaction: " . $e->getMessage());
            return redirect()->back()->with(['error' => $e->getMessage()]);
        }
    }

    public function collectIarTransactions()
    {
        DB::beginTransaction();
        
        try {
            $wareproList = IarTransaction::orderBy('sdi_iar_id', 'desc')->first();
            $wareproList = $wareproList ? $wareproList->sdi_iar_id : 0;

            $pgsoList = DB::connection('pgso-pms')
                ->table('sdi_air')
                ->select('sdi_air.air_id', 'sdi_air.po_no', 'psu_suppliers.name', 'sdi_air.air_date', 'sdi_air.warehouse')
                ->join('psu_suppliers', 'sdi_air.supplier_id', '=', 'psu_suppliers.supplier_id')
                ->where('sdi_air.air_id', '>' , $wareproList)
                ->where('warehouse', 1)
                ->get();

            foreach ($pgsoList as $iar) {
                if(!$this->verifyExistence($iar->air_id)) {
                    $createIar = IarTransaction::create([
                        'sdi_iar_id' => $iar->air_id,
                        'po_no' => $iar->po_no,
                        'supplier' => $iar->name,
                        'date' => $iar->air_date,
                    ]);
        
                    $particulars = DB::connection('pgso-pms')
                        ->table('sdi_air_particulars')
                        ->select('*')
                        ->where('air_id', $iar->air_id)
                        ->get();
        
                        foreach ($particulars as $particular) {
                            IarParticular::create([
                                'item_no' => $particular->item_no,
                                'stock_no' => $particular->stock_no,
                                'unit' => $particular->unit,
                                'description' => $particular->description,
                                'qty' => $particular->quantity,
                                'price' => $particular->unit_cost,
                                'air_id' => $createIar->id,
                            ]);
                        }
                }
            }
            DB::commit();
            return redirect()->back()->with(['message' => 'Successfully collected the IAR Transactions from the AssetPro!!']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error during transaction: " . $e->getMessage());
            throw $e;
        }
    }

    private function verifyExistence($request) {
        $record = IarTransaction::withTrashed()->where('sdi_iar_id', $request)->exists();
        return $record;
    }

    private function validateProduct($request)
    {
        if (preg_match("/^\d{2}-\d{4}$/", $request)) {
            if (preg_match('/-(\d+)$/', $request, $matches)) {
                if (isset($matches[1])) {
                    $product = Product::where('prod_oldNo', $matches[1])->first();
                    if ($product) {
                        return $product;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            }
        } 
        elseif (preg_match("/^\d{2}-\d{2,3}-\d{2,4}$/", $request)) {
            $product = Product::where('prod_newNo', $request)->first();
            if ($product) {
                return $product;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    private function createInventoryTransaction($request)
    {
        return ProductInventoryTransaction::create([
            'type' => $request['type'],
            'qty' => $request['qty'],
            'ref_no' => $request['refNo'],
            'prod_id' => $request['prodId'],
            'created_by' => $request['user'],
        ]);
    }

    private function updateProductInventory($request)
    {
        $productExist = ProductInventory::where('prod_id', $request['prodId'])->first();

        if($productExist) {
            $productExist->qty_on_stock += $request['qty'];
            $productExist->qty_purchase += $request['qty'];
            $productExist->updated_by += $request['user'];
            $productExist->save();
        } else {
            return ProductInventory::create([
                'qty_on_stock' => $request['qty'],
                'qty_purchase' => $request['qty'],
                'prod_id' => $request['prodId'],
                'updated_by' => $request['user'],
            ]);
        }
    }

    private function updateProductPrice($product) {
        $latestPrice = ProductPrice::where('prod_id', $product['prodId'])->orderBy('created_at', 'desc')->first();

        if($product['price'] != null && $latestPrice->prod_price > $product['price']){
            ProductPrice::create([
                'prod_price' => $product['prodPrice'],
                'prod_id' => $product['prodId'],
            ]);
        }
    }
}
