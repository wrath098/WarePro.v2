<?php

namespace App\Http\Controllers;

use App\Models\ProductInventory;
use App\Models\ProductInventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductInventoryTransactionController extends Controller
{
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            if($request->pid) {
                $productInventory = ProductInventory::where('id', $request->pid)->lockForUpdate()->first();
                $productInventory->qty_on_stock += $request->qty;
                $productInventory->updated_by = Auth::id();
                $productInventory->save();
            } else {
                $createProductInventory = ProductInventory::create([
                    'qty_on_stock' => $request->qty,
                    'prod_id' => $request->prodId,
                    'updated_by' => Auth::id(),
                ]);
            }

            ProductInventoryTransaction::create([
                'type' => $request->type,
                'qty' => $request->qty,
                'notes' => $request->remarks,
                'prod_id' => $request->prodId,
                'current_stock' => $request->qty,
                'created_by' => Auth::id(),
            ]);
            
            DB::commit();
            return redirect()->back()->with(['message' => 'Successfully added the quantity to Product code ' . $request->stockNo]);
        } catch(\Exception $e) {
            DB::rollBack();
            Log::error("Error during transaction: " . $e->getMessage());
            return redirect()->back()->with(['error' => $e->getMessage()]);
        }
    }
}
