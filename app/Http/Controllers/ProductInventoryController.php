<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\ProductInventoryTransaction;
use App\Models\RisTransaction;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        return Inertia::render('Inventory/Index', ['inventory' => $products]);
    }

    public function showStockCard(Request $request)
    {
        $productList = $this->productListInventory();
        return Inertia::render('Inventory/StockCard', ['productList' => $productList]);
    }

    public function getProductInventoryLogs(Request $request)
    {
        $query = $request->input('query');
        $inventoryTransactions = $this->getProductInventoryTransactions($query['prodId'], $query['startDate'], $query['endDate']);

       return response()->json(['data' => $inventoryTransactions], 200);
    }

    public function productListInventory()
    {
        $productList = [];
        $groupOfProductId = ProductInventory::pluck('prod_id')->all();

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
                ->where(function($query) use ($productId, $fromDate, $toDate) {
                    $query->where('prod_id', $productId)
                        ->whereBetween('created_at', [$fromDate, $toDate]);
                })
                ->orderBy('created_at', 'desc')
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
                });
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
            $status = 'Reorder';
            $qty_on_stock = 0;
            $reorder_level = 0;

            if(($product->deleted_at || $product->prod_status != 'active')  && ($currentStock <= 0)) {
                return null;
            } else {
                if ($inventory) {
                    $qty_on_stock = $inventory->qty_on_stock;
                    $reorder_level = $inventory->reorder_level;
                    $status = $qty_on_stock <= $reorder_level ? 'Reorder' : 'Available';
                }
            
                return [
                    'id' => $inventory ? $inventory->id : null,
                    'stockNo' => $product->prod_newNo,
                    'prodDesc' => $product->prod_desc,
                    'prodUnit' => $product->prod_unit,
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
            ->select('ris_no', 'office_id')
            ->find($id);

        if($risTransaction) {
            $requestee = $risTransaction->requestee()->select('office_code')->first();
            return [
                'risNo' => $risTransaction->ris_no,
                'officeCode' => $requestee ? $requestee->office_code : null,
            ];
        }

        return [];
    }
}
