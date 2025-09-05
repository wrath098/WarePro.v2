<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Fund;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(Fund $fund): Response
    {   
        $fund->load('categories');

        $formattedFunds = [
            'id' => $fund->id,
            'name' => $fund->fund_name
        ];
                    
        $categories = $fund->categories->map(function ($category) {
            return [
                'id' => $category->id,
                'code' => $category->cat_code,
                'name' => $category->cat_name,
                'status' => ucfirst($category->cat_status),
                'fundId' => $category->funder->id ?? '',
                'fundName' => $category->funder->fund_name ?? '',
                'creatorName' => $category->creator->name,
            ];
        })->filter(function ($category) {
            return $category['status'] !== 'Deactivated';
        })
        ->values();

        return Inertia::render('Category/Index', [
            'activeCategories' => $categories,
            'funds' => $formattedFunds, 
            'authUserId' => Auth::id()
        ]); 
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'fundId' => 'required|integer',
            'catName' => 'required|string',
            'createdBy' => 'required|integer',
        ]);        
        
        DB::beginTransaction();

        try {
            $lastCode = Category::selectRaw('cat_code')->orderBy('cat_code', 'desc')->first();
            $newCode = (int) $lastCode->cat_code + 1;

            $existingCategory  = $this->productService->validateCategoryExistence($validatedData['fundId'], $validatedData['catName']);
            
            if($existingCategory) {
                DB::rollBack();
                return back()->withInput()->withErrors([
                    'catName' => 'Category Name under the selected account class is already exist!'
                ]);
            }

            Category::create([
                'fund_id' => $validatedData['fundId'],
                'cat_name' => $validatedData['catName'],
                'cat_code' => $newCode,
                'created_by' => $validatedData['createdBy'],
            ]);

            Db::commit();
            return redirect()->back()->with(['message' => 'New Category was created successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Creating Category Failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'data' => $validatedData,
            ]);

            return back()->with('error', 'Creating New Category Failed. Please try again!')->withInput();
        }
    }

    public function update(Request $request)
    {   
        $validated = $request->validate([
            'catId' => 'required',
            'fundId' => 'required|integer',
            'catCode' => 'required|integer',
            'catName' => 'required|string|max:255',
            'updater' => 'required|integer',
        ]);

        $catId = $validated['catId'];
        $fundId = $validated['fundId'];
        $catCode = $validated['catCode'];
        $catName = $validated['catName'];
        $updater = $validated['updater'];

        try {
            DB::transaction(function () use ($catId, $fundId, $catName, $updater) {
                $category = Category::findOrFail($catId);
                $existingCategory  = $this->productService->validateCategoryExistence($fundId, $catName);

                if($existingCategory && $category->id !== $existingCategory->id) {
                    return back()->withInput()->withErrors([
                        'catName' => 'Category Name under the selected account class is already exist!'
                    ]);
                }
;
                $category->update(['cat_name' => $catName, 'updated_by' => $updater]);
                return redirect()->back()
                    ->with(['message' => 'Category was updated successfully.']);
            });
        } catch (\Exception $e) {
            Log::error("Updating Category Failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Updating Category Information Failed. Please try again!')->withInput();
        }
    }

    public function restore(Category $catId)
    {
        DB::beginTransaction();

        try {
            $catId->load([
                'items' => function($query) {
                    $query->onlyTrashed()->where('item_status', 'active');
                },
                'items.products' => function($query) {
                    $query->onlyTrashed()->where('prod_status', 'active');
                },
                'funder'
            ]);
            $catId->lockForUpdate();
            $user = Auth::id();

            if($catId->funder->fund_status != 'active') {
                DB::rollBack();
                return redirect()->back()
                ->with('error', 'Unable to restore the category, main account classification is inactive!');
            }

            foreach ($catId->items as $item) {
                $this->restoreProductsForItem($item, $user);
                $this->restoreItem($item, $user);
            }

            $catId->update([
                'cat_status' => 'active',
                'updated_by' => $user,
            ]);

            DB::commit();
            return redirect()->back()->with('message', 'Category was activated.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Restoring Category Failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error','Restoring Category Failed. Please try again!');
        }       
    }

    public function showTrashedCategories(Request $request)
    {
        $categories = Category::with(['funder', 'updater'])
            ->where('fund_id', $request->fundId)
            ->where('cat_status', 'deactivated')
            ->orderBy('cat_code')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'code' => $category->cat_code,
                    'name' => $category->cat_name,
                    'status' => ucfirst($category->cat_status),
                    'fundId' => $category->funder->id ?? '',
                    'fundName' => $category->funder->fund_name ?? '',
                    'updatedAt' => $category->updated_at->format('F j, Y'),
                    'updatedBy' => $category->updater->name ?? '',
                ];
            });

        return response()->json(['data' => $categories]);
    }

    public function deactivate(Request $request) {

        DB::beginTransaction();

        try {
            $catId = $request->input('catId');
            $updatedBy = $request->input('updater');

            $category = Category::findOrFail($catId);
            $category->load('items.products');

            if($category->items->isEmpty()) {
                $category->forceDelete();

                DB::commit();
                return redirect()->back()
                ->with(['message' => 'Category was deleted successfully.']);
            }

            foreach ($category->items as $item) {
                $this->deleteProductsForItem($item, $updatedBy);
                $this->deleteItem($item, $updatedBy);
            }

            $category->update(['cat_status' => 'deactivated', 'updated_by' => $updatedBy]);

            DB::commit();
            return redirect()->back()
                ->with(['message' => 'Category has been move to trash.']);
        } catch (\Exception $e) {

            DB::rollBack();
            Log::error("Deletion of Category Failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Deletion of a Category Failed. Please try again!');
        }
    }

    private function deleteProductsForItem($item, $updatedBy) {
        $productIds = $item->products->pluck('id');
        $item->products()->whereIn('id', $productIds)->update(['updated_by' => $updatedBy]);
        $item->products()->whereIn('id', $productIds)->delete();
    }

    private function deleteItem($item, $updatedBy) {
        $item->update(['updated_by' => $updatedBy]);
        $item->delete();
    }

    private function restoreProductsForItem($item, $updatedBy) {
        $productIds = $item->products->pluck('id');
        $item->products()->whereIn('id', $productIds)->update(['updated_by' => $updatedBy]);
        $item->products()->whereIn('id', $productIds)->restore();
    }

    private function restoreItem($item, $updatedBy) {
        $item->update(['updated_by' => $updatedBy]);
        $item->restore();
    }
}
