<?php

namespace App\Http\Controllers;

use App\Models\Fund;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class FundController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $funds = Fund::where('fund_status', 'active')
                    ->orderBy('fund_name')
                    ->get();
        foreach ($funds as $list) {
            $creator = User::findOrFail($list->created_by);
            $list['nameOfCreator'] = $creator->name;
        }
        return Inertia::render('Fund/Index', ['fundCluster' => $funds, 'authUserId' => Auth::id()]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validation = $request->validate([
            'fundName' => 'required|string|max:255',
            'fundDesc' => 'nullable|string|max:255',
            'createdBy' => 'nullable|integer',
        ]);
        
        try {
            DB::transaction(function () use ($validation) {
                Fund::create([
                    'fund_name' => $validation['fundName'],
                    'description' => $validation['fundDesc'],
                    'created_by' => $validation['createdBy'],
                ]);
            });
        
            return redirect()->route('fund.display.all')->with(['message' => 'Fund Cluster created successfully']);
        } catch (\Exception $e) {
            return redirect()->route('fund.display.all')->with(['error' => 'Failed to create Fund Cluster']);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Fund $fund)
    {
        $validation = $request->validate([
            'fundId' => 'required|integer',
            'updatedBy' => 'required|integer',
            'fundName' => 'required|string|max:255',
            'fundDesc' => 'nullable|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($validation, $fund) {
                $fund = Fund::findOrFail($validation['fundId']);

                $fund->fill([
                    'fund_name' => $validation['fundName'],
                    'description' => $validation['fundName'],
                    'updated_by' => $validation['updatedBy'],
                ])->save();
            });
            return redirect()->route('fund.display.all')->with(['message' => 'Fund Cluster updated successfully.']);
        } catch (\Exception $e) {
            return redirect()->route('fund.display.all')->with(['error' => 'Failed to update the fund cluster.']);
        }
    }

    /**
     * Update the status specified resource in storage.
     */
    public function deactivate(Request $request, Fund $fund)
    {
        $validation = $request->validate([
            'fundId' => 'required|integer',
            'updatedBy' => 'required|integer',
        ]);

        try {
            DB::transaction(function () use ($validation, $fund) {
                $fund = Fund::findOrFail($validation['fundId']);

                $fund->fill([
                    'fund_status' => 'deactivated',
                    'updated_by' => $validation['updatedBy'],
                ])->save();
            });
            return redirect()->route('fund.display.all')->with(['message' => 'Fund Cluster deactivated successfully.']);
        } catch (\Exception $e) {
            return redirect()->route('fund.display.all')->with(['error' => 'Failed to deactivate the fund cluster.']);
        }
    }
}
