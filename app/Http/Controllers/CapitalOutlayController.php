<?php

namespace App\Http\Controllers;

use App\Models\CapitalOutlay;
use App\Models\Fund;
use App\Models\FundAllocation;
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
        $accountClass = $this->getActiveAaccountClass();
        $availableYears = CapitalOutlay::select('year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->take(5)
            ->pluck('year');

        $latestYear = CapitalOutlay::max('year');
        $generalFund = CapitalOutlay::with('allocations')
            ->where('year', $latestYear)
            ->get();
        $groupedByYear = $generalFund->groupBy('year');

        $groupedByYear = $groupedByYear->map(function($funds) {
            $totalAmount = $funds->sum('amount');
            return [
                'totalAmount' => $totalAmount,
                'funds' => $funds->map(fn($fund) => [
                    'id' => $fund->id,
                    'amount' => $fund->amount,
                    'accountClass' => $this->getAccountClassName($fund->fund_id),
                    'allocations' => $fund->allocations ? $fund->allocations->map(fn($allocation) => [
                        'id' => $allocation->id,
                        'description' => $allocation->description,
                        'semester' => $allocation->semester == '1st' ? '1st Sem' : '2nd Sem',
                        'amount' => $allocation->amount,
                    ]) : [],
                ]),
            ];
        });
        
        return Inertia::render('Fund/GeneralFundIndex', [
            'availableYears' => $availableYears,
            'generalFund' => $groupedByYear, 
            'accountClassification' => $accountClass,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function storeFund(Request $request)
    {
        DB::beginTransaction();

        $validatedData = $request->validate([
            'year' => 'required|integer',
            'fundId' => 'required|integer',
            'amount' => 'required|integer',
        ]);
        
        try {
            $isFound = $this->verifyAccountBudget($validatedData['fundId'], $validatedData['year']);
            
            if($isFound) {
                DB::rollBack();
                return redirect()->back()->with(['error' => 'Designated amount in selected Account Class already exist!']);
            }

            CapitalOutlay::create([
                'year' => $validatedData['year'],
                'cluster' => 'Regular',
                'amount' => $validatedData['amount'],
                'fund_id' => $validatedData['fundId'],
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            DB::commit();
            return redirect()->back()->with(['message' => 'Designated amount in selected Account Class created successfully!']);

        } catch(\Exception $e) {
            
            DB::rollBack();
            Log::error('Error creating fund account budget: '.$e->getMessage());
            return redirect()->back()->with(['error' => 'An error occurred while creating the fund account budget.']);
        }
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

        try {

            $validatedFunds = $request->validate([
                'funds' => 'array',
            ]);

            $fundUpdates = [];
            $allocationUpdates = [];

            foreach ($validatedFunds['funds'] as $fund) {
                $fundId = intval($fund['id']);
                $fundAmount = (float)$fund['amount'];

                $fundUpdates[$fundId] = [
                    'amount' => $fundAmount,
                    'updated_by' => Auth::id(),
                ];

                if (isset($fund['allocations']) && is_array($fund['allocations'])) {
                    foreach ($fund['allocations'] as $allocation) {
                        $allocationId = intval($allocation['id']);
                        $allocationAmount = (float)$allocation['amount'];

                        $allocationUpdates[$allocationId] = [
                            'amount' => $allocationAmount,
                        ];
                    }
                }
            }

            foreach ($fundUpdates as $fundId => $data) {
                CapitalOutlay::where('id', $fundId)->lockForUpdate()->update($data);
            }

            foreach ($allocationUpdates as $allocationId => $data) {
                FundAllocation::where('id', $allocationId)->lockForUpdate()->update($data);
            }
    
            DB::commit();
            return redirect()->route('general.fund.display')->with(['message' => 'Annual Budget updated successfully!']);
        } catch (\Exception $e) {

            DB::rollBack();
            Log::error("Error in updating Annual budget: " . $e->getMessage());
            return redirect()->back()->with(['error' => 'Failed to update fund allocations. Please try again.']);
        }
    }

    public function showFundByYear(Request $request)
    {
        $query = CapitalOutlay::where('year', $request->year)
        ->with('allocations')
        ->get();

        $totalAmount = $query->sum('amount');

        $result = $query->map(function ($fund) use ($totalAmount) {
            $allocationsData = $fund->allocations->map(function ($allotment) {
                return [
                    'id' => $allotment->id,
                    'description' => $allotment->description,
                    'semester' => $allotment->semester == '1st' ? '1st Sem' : '2nd Sem',
                    'amount' => $allotment->amount,
                ];
            });
    
            return [
                'totalAmount' => $totalAmount,
                'funds' => [
                    'id' => $fund->id,
                    'amount' => $fund->amount,
                    'accountClass' => $this->getAccountClassName($fund->fund_id),
                    'allocation' => $allocationsData,
                ],
            ];
        });

        return response()->json(['data' => $result]);
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

    private function getActiveAaccountClass() {
        return Fund::where('fund_status', 'active')
            ->get()
            ->map(fn($class) => [
                'id' => $class->id,
                'account' => $class->fund_name,
            ]);
    }

    private function getAccountClassDetails($id) {
        $accountClass = Fund::findOrFail($id);
        return $accountClass;
    }

    private function verifyAccountBudget(int $fundId, int $year): bool
    {
        return CapitalOutlay::where('year', $year)
                ->where('fund_id', $fundId)
                ->exists();
    }
}
