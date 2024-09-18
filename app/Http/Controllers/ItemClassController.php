<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ItemClass;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ItemClassController extends Controller
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
        // $itemClass = $this->productService->getActiveItemclass()->map(fn($item) => [
        //     'id' => $item->id,
        //     'code' => sprintf('%02d', $item->item_code),
        //     'name' => $item->item_name
        // ]);

        $itemClass = $this->productService->getActiveItemclass();

        $categories = $this->productService->getActiveCategory()->map(fn($category) => [
            'id' => $category->id,
            'name' => $category->cat_name
        ]);

        return Inertia::render('Item/Index', ['itemClasses' => $itemClass, 'categories' => $categories, 'authUserId' => Auth::id()]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'catId' => 'required|exists:categories,id',
            'itemCode' => 'required|integer',
            'itemName' => 'required|string|max:255',
            'createdBy' => 'required|integer',

        ]);

        $catId = $validated['catId'];
        $itemCode = $validated['itemCode'];
        $itemName = $validated['itemName'];
        $createdBy = $validated['createdBy'];

        try {
            $itemExists = ItemClass::where('cat_id', $catId)
                                    ->where('item_code', $itemCode)
                                    ->exists();

            if($itemExists) {
                return redirect()->route('item.display.active')->with(['error' => 'Item code is already taken within the selected Category.']);
            } 

            ItemClass::create([
                'item_code' => $itemCode,
                'item_name' => $itemName,
                'cat_id' => $catId,
                'created_by' => $createdBy,
            ]);

            return redirect()->route('item.display.active')->with(['message' => 'New Item Class has been successfully added']);
            
        } catch (\Exception $e) {
            return redirect()->route('item.display.active')->with(['error' => 'An error occurred while adding the new item class.']);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ItemClass $itemClass)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ItemClass $itemClass)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ItemClass $itemClass)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ItemClass $itemClass)
    {
        //
    }
}
