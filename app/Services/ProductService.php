<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Fund;
use App\Models\ItemClass;

class ProductService 
{

    public function generateStockNo($id)
    {
        $itemClass = ItemClass::findOrFail($id); 
        $latestProduct  = $itemClass->products()->orderBy('prod_newNo', 'desc')->first();
        $latestNo = $latestProduct ? $latestProduct->prod_newNo : 0;
        $controlNo = $latestNo + 1;
        return str_pad($controlNo, 2, '0', STR_PAD_LEFT);
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

    public function validateCategoryExistence($fundId, $code, $name)
    {
        $category = Category::where('fund_id', $fundId)
                            ->where('cat_code', $code)
                            ->where('cat_name', $name)
                            ->first();
        return $category;
    }
}