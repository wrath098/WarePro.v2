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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
            'pastMonths' => $this->pastMonths(),
        ];

        $products = $this->monitorProductItemsPriceStatus();
        
        return Inertia::render('Dashboard', ['core' => $core, 'products' => $products]);
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

    private function pastMonths(int $months = 5): Collection
    {
        return collect(range(0, $months))
            ->map(fn ($m) => now()->subMonths($m))
            ->sort()
            ->map(fn ($date) => [
                'date' => $date->format('Y-m'),
                'month' => $date->format('F Y'),
                'itemPriceHike' => 0,
                'itemPriceStable' => 0,
                'itemPriceDown' => 0,
            ]);
    }

    private function monitorProductItemsPriceStatus()
    {
        $months = $this->pastMonths();
        $monthDates = $months->pluck('date')->all();
        
        Product::select('id')
        ->with(['prices' => function ($query) use ($monthDates) {
            $query->whereIn(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m')"), 
                $monthDates
            )->latest('created_at')
            ->take(1);
        }])
        ->where('prod_status', 'active')
        ->chunk(200, function ($products) use (&$months) {
            foreach ($products as $product) {
                $this->processProductPrices($product, $months);
            }
        });

        return $months;
    }

    private function processProductPrices(Product $product, Collection &$months): void
    {        
        foreach ($months as &$month) {
            $price = $product->prices->firstWhere(function ($price) use ($month) {
                $monthKey = Carbon::parse($price->created_at)->format('Y-m');
                return $monthKey == $month['date'];
            });

            $lastPrice = $this->getPreviousPrice($product, $month['date']);

            if (!$price) {
                $month['itemPriceStable'] += 1;
            } elseif ($price && (float) $price->prod_price > $lastPrice) {
                $month['itemPriceHike'] += 1;
            } elseif ($price && (float) $price->prod_price < $lastPrice) {
                $month['itemPriceDown'] += 1;
            }
        }
    }

    private function getPreviousPrice($product, $month)
    {
        $latestPrice = $product->load(['prices' => function($price) use ($month) {
            $price->where(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"), '<', $month)
            ->orderBy('created_at', 'desc')
            ->take(1);
        }]);

        $latestPrice = $product->prices->first(); 
        
        return $latestPrice ? (float) $latestPrice->prod_price : 0;
    }
}
