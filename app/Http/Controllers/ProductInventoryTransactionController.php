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

    public function inventoryReport(Request $request)
    {
        return Inertia::render('Inventory/MonthlyInventory');
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
                return back()->with('error', 'Please verify the entered date and try again!');
            }

            $productInventory = ProductInventory::where('id', $request->pid)->lockForUpdate()->first();

            if ($productInventory && $productInventory->qty_physical_count > 0) {
                DB::rollBack();
                return redirect()->back()->with(['error' => 'Product Code #'. $request->stockNo. ' already has an existing inventory and cannot be updated. Please contact the system administrator if you need to modify the current stock']);
            }
            
            $succeedingTransactions = $this->succeedingTransaction($request->prodId);

            if($request->pid) {
                $productInventory->qty_on_stock = $currentInventory;
                $productInventory->qty_physical_count = $currentInventory;
                $productInventory->qty_purchase = 0;
                $productInventory->qty_issued = 0;
                $productInventory->updated_by = $userId;
                $productInventory->save();
            } else {
                $productInventory = ProductInventory::create([
                    'qty_on_stock' => $currentInventory,
                    'qty_physical_count' => $currentInventory,
                    'prod_id' => $request->prodId,
                    'updated_by' => $userId,
                ]);

                $this->createInventoryTransaction($request , $userId, $currentInventory, $formattedDate);
            }

            if($succeedingTransactions->isNotEmpty()) {
                $this->updateInventoryTransaction($succeedingTransactions, $currentInventory, $productInventory);
            }
            
            DB::commit();
            return redirect()->back()->with(['message' => 'Successfully added quantity to Product Code# ' . $request->stockNo]);
        } catch(\Exception $e) {
            DB::rollBack();
            Log::error("Product Inventory adjustment failed: ", [
                'user' => Auth::user()->name,
                'error' => $e->getMessage(),
                'data' => $request->toArray()
            ]);
            return back()->with('error', 'Failed to update inventory. Please try again.');
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
                'data' => $request->toArray()
            ]);
            return back()->with(['error' => 'Failed to update product inventory reorder level. Please try again!']);
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
        $currentDate = Carbon::now();
        $dateToCheck = Carbon::parse($date);
        
        if ($dateToCheck->isPast()) {
            return 'Expired';
        }

        if ($currentDate->diffInDays($dateToCheck) <= 90) {
            return 'Expiring';
        }

        return null;
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

    private function succeedingTransaction(int $prodId) {
        return ProductInventoryTransaction::withTrashed()
            ->where('prod_id', $prodId)
            ->oldest('created_at')
            ->get();
    }

    private function updateInventoryTransaction(iterable $transactions, int $qty = 0, iterable $inventoryTransaction): void
    {
        $requestQty = $qty;
        $stockQty = $qty;
        $currentStock = $qty;

        $purchasesQty = 0;
        $issuanceQty = 0;

        $beginningBalanceTransaction = null;

        foreach ($transactions as $transaction) {
            switch ($transaction->type) {
                case 'adjustment':
                    $beginningBalanceTransaction = $transaction;
                    $transaction->qty = $requestQty;
                    $transaction->stock_qty = $stockQty;
                    $transaction->current_stock = $currentStock;
                    $transaction->save();
                    break;

                case 'issuance':
                    $issuanceQty += $transaction->qty;
                    $currentStock -= $transaction->qty;
                    $stockQty -= $transaction->qty;
                    $transaction->current_stock = $currentStock;
                    $transaction->save();
                    break;

                default:
                    $purchasesQty += $transaction->qty;
                    $currentStock += $transaction->qty;
                    $transaction->current_stock = $currentStock;
                    $transaction->save();
                    break;
            }
        }

        if ($beginningBalanceTransaction) {
            $beginningBalanceTransaction->stock_qty = $stockQty;
            $beginningBalanceTransaction->save();
        }

        $inventoryTransaction->qty_physical_count = $purchasesQty;
        $inventoryTransaction->qty_issued = $issuanceQty;
        $inventoryTransaction->qty_on_stock = $currentStock;
        $inventoryTransaction->save();
    }
}
