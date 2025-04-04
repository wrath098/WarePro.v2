<?php

namespace App\Http\Controllers;

use App\Models\IarTransaction;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\ProductInventoryTransaction;
use App\Services\ProductService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProductInventoryTransactionController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function showProductsOnExpired()
    {
        $listOfExpiries = $this->getProductsWithExpiry();

        $countExpired = $listOfExpiries->filter(function($transaction) {
            return $transaction['status'] === 'Expired';
        })->count();

        $countExpiring = $listOfExpiries->filter(function($transaction) {
            return $transaction['status'] === 'Expiring';
        })->count();

        return Inertia::render('Inventory/OnExpiry', [
            'products' => $listOfExpiries,
            'countExpired' => $countExpired,
            'countExpiring' => $countExpiring,
        ]);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();        
        try {
            $userId = Auth::id();
            $currentInventory = $request->qty;
            $formattedDate = $this->productService->defaultDateFormat($request->dateOfAdjustment);

            if (!$formattedDate || !$this->productService->isDateValid($formattedDate)) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Please verify the entered date and try again!');
            }

            $productInventory = ProductInventory::where('id', $request->pid)->lockForUpdate()->first();
            
            $previousTransaction = $this->productService->getPreviousProductInventoryTransaction($request->prodId, $formattedDate);
            $previousInventory = $previousTransaction->current_stock ?? 0;

            $succeedingTransactions = $this->productService->getSucceedingProductInventoryTransaction($request->prodId, $formattedDate);

            if($request->pid) {
                $currentInventory = $previousInventory + $request->qty;
                $productInventory->qty_on_stock += $request->qty;
                
                if ($productInventory->qty_physical_count > 0) {
                    $productInventory->qty_purchase += $request->qty;
                } else {
                    $productInventory->qty_physical_count = $request->qty;
                }

                $productInventory->updated_by = $userId;
                $productInventory->save();
            } else {
                ProductInventory::create([
                    'qty_on_stock' => $currentInventory,
                    'qty_physical_count' => $currentInventory,
                    'prod_id' => $request->prodId,
                    'updated_by' => $userId,
                ]);
            }

            $this->createInventoryTransaction($request , $userId, $currentInventory, $formattedDate);
            $this->productService->updateInventoryTransactionsCurrentStock($succeedingTransactions, $currentInventory);
            
            DB::commit();
            return redirect()->back()->with(['message' => 'Successfully added quantity to Product Code# ' . $request->stockNo]);
        } catch(\Exception $e) {
            DB::rollBack();
            Log::error("Product Inventory adjustment failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Failed to update inventory. Please try again.');
        }
    }
    
    public function updateReOrderLevel(Request $request)
    {
        $validated = $request->validate([
            'prodId' => 'required|exists:products,id',
            'reorder' => 'required|numeric|min:1', 
            'stockNo' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            ProductInventory::updateOrCreate(
                ['prod_id' => $validated['prodId']],
                [
                    'reorder_level' => $validated['reorder'],
                    'updated_by' => Auth::id(),
                ]
            );

            DB::commit();
            return redirect()->back()->with(['message' => 'Successfully updated the reorder quantity level for Product Code# ' . $validated['stockNo']]);
        } catch(\Exception $e) {
            DB::rollBack();
            Log::error("Product Inventory Threshold failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with(['error' => 'Failed to update product inventory reorder level. Please try again!']);
        }
    }

    private function getProductsWithExpiry()
    {   
        $query =  ProductInventoryTransaction::where('type', 'purchase')
            ->where('dispatch', 'incomplete')
            ->whereNotNull('date_expiry')
            ->orderBy('date_expiry', 'asc')
            ->get()
            ->map(function($query) {
                $status = $this->verifyExpiryDateStatus($query->date_expiry);
                $productName = Str::limit($this->productService->getProductName($query->prod_id), 75, '...');
                $productCode = $this->productService->getProductCode($query->prod_id);
                $description = $this->getPurchaseDetails($query->ref_no);
                return [
                    'tId' => $query->id,
                    'prodId' => $query->prod_id,
                    'prodDesc' => $productName,
                    'stockNo' => $productCode,
                    'qtyLeft' => $query->stock_qty,
                    'status' => $status,
                    'description' => $description,
                    'dateExpired' => $query->date_expiry,
                ];
            });
        
            return $query->filter(function($transaction) {
                return $transaction['status'] !== null;
            });
    }

    private function verifyExpiryDateStatus(string $date)
    {
        $dateToCheck = Carbon::parse($date);
        $currentDate = Carbon::now();

        if ($dateToCheck->isPast()) {
            return 'Expired';
        }

        if ($dateToCheck->diffInDays($currentDate) <= 90) {
            return 'Expiring';
        }

        return true;
    }

    private function getPurchaseDetails(int $refNo): ?IarTransaction
    {
        return IarTransaction::findOrFail($refNo)->select('sdi_iar_id', 'po_no')->first();
    }

    private function createInventoryTransaction(object $request, int $userId, int $currentInventory, string $formattedDate): ?ProductInventoryTransaction
    {
        return ProductInventoryTransaction::create([
            'type' => $request->type ?? 'adjustment',
            'qty' => $request->qty,
            'stock_qty' => $request->qty,
            'notes' => $request->remarks,
            'prod_id' => $request->prodId,
            'current_stock' => $currentInventory,
            'created_by' => $userId,
            'created_at' => $formattedDate,
        ]);
    }
}
