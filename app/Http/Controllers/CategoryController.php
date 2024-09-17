<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {   
        $categories = $this->productService->getActiveCategory();
        $funds = $this->productService->getActiveFunds();

        $funds = $funds->map(function ($fund) {
            return [
                'id' => $fund->id,
                'name' => $fund->fund_name
            ];
        });

        $categories->load('funder', 'creator');
                    
        $categories = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'code' => $category->cat_code,
                'name' => $category->cat_name,
                'status' => $category->cat_status,
                'fundId' => $category->funder->id,
                'fundName' => $category->funder->fund_name,
                'creatorName' => $category->creator->name,
            ];
        });

        return Inertia::render('Category/Index', ['categories' => $categories, 'funds' => $funds, 'authUserId' => Auth::id()]); 
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $fundId = $request->input('fundId');
        $catCode = $request->input('catCode');
        $catName = $request->input('catName');
        $createdBy = $request->input('createdBy');
        
        try {
            $existingCategory  = $this->productService->validateCategoryExistence($fundId, $catCode, $catName);

            if($existingCategory) {
                return redirect()->route('category.display.active')->with(['error' => 'Category is already exist. If not on the list below, Please verify this to your system administrator.']);
            } else {
                Category::create([
                    'fund_id' => $fundId,
                    'cat_name' => $catName,
                    'cat_code' => $catCode,
                    'created_by' => $createdBy,
                ]);

                return redirect()->route('category.display.active')->with(['message' => 'New Category was created successfully']);
            }
        } catch (\Exception $e) {
            return redirect()->route('category.display.active')->with(['error' => 'Failed to create New Category']);
        }
    }

    /**
     * Update the specified resource in storage.
     */
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
            DB::transaction(function () use ($catId, $fundId, $catCode, $catName, $updater) {
                $category = Category::findOrFail($catId);

                if($category->cat_code !== $catCode || $category->fund_id !== $fundId) {
                    $existingCategory  = $this->productService->validateCategoryExistence($fundId, $catCode, $catName);
                    $category->update(['cat_status' => 'deactivated', 'updated_by' => $updater]);

                    if($existingCategory ) {
                        $existingCategory ->update(['cat_status' => 'active', 'updated_by' => $updater]);
                    } else {
                        Category::create([
                            'fund_id' => $fundId,
                            'cat_name' => $catName,
                            'cat_code' => $catCode,
                            'created_by' => $updater,
                        ]);
                    }
                } else {
                    $category->update(['cat_name' => $catName, 'updated_by' => $updater]);
                }
                return redirect()->route('category.display.active')->with(['message' => 'Category was updated successfully.']);
            });
        } catch (\Exception $e) {
            return redirect()->route('category.display.active')->with(['error' => 'Failed to update the category.']);
        }
    }

    public function deactivate(Request $request) {
        try {

            $catId = $request->input('catId');
            $updatedBy = $request->input('updater');

            $category = Category::findOrFail($catId);
            $category->fill([
                'cat_status' => 'deactivated',
                'updated_by' => $updatedBy,
            ])->save();

            return redirect()->route('category.display.active')->with(['message' => 'Category was remove successfully.']);
        } catch (\Exception $e) {
            return redirect()->route('category.display.active')->with(['error' => 'Failed to remove the category.']);
        }
    }
}
