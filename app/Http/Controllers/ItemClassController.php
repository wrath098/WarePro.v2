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
    public function index(Request $request): Response
    {
        $queryItem = ItemClass::query()
        ->when($request->input('search'), function ($query, $search){
            $query->where('item_name', 'like', '%' . $search . '%');
        })
        ->with('category', 'creator')
        ->where('item_status', 'active')
        ->orderBy('cat_id', 'asc')
        ->orderBy('item_name', 'asc')
        ->paginate(10)
        ->withQueryString();

        $itemClass = $queryItem->through(fn($item) => [
            'id' => $item->id,
            'code' => str_pad($item->item_code, 2, '0', STR_PAD_LEFT),
            'name' => $item->item_name,
            'catId' => $item->category->id,
            'category' => $item->category->cat_name,
            'status' => ucfirst($item->item_status),
            'creator' => $item->creator->name,
        ]);

        $categories = $this->productService->getActiveCategory()->map(fn($category) => [
            'id' => $category->id,
            'name' => $category->cat_name
        ]);

        return Inertia::render('Item/Index', ['itemClasses' => $itemClass, 'filters' => $request->only(['search']), 'categories' => $categories, 'authUserId' => Auth::id()]);
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
     * Update the specified resource in storage.
     */
    public function update(Request $request, ItemClass $itemClass)
    {
        $validatedData = $request->validate([
            'itemId' => 'required|integer',
            'editName' => 'required|string|max:255',
            'updatedBy' => 'required|integer',
        ]);

        try {
            $itemClass = ItemClass::findOrFail($validatedData['itemId']);

            $itemClass->fill([
                'item_name' => $validatedData['editName'],
                'updated_by' => $validatedData['updatedBy'],
            ])->save();

            return redirect()->route('item.display.active')->with(['message' => 'Item Class name was updated successfully.']);
        } catch (\Exception $e) {
            return redirect()->route('item.display.active')->with(['error' => 'An error occurred while adding the new item class.']);
        }
    }

    public function deactivate(Request $request, ItemClass $itemClass)
    {
        $validatedData = $request->validate([
            'itemId' => 'required|integer',
            'updatedBy' => 'required|integer',
        ]);

        try {
            $itemClass = ItemClass::findOrFail($validatedData['itemId']);

            $itemClass->fill([
                'item_status' => 'deactivated',
                'updated_by' => $validatedData['updatedBy'],
            ])->save();

            return redirect()->route('item.display.active')->with(['message' => 'Item Class name was updated successfully.']);
        } catch (\Exception $e) {
            return redirect()->route('item.display.active')->with(['error' => 'An error occurred while adding the new item class.']);
        }
    }
}
