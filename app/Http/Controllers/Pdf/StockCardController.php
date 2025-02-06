<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\ProductInventoryTransaction;
use App\Services\ProductService;
use Illuminate\Http\Request;

class StockCardController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function generatePdf_StockCard(Request $request)
    {
        $query = $request->productDetails;
        $query['prodUnit'] = $this->productService->getProductUnit($query['prodId']);
        $inventoryTransactions = $this->getProductInventoryTransactions($query['prodId'], $query['startDate'], $query['endDate']);

        dump($query, $inventoryTransactions->toArray());
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
                    return [
                        'id' => $transaction->id,
                        'created' => $transaction->created_at->format('d-m-Y'),
                        'unit' => $productUnit,
                        'type' => ucfirst($transaction->type),
                        'qty' => $transaction->qty,
                        'adjustedTotalStock' => $transaction->current_stock,
                    ];
                });
    }
}
