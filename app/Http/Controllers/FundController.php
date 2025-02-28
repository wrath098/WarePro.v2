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
        return Inertia::render('Fund/Index', [
            'activeFund' => $activeFunds,
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
            $existingFund = Fund::withTrashed()
                ->whereRaw('LOWER(fund_name) = ?', [strtolower($validation['fundName'])])
                ->first();
                
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
            $fundId->load([
                'categories' => function ($query) {
                    $query->onlyTrashed()->where('cat_status', 'active');
                },
                'categories.items' => function ($query) {
                    $query->onlyTrashed()->where('item_status', 'active');
                },
                'categories.items.products' => function ($query) {
                    $query->onlyTrashed()->where('prod_status', 'active');
                },
            ]);
            $fundId->lockForUpdate();
            $user = Auth::id();

            foreach ($fundId->categories as $category) {
                foreach ($category->items as $item) {
                    $this->restoreProducts($item, $user);
                    $item->update(['updated_by' => $user]);
                    $item->restore();
                }

                $category->update(['updated_by' => $user]);
                $category->restore();
            }

            $fundId->update(['fund_status' => 'active', 'updated_by' => $user]);

            DB::commit();
            return redirect()->back()->with(['message' => 'An account classification activated.']);
        } catch (\Exception $e) {
            
            DB::rollBack();
            Log::error('Restoration of Fund Cluster error for fundId ' . $fundId->fund_name . ': ' . $e->getMessage());
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
            $fund = Fund::with('categories.items.products')->findOrFail($validation['fundId']);
            $fund->lockForUpdate();

            if($fund->categories->isEmpty()) {
                $fund->forceDelete();
                DB::commit();
                return redirect()->back()->with(['message' => 'Account Classification deleted successfully.']);
            }

            foreach ($fund->categories as $category) {
                foreach ($category->items as $item) {
                    $this->deleteProductsForItem($item, $validation['updatedBy']);
                    $this->deleteItem($item, $validation['updatedBy']);
                }
                $this->deleteCategory($category, $validation['updatedBy']);
            }

            $fund->update([
                'fund_status' => 'deactivated',
                'updated_by' => $validation['updatedBy'],
            ]);

            DB::commit();
            return redirect()->back()->with(['message' => 'Account Classification move to trashed.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Deletion of Fund Cluster error for fundId ' . $validation['fundId'] . ': ' . $e->getMessage());
            return redirect()->back()->with(['error' => 'Failed to move the fund cluster to trash.']);
        }
    }

    public function showTrashedFunds()
    {
        $deactivatedFunds = $this->getDeactivatedFund();
        return response()->json(['data' => $deactivatedFunds]);
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
                    'name' => $fund->fund_name,
                    'status' => ucfirst($fund->fund_status),
                    'desc' => $fund->description,
                    'updatedAt' => $fund->updated_at->format('F j, Y'),
                    'nameOfCreator' => $this->getUserName($fund->created_by),
                ];
            });

        return $funds;
    }

    private function deleteProductsForItem($item, $updatedBy)
    {
        $productIds = $item->products->pluck('id');
        $item->products()->whereIn('id', $productIds)->update(['updated_by' => $updatedBy]);
        $item->products()->whereIn('id', $productIds)->delete();
    }

    private function deleteItem($itemClass, $updatedBy)
    {
        $itemClass->update(['updated_by' => $updatedBy]);
        $itemClass->delete();
    }

    private function deleteCategory($category, $updatedBy)
    {
        $category->update(['updated_by' => $updatedBy]);
        $category->delete();
    }

    private function restoreProducts($item, $user)
    {
        foreach ($item->products as $product) {
            $product->update(['updated_by' => $user]);
            $product->restore();
        }
    }
}
