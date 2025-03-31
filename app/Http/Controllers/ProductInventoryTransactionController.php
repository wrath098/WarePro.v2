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
        $currentInventory = $request->qty;
        $formattedDate = $this->defaultDateFormat($request->dateOfAdjustment);
        
        try {
            $productInventory = ProductInventory::where('id', $request->pid)->lockForUpdate()->first();

            if($request->pid) {
                $currentInventory = $productInventory->qty_on_stock + $request->qty;
                $productInventory->qty_on_stock += $request->qty;
                $productInventory->updated_by = Auth::id();
                $productInventory->save();
            } else {
                ProductInventory::create([
                    'qty_on_stock' => $request->qty,
                    'prod_id' => $request->prodId,
                    'updated_by' => Auth::id(),
                ]);
            }

            $isDateValid = $this->prodDateValidation($request->prodId, $formattedDate);
            if(!$isDateValid) {
                DB::rollback();
                throw new \Exception('Please check the Date. Product/s latest transaction is later than the inputted Date!');
            }

            ProductInventoryTransaction::create([
                'type' => $request->type,
                'qty' => $request->qty,
                'notes' => $request->remarks,
                'prod_id' => $request->prodId,
                'current_stock' => $currentInventory,
                'created_by' => Auth::id(),
                'created_at' => $formattedDate,
            ]);
            
            DB::commit();
            return redirect()->back()->with(['message' => 'Successfully added the quantity to Product code ' . $request->stockNo]);
        } catch(\Exception $e) {
            DB::rollBack();
            Log::error("Error during transaction: " . $e->getMessage());
            return redirect()->back()->with(['error' => $e->getMessage()]);
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
            $productInventory = ProductInventory::updateOrCreate(
                ['prod_id' => $validated['prodId']],
                [
                    'reorder_level' => $validated['reorder'],
                    'updated_by' => Auth::id(),
                ]
            );

            DB::commit();
            return redirect()->back()->with(['message' => 'Successfully updated the quantity re-order level of Product code# ' . $validated['stockNo']]);
        } catch(\Exception $e) {
            DB::rollBack();
            Log::error("Error during updating of the quantity re-order level of Product code# " . $validated['stockNo'] . " : " . $e->getMessage());
            return redirect()->back()->with(['error' => 'Faile to update the quantity re-order level of Product code# ' . $validated['stockNo']]);
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

    private function verifyExpiryDateStatus($date): string
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

    private function getPurchaseDetails($refNo)
    {
        return IarTransaction::findOrFail($refNo)->select('sdi_iar_id', 'po_no')->first();
    }

    private function defaultDateFormat($inputDate)
    {
        $date = $inputDate;
        $currentTime = Carbon::now()->toTimeString();

        $combinedDateTime = Carbon::parse($date . ' ' . $currentTime)->format('Y-m-d H:i:s');
        
        return $combinedDateTime;
    }

    private function prodDateValidation($prodId, $date)
    {
        $query = ProductInventoryTransaction::withTrashed()->where('prod_id', $prodId)->orderBy('created_at', 'desc')->first();

        if(!$query) {
            return true;
        }

        return $date > $query->created_at;
    }
}
