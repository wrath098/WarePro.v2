<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\IarTransaction;
use App\Models\ItemClass;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\ProductInventoryTransaction;
use App\Models\PrTransaction;
use App\Models\RisTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $products = Product::where('prod_status', 'active')->count();

        $core = [
            'product' => $products,
            'redorder' => $this->countReorderProductItem(),
            'iarTransaction' => $this->countIarPendingTransactions(),
            'risTransaction' => $this->countRisTransactionThisDay(),
            'prTransaction'=> $this->countDraftedPurchaseRequest(),
            'availableProductItem' => $this->countAvailableProductItem(),
            'expiringProduct' => $this->countExpiringProductItem(),
            'outOfStockProducts' => $this->countOutOfStockProductItem(),
        ];
        
        return Inertia::render('Dashboard', ['core' => $core]);
    }

    private function countIarPendingTransactions(): int
    {
        return IarTransaction::selectRaw('COUNT(*) as count')
            ->where('status', 'pending')
            ->value('count') ?? 0;
    }

    private function countRisTransactionThisDay(): int
    {
        $today = Carbon::today('Asia/Manila');
        return RisTransaction::whereBetween('created_at', [
            $today->copy()->startOfDay(), 
            $today->copy()->endOfDay()
        ])->count();
    }

    private function countDraftedPurchaseRequest(): int
    {
        return PrTransaction::selectRaw('COUNT(*) as count')
            ->where('pr_status', 'draft')
            ->value('count') ?? 0;;
    }

    private function countAvailableProductItem(): int
    {
        return ProductInventory::where('qty_on_stock', '>', 0)->count();
    }  

    private function countExpiringProductItem(): int
    {   
        return ProductInventoryTransaction::where('type', ['purchase', 'adjustment'])
            ->where('dispatch', 'incomplete')
            ->whereNotNull('date_expiry')
            ->where('date_expiry', '<=', now()->addDays(90))
            ->count();
    }

    private function countReorderProductItem(): int
    {
        return ProductInventory::whereColumn('qty_on_stock', '<=', 'reorder_level')->where('qty_on_stock', '!=', 0)->count();
    }

    private function countOutOfStockProductItem(): int
    {
        return Product::where('prod_status', 'active')
            ->where(function ($query) {
                $query->whereHas('inventory', fn($q) => $q->where('qty_on_stock', 0))
                    ->orDoesntHave('inventory');
            })
            ->count();
    }

    // public function fetchLatestProductsWithPrices()
    // {
    //         return ProductPrice::query()
    //             ->selectRaw('DATE(created_at) as date, AVG(price) as price')
    //             ->groupBy('date')
    //             ->orderBy('date')
    //             ->get();
    // }
}
