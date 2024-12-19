<?php

namespace App\Http\Controllers;

use App\Models\Fund;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class FundController extends Controller
{
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

    public function store(Request $request)
    {
        DB::beginTransaction();
        $validation = $request->validate([
            'fundName' => 'required|string|max:255',
            'fundDesc' => 'nullable|string|max:255',
            'createdBy' => 'nullable|integer',
        ]);
        
        try {
            $existingFund = Fund::withTrashed()->whereRaw('LOWER(fund_name) = ?', [strtolower($validation['fundName'])])->first();
            if ($existingFund) {
                DB::rollBack();
                return redirect()->back()->with(['error' => 'Fund Cluster Name is already exist.']);
            }

            Fund::create([
                'fund_name' => $validation['fundName'],
                'description' => $validation['fundDesc'],
                'created_by' => $validation['createdBy'],
            ]);

            DB::commit();
            return redirect()->back()->with(['message' => 'Fund Cluster created successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Creation of Fund Cluster error: ' . $e->getMessage());
            return redirect()->back()->with(['error' => 'Failed to create Fund Cluster']);
        }
    }

    public function update(Request $request, Fund $fund)
    {
        DB::beginTransaction();

        $validation = $request->validate([
            'fundId' => 'required|integer',
            'updatedBy' => 'required|integer',
            'fundName' => 'required|string|max:255',
            'fundDesc' => 'nullable|string|max:255',
        ]);

        try {
            $fund = Fund::findOrFail($validation['fundId']);
            $fund->lockForUpdate();

            $fund->fill([
                'fund_name' => $validation['fundName'],
                'description' => $validation['fundDesc'],
                'updated_by' => $validation['updatedBy'],
            ])->save();

            DB::commit();
            return redirect()->back()->with(['message' => 'Fund Cluster updated successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Updating of Fund Cluster error: ' . $e->getMessage());
            return redirect()->back()->with(['error' => 'Updating of Fund Cluster failed.']);
        }
    }

    public function deactivate(Request $request, Fund $fund)
    {
        DB::beginTransaction();

        $validation = $request->validate([
            'fundId' => 'required|integer',
            'updatedBy' => 'required|integer',
        ]);

        try {
            $fund = Fund::findOrFail($validation['fundId']);

            $fund->fill([
                'fund_status' => 'deactivated',
                'updated_by' => $validation['updatedBy'],
            ])->save();
            
            DB::commit();
            return redirect()->back()->with(['message' => 'Fund Cluster deactivated successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Deletion of Fund Cluster error: ' . $e->getMessage());
            return redirect()->back()->with(['error' => 'Failed to move the fund cluster to trash.']);
        }
    }
}
