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
        try {
            DB::transaction(function () use ($request) {
                Category::create([
                    'fund_id' => $request->input('fundId'),
                    'cat_name' => $request->input('catName'),
                    'cat_code' => $request->input('catCode'),
                    'created_by' => $request->input('createdBy'),
                ]);
            });
            return redirect()->route('category.display.active')->with(['message' => 'New Category was created successfully']);
        } catch (\Exception $e) {
            return redirect()->route('category.display.active')->with(['error' => 'Failed to create New Category']);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        //
    }
}
