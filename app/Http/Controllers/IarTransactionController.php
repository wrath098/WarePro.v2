<?php

namespace App\Http\Controllers;

use App\Models\IarParticular;
use App\Models\IarTransaction;
use Illuminate\Http\Request;
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

        $particulars = $iarTransaction->load('iarParticulars');
        $particulars = $particulars->iarParticulars->map(fn($item) => [
            'pId' => $item,
            
        ]);

        return Inertia::render('Iar/Particular', ['iar' => $iarTransaction,'particulars' => $particulars]);
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
}
