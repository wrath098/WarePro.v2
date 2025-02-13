<?php

namespace App\Http\Controllers;

use App\Models\ItemClass;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ItemClassController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(): Response
    {
        $activeItemClass = $this->getActiveItemClass();
        $deactivatedItemClass = $this->getDeactivitedItemClass();
        
        $categories = $this->productService->getActiveCategory()->map(fn($category) => [
            'id' => $category->id,
            'name' => $category->cat_name
        ]);

        return Inertia::render('Item/Index', [
            'activeItemClass' => $activeItemClass,
            'deactivatedItemClass' => $deactivatedItemClass,
            'categories' => $categories,
            'authUserId' => Auth::id()
        ]);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        $validated = $request->validate([
            'catId' => 'required|exists:categories,id',
            'itemName' => 'required|string|max:255',
            'createdBy' => 'required|integer',
        ]);

        $catId = $validated['catId'];
        $itemName = $validated['itemName'];
        $createdBy = $validated['createdBy'];

        try {
            $itemNameExist = ItemClass::withTrashed()->where('cat_id', $catId)->whereRaw('LOWER(item_name) = ?', [strtolower($validated['itemName'])])->first();

            if ($itemNameExist) {
                DB::rollBack();
                return redirect()->back()->with(['error' => 'Item Name under the selected category is already exist.']);
            }

            $latestCode = ItemClass::withTrashed()
                    ->where('cat_id', $catId)
                    ->orderBy('created_at', 'desc')
                    ->select('item_code')
                    ->first();

            $itemCode = $latestCode ? (int) $latestCode->item_code + 1 : 1;

            ItemClass::create([
                'item_code' => $itemCode,
                'item_name' => $itemName,
                'cat_id' => $catId,
                'created_by' => $createdBy,
            ]);

            DB::commit();
            return redirect()->back()
                ->with(['message' => 'New Item Class has been successfully added']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Creation of Item Class: ' . $e->getMessage());
            return redirect()
                ->with(['error' => 'An error occurred while adding the new item class.']);
        }
    }

    public function update(Request $request, ItemClass $itemClass)
    {
        DB::beginTransaction();
        $validatedData = $request->validate([
            'itemId' => 'required|integer',
            'editName' => 'required|string|max:255',
            'updatedBy' => 'required|integer',
        ]);

        try {
            $itemClass = ItemClass::findOrFail($validatedData['itemId']);
            $itemNameExist = ItemClass::withTrashed()->whereRaw('LOWER(item_name) = ?', [strtolower($validatedData['editName'])])->first();

            if ($itemNameExist) {
                DB::rollBack();
                return redirect()->back()->with(['error' => 'Item Name under the selected category is already exist.']);
            }

            $itemClass->fill([
                'item_name' => $validatedData['editName'],
                'updated_by' => $validatedData['updatedBy'],
            ])->save();
            
            DB::commit();
            return redirect()->back()
                ->with(['message' => 'Item Class name was updated successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update of Item Class: ' . $e->getMessage());
            return redirect()->back()
                ->with(['error' => 'An error occurred while adding the new item class.']);
        }
    }

    public function restore(ItemClass $itemClass)
    {
        DB::beginTransaction();
        $user = Auth::id();

        try
        {
            $itemClassDetails = $itemClass->load(['category', 'products']);
            $itemClassDetails->lockForUpdate();

            if($itemClassDetails->category->cat_status != 'active'){
                DB::rollBack();
                return redirect()->back()->with(['error' => 'Unable to restore the item class, main category is inactive!']);
            }

            foreach ($itemClass->products as $product) {  
                $product->update([
                    'prod_status' => 'active',
                    'updated_by' => $user,
                ]);
            }

            $itemClass->update([
                'item_status' => 'active',
                'updated_by' => $user,
            ]);
            
            DB::commit();
            return redirect()->back()
                ->with(['message' => 'Item Class has been restored!.']);
        } catch(\Exception $e) {
            
            DB::rollBack();
            Log::error('Restoration of Item Class failed: ' . $e->getMessage());
            return redirect()->back()
                ->with(['error' => 'An error occurred while restoring the item class.']);
        }
        
    }

    public function deactivate(Request $request, ItemClass $itemClass)
    {
        DB::beginTransaction();

        $validatedData = $request->validate([
            'itemId' => 'required|integer',
            'updatedBy' => 'required|integer',
        ]);

        try {
            $itemClass = ItemClass::findOrFail($validatedData['itemId']);
            $itemClass->load('products');

            if($itemClass->products->isEmpty()) {
                $itemClass->forceDelete();

                DB::commit();
                return redirect()->back()
                    ->with(['message' => 'Item Class Name was removed successfully']);
            }

            foreach ($itemClass->products as $product) {  
                $product->update([
                    'prod_status' => 'deactivated',
                    'updated_by' => $validatedData['updatedBy'],
                ]);
            }

            $itemClass->update([
                'item_status' => 'deactivated',
                'updated_by' => $validatedData['updatedBy'],
            ]);
            
            DB::commit();
            return redirect()->back()
                ->with(['message' => 'Item Class has been move to trash!.']);

        } catch (\Exception $e) {

            DB::rollBack();
            Log::error('Deletion of Item Class: ' . $e->getMessage());
            return redirect()->back()
                ->with(['error' => 'An error occurred while removing the new item class.']);
        }
    }

    private function getActiveItemClass()
    {
        $itemClass = ItemClass::with('category', 'creator')
            ->where('item_status', 'active')
            ->orderBy('cat_id', 'asc')
            ->orderBy('item_name', 'asc')
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'code' => str_pad($item->item_code, 2, '0', STR_PAD_LEFT),
                'name' => $item->item_name,
                'catId' => $item->category->id,
                'category' => $item->category->cat_name,
                'status' => ucfirst($item->item_status),
                'creator' => $item->creator->name,
                'createdAt' => $item->created_at->format('F j, Y'),
            ]
        );

        return $itemClass;
    }

    private function getDeactivitedItemClass()
    {
        $itemClass = ItemClass::with('category', 'creator')
            ->where('item_status', 'deactivated')
            ->orderBy('cat_id', 'asc')
            ->orderBy('item_name', 'asc')
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'code' => str_pad($item->item_code, 2, '0', STR_PAD_LEFT),
                'name' => $item->item_name,
                'catId' => $item->category->id,
                'category' => $item->category->cat_name,
                'status' => ucfirst($item->item_status),
                'creator' => $item->creator->name,
                'updatedAt' => $item->updated_at->format('F j, Y'),
            ]
        );

        return $itemClass;
    }
}
