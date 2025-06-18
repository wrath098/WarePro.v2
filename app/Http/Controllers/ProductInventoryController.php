<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\ProductInventoryTransaction;
use App\Models\RisTransaction;
use App\Services\ProductService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductInventoryController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(): Response
    {
        $products = $this->filterProductsWithInventory();
        $countOutOfStock = $products->filter(function($transaction) {
            return $transaction['status'] === 'Out of Stock';
        })->count();

        $countAvailable = $products->filter(function($transaction) {
            return $transaction['status'] === 'Available';
        })->count();

        $countReorder = $products->filter(function($transaction) {
            return $transaction['status'] === 'Re-order';
        })->count();

        return Inertia::render('Inventory/Index', [
            'inventory' => $products,
            'countOutOfStock' => $countOutOfStock,
            'countAvailable' => $countAvailable,
            'countReorder' => $countReorder,
        ]);
    }

    public function showStockCard()
    {
        return Inertia::render('Inventory/StockCard');
    }

    public function getProductInventoryLogs(Request $request)
    {
        $query = $request->input('query');
        $startDate = Carbon::parse($query['startDate']);
        $endDate = Carbon::parse($query['endDate']);

        $customEndDate = $endDate->setTime(23, 59, 59);
        
        $formattedStartDate = $startDate->format('Y-m-d H:i:s');
        $formattedEndDate = $customEndDate->format('Y-m-d H:i:s');

        $inventoryTransactions = $this->getProductInventoryTransactions($query['prodId'], $formattedStartDate, $formattedEndDate);

       return response()->json(['data' => $inventoryTransactions], 200);
    }

    public function getMonthlyInventory(Request $request)
    {   
        $date = Carbon::createFromFormat('Y-m', $request->input('query'));
        $query_year = $date->year;
        $query_month= $date->month;

        $start_date = Carbon::createFromDate($query_year, 1, 1)->startOfDay();
        $limit_date = Carbon::createFromDate($query_year, $query_month, 1)->endOfMonth()->endOfDay();

        $products = Product::with([
            'inventory' => function ($query) {
                $query->select('id', 'prod_id', 'qty_physical_count');
            }, 
            'inventoryTransactions' => function ($query) use ($start_date, $limit_date) {
                $query->withTrashed()
                    ->select('id', 'type', 'qty', 'prod_id', 'created_at')
                    ->whereBetween('created_at', [$start_date, $limit_date])
                    ->orderBy('created_at', 'asc');
            }])
            ->where('prod_status', 'active')
            ->get();

        $newProductArray = [];

        foreach ($products as $product) {
            $currentStock = $product->inventory ? $product->inventory->qty_physical_count : 0;

            foreach ($product->inventoryTransactions as $transaction){
                if ($transaction->type == 'issuance') {
                    $currentStock -= $transaction->qty;
                } elseif ($transaction->type == 'purchase') {
                    $currentStock += $transaction->qty;
                } else {
                    continue;
                }
            }

            if($product->prod_status == 'active' || ($product->prod_status == 'deactivated' && $currentStock > 0)) {
                $newProductArray[] = [
                    'id' => $product->id,
                    'newStockNo' => $product->prod_newNo,
                    'description' => $product->prod_desc,
                    'unit' => $product->prod_unit,
                    'oldStockNo' => $product->prod_oldNo,
                    'currentInventory' => number_format($currentStock, 0, '.', ','),
                ];
            }
        }

        return response()->json(['data' => $newProductArray], 200);
    }

    public function productListInventory()
    {
        $productList = [];
        $groupOfProductId = ProductInventory::pluck('prod_id')->filter(function ($value) {
            return !is_null($value);
        })->all();

        foreach($groupOfProductId as $productId) {
            $product = $this->getProductDetails($productId);
            $productList[] = $product;
        }

        return $productList;
    }

    public function searchProductItem(Request $request) 
    {
        $query = $request->input('query');

        $products = Product::withTrashed()
            ->where(function($product) use ($query) {
                $product->where('prod_newNo', 'LIKE', '%' . $query . '%')
                    ->orWhere('prod_desc', 'LIKE', '%' . $query . '%');
            })
            ->get()
            ->map(fn($product) => [
                'prodId' => $product->id,
                'prodDesc' => $product->prod_desc,
                'prodUnit' => $product->prod_unit,
                'prodStockNo' => $product->prod_newNo,
            ]);
            
        return response()->json(['data' => $products]);
    }

    private function getProductDetails($productId)
    {
        $productDetail = Product::withTrashed()->findOrFail($productId);
        
        return [
            'prodId' => $productDetail->id,
            'prodDesc' => $productDetail->prod_desc,
            'prodUnit' => $productDetail->prod_unit,
            'prodStockNo' => $productDetail->prod_newNo,
        ];
    }

    private function getProductInventoryTransactions($productId, $fromDate, $toDate)
    {
        $productUnit = $this->productService->getProductUnit($productId);

        return ProductInventoryTransaction::withTrashed()
                ->where('prod_id', $productId)
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->latest('created_at')
                ->get()
                ->map(function($transaction) use ($productUnit) {
                    $issuanceDetails = '';
                    if ($transaction->type == 'issuance') {
                        $issuanceDetails = $this->getIssuanceTypeDetails($transaction->ref_no);
                    }
                    return [
                        'id' => $transaction->id,
                        'created' => $transaction->created_at->format('d-m-Y'),
                        'unit' => $productUnit,
                        'type' => ucfirst($transaction->type),
                        'qty' => $transaction->qty,
                        'adjustedTotalStock' => $transaction->current_stock,
                        'risNo' => $issuanceDetails ? $issuanceDetails['risNo'] : '',
                        'requestee' => $issuanceDetails ? $issuanceDetails['officeCode'] : '',
                    ];
                })->values();
    }

    private function fetchAllProductsWithQuantity()
    {
        return Product::withTrashed()->with('inventory')->get();
    }

    private function filterProductsWithInventory()
    {
        $products = $this->fetchAllProductsWithQuantity();

        $products = $products->map(function($product) {
            $inventory = $product->inventory;

            $currentStock = $inventory ? $inventory->qty_on_stock : 0;
            $status = 'Out of Stock';
            $qty_on_stock = 0;
            $reorder_level = 0;

            if(($product->deleted_at || $product->prod_status != 'active')  && ($currentStock <= 0)) {
                return null;
            } else {
                if ($inventory) {
                    $qty_on_stock = (int) $inventory->qty_on_stock;
                    $reorder_level = $inventory->reorder_level;

                    $status = $this->getStockStatus($qty_on_stock, $reorder_level);
                }
            
                return [
                    'id' => $inventory ? $inventory->id : null,
                    'stockNo' => $product->prod_newNo,
                    'prodDesc' => $product->prod_desc,
                    'prodUnit' => $product->prod_unit,
                    'reorderLevel' => $reorder_level,
                    'beginningBalance' => $inventory->qty_physical_count ?? 0,
                    'stockAvailable' => $qty_on_stock,
                    'purchases' => $inventory->qty_purchase ?? 0,
                    'issuances' => $inventory->qty_issued ?? 0,
                    'status' => $status,
                    'prodId' => $product->id
                ];
            }
        })->filter();

        return $products;
    }

    private function getIssuanceTypeDetails(int $id): array
    {
        $risTransaction = RisTransaction::withTrashed()
            ->select('ris_no', 'office_id', 'remarks')
            ->find($id);

        if($risTransaction) {
            $requestee = $risTransaction->requestee()->select('office_code')->first();
            return [
                'risNo' => $risTransaction->ris_no,
                'officeCode' => $requestee ? $requestee->office_code : 'Others',
                'remarks' => $risTransaction->remarks ?? '',
            ];
        }

        return [];
    }

    private function getStockStatus(int $qty_on_stock, int $reorder_level)
    {
        if ($qty_on_stock === 0) {
            return 'Out of Stock';
        } elseif ($qty_on_stock <= $reorder_level) {
            return 'Re-order';
        }
        return 'Available';
    }
}
