<?php

namespace App\Http\Controllers;

use App\Models\Category;
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

    public function index(): Response
    {   
        $categories = $this->productService->getActiveCategory();
        $deactivatedCategories = $this->productService->getDeactivatedCategory();
        $funds = $this->productService->getActiveFunds();

        $funds = $funds->map(function ($fund) {
            return [
                'id' => $fund->id,
                'name' => $fund->fund_name
            ];
        });

        $categories->load('funder', 'creator');
        $deactivatedCategories->load('funder', 'updater');
                    
        $categories = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'code' => $category->cat_code,
                'name' => $category->cat_name,
                'status' => ucfirst($category->cat_status),
                'fundId' => $category->funder->id ?? '',
                'fundName' => $category->funder->fund_name ?? '',
                'creatorName' => $category->creator->name,
            ];
        });

        $deactivatedCategories = $deactivatedCategories->map(function ($category) {
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

        return Inertia::render('Category/Index', [
            'activeCategories' => $categories,
            'deactivatedCategories' => $deactivatedCategories, 
            'funds' => $funds, 
            'authUserId' => Auth::id()
        ]); 
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        $fundId = $request->input('fundId');
        $catName = $request->input('catName');
        $createdBy = $request->input('createdBy');
        
        try {
            $lastCode = Category::selectRaw('cat_code')->orderBy('cat_code', 'desc')->first();
            $newCode = (int) $lastCode->cat_code + 1;

            $existingCategory  = $this->productService->validateCategoryExistence($fundId, $catName);

            if($existingCategory) {
                DB::rollBack();
                return redirect()->back()->with(['error' => 'Category Name is already exist.']);
            }

            Category::create([
                'fund_id' => $fundId,
                'cat_name' => $catName,
                'cat_code' => $newCode,
                'created_by' => $createdBy,
            ]);

            Db::commit();
            return redirect()->back()->with(['message' => 'New Category was created successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create category: ' . $e->getMessage());
            return redirect()->back()->with(['error' => 'Failed to create New Category']);
        }
    }

    public function update(Request $request)
    {   
        $validated = $request->validate([
            'catId' => 'required|exists:categories,id',
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
            DB::transaction(function () use ($catId, $fundId, $catCode, $catName, $updater, $request) {
                $category = Category::findOrFail($catId);
                $existingCategory  = $this->productService->validateCategoryExistence($fundId, $catName);

                if($existingCategory) {
                    return redirect()->back()
                    ->with(['error' => 'Category Name already exist.']);
                }
;
                $category->update(['cat_name' => $catName, 'updated_by' => $updater]);
                return redirect()->back()
                    ->with(['message' => 'Category was updated successfully.']);
            });
        } catch (\Exception $e) {
            Log::error('Failed to update the category: ' . $e->getMessage());
            return redirect()->back()
                ->with(['error' => 'Failed to update the category.']);
        }
    }

    public function restore(Category $catId)
    {
        DB::beginTransaction();

        try {
            $catId->load('items.products');
            $catId->lockForUpdate();
            $user = Auth::id();

            foreach ($catId->items as $item) {
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

            $catId->update([
                'cat_status' => 'active',
                'updated_by' => $user,
            ]);

            DB::commit();
            return redirect()->back()->with(['message' => 'A category was activated.']);

        } catch (\Exception $e) {
            
            DB::rollBack();
            return redirect()->back()->with(['error' => $e->getMessage()]);
        }       
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
                ->with(['message' => 'Category was remove successfully.']);
            }

            foreach ($category->items as $item) {
                $productIds = $item->products->pluck('id');
        
                $item->products()->whereIn('id', $productIds)->update([
                    'prod_status' => 'deactivated',
                    'updated_by' => $updatedBy,
                ]);

                $item->update([
                    'item_status' => 'deactivated',
                    'updated_by' => $updatedBy,
                ]);
            }
            $category->update([
                'cat_status' => 'deactivated',
                'updated_by' => $updatedBy,
            ]);

            DB::commit();
            return redirect()->back()
                ->with(['message' => 'Category has been move to trash.']);
        } catch (\Exception $e) {

            DB::rollBack();
            Log::error('Failed to remove the category: ' . $e->getMessage());
            return redirect()->back()
                ->with(['error' => 'Failed to remove the category.']);
        }
    }
}
