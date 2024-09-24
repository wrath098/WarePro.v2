<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Fund;
use App\Models\ItemClass;
use App\Models\Product;

class ProductService 
{
    private function extractLastNumber(?string $stockNo): ?int
    {
        if ($stockNo) {
            preg_match('/-(\d+)$/', $stockNo, $matches);
            return $matches[1] ?? null;
        }
        return null;
    }

    private function formatStockNo(int $catId, int $itemCode, int $controlNo): string
    {
        return sprintf('%02d-%02d-%02d', $catId, $itemCode, $controlNo);
    }
    
    public function generateStockNo($id)
    {
        $itemClass = ItemClass::findOrFail($id); 
        $latestProduct  = $itemClass->products()->orderBy('prod_newNo', 'desc')->first();

        $lastStockNo  = $latestProduct ? $latestProduct->prod_newNo : null;
        $lastNumber = $this->extractLastNumber($lastStockNo);
        $controlNo = $lastNumber ? $lastNumber + 1 : 1;
        return $this->formatStockNo($itemClass->cat_id, $itemClass->item_code, $controlNo);
    }

    public function getActiveFunds()
    {
        return Fund::where('fund_status', 'active')->orderBy('fund_name')->get();
    }

    public function getActiveCategory()
    {
        return Category::where('cat_status', 'active')->orderBy('cat_code')->get();
    }

    public function getActiveItemclass()
    {
        return ItemClass::with('category')
        ->where('item_status', 'active')
        ->orderBy('cat_id', 'asc')
        ->orderBy('item_name', 'asc')
        ->paginate(10);
    }

    public function getLatestPrice($id)
    {
        $product = Product::findOrFail($id);
        $priceResult = $product->prices()->orderBy('created_at', 'desc')->first();
        return $priceResult->prod_price ?? null;
    }

    public function validateCategoryExistence($fundId, $code, $name)
    {
        $category = Category::where('fund_id', $fundId)
            ->where('cat_code', $code)
            ->where('cat_name', $name)
            ->first();
        return $category;
    }
}