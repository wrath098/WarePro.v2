<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\IarTransaction;
use App\Models\ItemClass;
use App\Models\PpmpTransaction;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\ProductInventoryTransaction;
use App\Models\ProductPrice;
use App\Models\PrTransaction;
use App\Models\RisTransaction;
use App\Services\ProductService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(): Response
    {
        $products = Product::where('prod_status', 'active')->count();
        $currentYear = Carbon::now()->addYear()->format('Y');

        $core = [
            'product' => $products,
            'reOrder' => $this->countReorderProductItem(),
            'iarTransaction' => $this->countIarPendingTransactions(),
            'risTransaction' => $this->countRisTransactionThisDay(),
            'prTransaction'=> $this->countDraftedPurchaseRequest(),
            'availableProductItem' => $this->countAvailableProductItem(),
            'expiringProduct' => $this->countExpiringProductItem(),
            'outOfStockProducts' => $this->countOutOfStockProductItem(),
            'ppmpTransactions' => $this->countPpmpUploaded($currentYear),
            'consolidatedPpmp' => $this->checkApprovedApp($currentYear),
        ];

        $priceEvaluation = $this->monitorProductItemsPriceStatus() ?? collect();
    
        $monthlyPriceEvaluation = [
            'labels' => $priceEvaluation->pluck('month')->toArray() ?? [],
            'datasets' => [
                'hikes' => $priceEvaluation->pluck('itemPriceHike')->toArray() ?? [],
                'stable' => $priceEvaluation->pluck('itemPriceStable')->toArray() ?? [],
                'drops' => $priceEvaluation->pluck('itemPriceDown')->toArray() ?? []
            ]
        ];
        
        return Inertia::render('Dashboard', ['core' => $core, 'monthlyPriceEvaluation' => $monthlyPriceEvaluation]);
    }

    public function getFastMovingItems()
    {
        $year = Carbon::now()->startOfYear();

        $queryIssuances = Cache::remember('fast_moving_items_' . $year, 10, function() use ($year) {
            return RisTransaction::select('prod_id', 
                                            DB::raw('count(*) as count'),  
                                            DB::raw('SUM(qty) as total_quantity'),
                                            DB::raw('SUM(qty) / count(*) as avg_qty_per_transaction'))
            ->where('created_at', '>=', $year)
            ->groupBy('prod_id')
            ->orderBy(DB::raw('SUM(qty) / count(*)'), 'desc')
            ->take(20)
            ->with('productDetails')
            ->get()
            ->map(function($product) {
                $productDetails = $product->productDetails;
                return [
                    'code' => $productDetails->prod_newNo,
                    'description' => $productDetails->prod_desc,
                    'unit' => $productDetails->prod_unit,
                    'count' => $product->count,
                    'total_quantity' => $product->total_quantity,
                    'average' => $product->avg_qty_per_transaction,
                ];
            });
        }) ?? [];

        return response()->json(['products' => $queryIssuances]);
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
        $months = $this->pastMonths(5);
        
        Product::select('id')
            ->where('prod_status', 'active')
            ->chunk(200, function ($products) use (&$months) {
                foreach ($products as $product) {
                    $this->processProductPrices($product, $months);
                }
            }
        );

        return $months;
    }

    private function processProductPrices(Product $product, Collection &$months): void
    {   
        $month['itemPriceHike'] = $month['itemPriceHike'] ?? 0;
        $month['itemPriceDown'] = $month['itemPriceDown'] ?? 0;
        $month['itemPriceStable'] = $month['itemPriceStable'] ?? 0;

        $months = $months->map(function ($month) use ($product) {
            $currentPrice = ProductPrice::where('prod_id', $product->id)
                ->whereYear('created_at', Carbon::parse($month['date'])->year)
                ->whereMonth('created_at', Carbon::parse($month['date'])->month)
                ->latest('created_at')
                ->first();
    
            $lastPrice = $this->getPreviousPrice($product, $month['date']);
    
            if (!$currentPrice) {
                $month['itemPriceStable'] += 1;
            } else {
                $currentPriceValue = (float)($currentPrice->prod_price ?? 0);
                $lastPriceValue = (float)($lastPrice ?? 0);
        
                if ($currentPriceValue > $lastPriceValue) {
                    $month['itemPriceHike'] += 1;
                } elseif ($currentPriceValue < $lastPriceValue) {
                    $month['itemPriceDown'] += 1;
                } else {
                    $month['itemPriceStable'] += 1;
                }
            }

            return $month;
        });
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

    private function countPpmpUploaded(string $year, string $type = 'individual')
    {
        $transactions =  PpmpTransaction::where('ppmp_year', $year)
            ->where('ppmp_type', $type)
            ->count();
        
        return [
            'count' => $transactions,
            'year' => $year
        ];
    }

    private function checkApprovedApp(string $year, string $type = 'consolidated', string $status = 'approved')
    {
        $exists =  PpmpTransaction::where('ppmp_type', $type)
            ->where('ppmp_year', $year)
            ->where('ppmp_status', $status)
            ->exists();
        
        $status = $exists ? 'Exists' : 'Empty';
        
        return [
            'status' => $status ,
            'year' => $year
        ];
    }

    public function filterByDate(Request $request)
    {
        $products = Product::where('prod_status', 'active')->count();
        $currentYear = Carbon::now()->addYear()->format('Y');

        $core = [
            'product' => $products,
            'reOrder' => $this->countReorderProductItem(),
            'iarTransaction' => $this->countIarPendingTransactions(),
            'risTransaction' => $this->countRisTransactionThisDay(),
            'prTransaction'=> $this->countDraftedPurchaseRequest(),
            'availableProductItem' => $this->countAvailableProductItem(),
            'expiringProduct' => $this->countExpiringProductItem(),
            'outOfStockProducts' => $this->countOutOfStockProductItem(),
            'ppmpTransactions' => $this->countPpmpUploaded($currentYear),
            'consolidatedPpmp' => $this->checkApprovedApp($currentYear),
        ];
    }
}
