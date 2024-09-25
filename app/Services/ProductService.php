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

    public function getFiveLatestPrice($id)
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

    public function getActiveProduct_FundModel()
    {
        $funds = Fund::with([
            'categories' => function ($query) {
                $query->where('cat_status', 'active')->select('id', 'fund_id', 'cat_code', 'cat_name');
            },
            'categories.items' => function ($query) {
                $query->where('item_status', 'active')->select('id', 'cat_id', 'item_code', 'item_name');
            },
            'categories.items.products' => function ($query) {
                $query->where('prod_status', 'active')->select('id', 'item_id', 'prod_newNo', 'prod_desc', 'prod_unit', 'prod_oldNo');
            }
        ])
        ->where('fund_status', 'active')
        ->get(['id', 'fund_name']);

        return $funds;
    }
}