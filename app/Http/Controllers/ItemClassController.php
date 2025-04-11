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
        
        $categories = $this->productService->getActiveCategory()->map(fn($category) => [
            'id' => $category->id,
            'name' => $category->cat_name
        ]);

        return Inertia::render('Item/Index', [
            'activeItemClass' => $activeItemClass,
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
            Log::error("Creating New Item Class Failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with(['error' => 'Creation of New Item Class Failed. Please try again!']);
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
            Log::error("Updating Item Class Failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with(['error' => 'Updating Item Class Failed. Please try again!']);
        }
    }

    public function restore(ItemClass $itemClass)
    {
        DB::beginTransaction();
        $user = Auth::id();

        try
        {
            $itemClassDetails = $itemClass->load([
                'category', 
                'products' => function ($query) {
                    $query->onlyTrashed()
                        ->where('prod_status', 'active')
                        ->lockForUpdate();
                }
            ]);

            if($itemClassDetails->category->cat_status != 'active'){
                DB::rollBack();
                return redirect()->back()->with(['error' => 'Unable to restore the item class, main category is inactive!']);
            }

            foreach ($itemClassDetails->products as $product) {
                $product->update(['updated_by' => $user]);
                $product->restore();
            }

            $itemClassDetails->update(['item_status' => 'active', 'updated_by' => $user]);
            
            DB::commit();
            return redirect()->back()
                ->with(['message' => 'Item Class has been restored!.']);
        } catch(\Exception $e) {

            DB::rollBack();
            Log::error("Restoring Item Class Failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with(['error' => 'Restoring Item Class Failed. Please try again!']);
        }
    }

    public function showTrashedItemClass()
    {
        $deactivatedItemClass = $this->getDeactivitedItemClass();
        return response()->json(['data' => $deactivatedItemClass]);
    }

    public function deactivate(Request $request, ItemClass $itemClass)
    {
        DB::beginTransaction();

        $validatedData = $request->validate([
            'itemId' => 'required|integer',
            'updatedBy' => 'required|integer',
        ]);

        try {
            $itemClass = ItemClass::with(['products' => function ($query) {
                $query->withTrashed();
            }])->findOrFail($validatedData['itemId']);

            if($itemClass->products->isEmpty()) {
                $itemClass->forceDelete();

                DB::commit();
                return redirect()->back()
                    ->with(['message' => 'Item Class Name was removed successfully']);
            }

            foreach ($itemClass->products as $product) {  
                if ($product->prod_status == 'active' && $product->deleted_at == null) {
                    $product->update([
                        'updated_by' => $validatedData['updatedBy'],
                    ]);
    
                    $product->delete();
                }
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
            Log::error("Deletion of Item Class Failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with(['error' => 'Deleting Item Class Failed. Please try again!']);
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
        return ItemClass::where('item_status', 'deactivated')
            ->with('category', 'updater')
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
                'updatedBy' => $item->updater ? $item->updater->name : '',
                'removeAt' => $item->updated_at->format('F j, Y'),
            ]
        );
    }
}
