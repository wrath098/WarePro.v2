<?php

namespace App\Http\Controllers;

use App\Models\CapitalOutlay;
use App\Models\Fund;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;


class CapitalOutlayController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $generalFund = CapitalOutlay::with('allocations')->get();
        $groupedByYear = $generalFund->groupBy('year');

        $groupedByYear = $groupedByYear->map(function($funds) {
            $totalAmount = $funds->sum('amount');
            return [
                'totalAmount' => $totalAmount,
                'funds' => $funds ?  $funds->map(fn($fund) => [
                    'id' => $fund->id,
                    'amount' => $fund->amount,
                    'accountClass' => $this->getAccountClassName($fund->fund_id),
                    'allocations' => $fund->allocations ? $fund->allocations->map(fn($allocation) => [
                        'id' => $allocation->id,
                        'description' => $allocation->description,
                        'semester' => $allocation->semester == '1st' ? '1st Sem' : '2nd Sem',
                        'amount' => $allocation->amount,
                    ]) : [],
                ]) : [],
            ];
        });
        
        return Inertia::render('Fund/GeneralFundIndex', ['generalFund' => $groupedByYear]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(CapitalOutlay $capitalOutlay)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function editFundAllocation(Request $request)
    {
        $validatedData = $request->validate([
            'budgetDetails' => 'required|array',
            'year' => 'required',
        ]);

        return Inertia::render('Fund/EditGeneralFund', [
            'budgetDetails' => $validatedData['budgetDetails'],
            'year' => $validatedData['year'],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateFundAllocation(Request $request)
    {
        DB::beginTransaction();
        dd($request->all());
        try {
            $validateData = $request->all();

            if(!$request->all()) {
                dd('yes');
            }
            dd('no');
        } catch(\Exception $e) {
            DB::rollBack();
            Log::error("Error during transaction: " . $e->getMessage());
            return redirect()->back()->with(['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CapitalOutlay $capitalOutlay)
    {
        //
    }

    private function getAccountClassName($id) {
        $accountClass = Fund::findOrFail($id);
        $name = $accountClass ? $accountClass->fund_name : '';
        return $name;
    }
}
