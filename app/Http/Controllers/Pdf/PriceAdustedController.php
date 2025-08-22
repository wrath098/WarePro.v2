<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;

class PriceAdustedController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function generate_priceAdjusted(Request $request)
    {
        $adjustment = (int) $request->adjustment / 100;

        $products = Product::where('prod_status', 'active')
            ->get()
            ->map(function($product) use ($adjustment) {
                $latestPrice = $this->productService->getLatestPrice($product->id);
                $adjustedPrice = (float) $latestPrice * $adjustment;
                return [
                    'id' => $product->id,
                    'newCode' => $product->prod_newNo,
                    'desc' => $product->prod_desc,
                    'unit' => $product->prod_unit,
                    'oldCode' => $product->prod_oldNo,
                    'price' => number_format($adjustedPrice, 2, '.', ',')
                ];
            });

        dd($products->all());
    }
}
