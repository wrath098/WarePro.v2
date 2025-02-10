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
        $activeFunds = $this->getActiveFund();
        $deactivatedFunds = $this->getDeactivatedFund();

        return Inertia::render('Fund/Index', [
            'activeFund' => $activeFunds, 
            'inActiveFund' => $deactivatedFunds, 
            'authUserId' => Auth::id()
        ]);
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
                return redirect()->back()->with(['error' => 'Account Classification Name is already exist.']);
            }

            Fund::create([
                'fund_name' => $validation['fundName'],
                'description' => $validation['fundDesc'],
                'created_by' => $validation['createdBy'],
                'updated_by' => $validation['createdBy'],
            ]);

            DB::commit();
            return redirect()->back()->with(['message' => 'Account Classification created successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Creation of Account Classification error: ' . $e->getMessage());
            return redirect()->back()->with(['error' => 'Failed to create Account Classification']);
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
            return redirect()->back()->with(['message' => 'Account Classification updated successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Updating of Account Classification error: ' . $e->getMessage());
            return redirect()->back()->with(['error' => 'Updating failed! Account Classification Name is already exist.']);
        }
    }
    
    public function restore(Fund $fundId)
    {
        DB::beginTransaction();

        try {
            $fundId->load('categories.items.products');
            $fundId->lockForUpdate();
            $user = Auth::id();

            foreach ($fundId->categories as $category) {
                foreach ($category->items as $item) {
                    $productIds = $item->products->pluck('id');
            
                    $item->products()->whereIn('id', $productIds)->update([
                        'prod_status' => 'active',
                        'updated_by' => $user,
                    ]);

                    $item->update([
                        'item_status' => 'active',
                        'updated_by' => $user,
                    ]);
                }
                $category->update([
                    'cat_status' => 'active',
                    'updated_by' => $user,
                ]);
            }

            $fundId->update([
                'fund_status' => 'active',
                'updated_by' => $user,
            ]);

            DB::commit();
            return redirect()->back()->with(['message' => 'An account classification activated.']);
        } catch (\Exception $e) {
            
            DB::rollBack();
            return redirect()->back()->with(['error' => $e->getMessage()]);
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
            $fund->load('categories.items.products');
            $fund->lockForUpdate();

            if($fund->categories->isEmpty()) {
                $fund->forceDelete();
                DB::commit();
                return redirect()->back()->with(['message' => 'Account Classification removed successfully.']);
            }

            foreach ($fund->categories as $category) {
                foreach ($category->items as $item) {
                    $productIds = $item->products->pluck('id');
            
                    $item->products()->whereIn('id', $productIds)->update([
                        'prod_status' => 'deactivated',
                        'updated_by' => $validation['updatedBy'],
                    ]);

                    $item->update([
                        'item_status' => 'deactivated',
                        'updated_by' => $validation['updatedBy'],
                    ]);
                }
                $category->update([
                    'cat_status' => 'deactivated',
                    'updated_by' => $validation['updatedBy'],
                ]);
            }

            $fund->update([
                'fund_status' => 'deactivated',
                'updated_by' => $validation['updatedBy'],
            ]);

            DB::commit();
            return redirect()->back()->with(['message' => 'Account Classification deactivated.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Deletion of Fund Cluster error: ' . $e->getMessage());
            return redirect()->back()->with(['error' => 'Failed to move the fund cluster to trash.']);
        }
    }

    private function getUserName($userId)
    {
        $creator = User::findOrFail($userId);
        $name = $creator ? $creator->name : '';
        return $name;
    }

    private function getActiveFund()
    {
        $funds = Fund::where('fund_status', 'active')
            ->orderBy('fund_name')
            ->get()
            ->map(function($fund) {
                return [
                    'id' => $fund->id,
                    'fund_name' => $fund->fund_name,
                    'fund_status' => ucfirst($fund->fund_status),
                    'description' => $fund->description,
                    'created_by' => $fund->created_by,
                    'nameOfCreator' => $this->getUserName($fund->created_by),
                ];
            });

        return $funds;
    }

    private function getDeactivatedFund()
    {
        $funds = Fund::where('fund_status', 'deactivated')
            ->orderBy('fund_name')
            ->get()
            ->map(function($fund) {
                return [
                    'id' => $fund->id,
                    'fund_name' => $fund->fund_name,
                    'fund_status' => ucfirst($fund->fund_status),
                    'description' => $fund->description,
                    'updated_at' => $fund->updated_at->format('F j, Y'),
                    'nameOfCreator' => $this->getUserName($fund->created_by),
                ];
            });

        return $funds;
    }
}
