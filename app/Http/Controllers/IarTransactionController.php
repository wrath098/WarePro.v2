<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class IarTransactionController extends Controller
{
    public function index()
    {
        $lists = DB::connection('pgso-pms')
                ->table('sdi_air')
                ->select('sdi_air.air_id', 'sdi_air.po_no', 'psu_suppliers.name', 'sdi_air.air_date', 'sdi_air.warehouse', 'sdi_air.date_inspected')
                ->join('psu_suppliers', 'sdi_air.supplier_id', '=', 'psu_suppliers.supplier_id')
                ->where('warehouse', 1)
                ->orderBy('date_inspected', 'DESC')
                ->get();

        return Inertia::render('Iar/Index', ['iar' => $lists]);
    }

    public function fetchIarParticular(Request $request)
    {
        $iarId = $request->input('iar');

        $particulars = DB::connection('pgso-pms')
                ->table('sdi_air_particulars')
                ->select('*')
                ->where('air_id', $iarId)
                ->get();
        
        return response()->json(['data' => $particulars]);

        return Inertia::render('Iar/Particular', ['particulars' => $particulars]);
    }
}
