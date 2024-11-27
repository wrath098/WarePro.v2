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
}
