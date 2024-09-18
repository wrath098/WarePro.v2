<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Fund;
use App\Models\ItemClass;

class ProductService 
{
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
        return ItemClass::where('item_status', 'active')->orderBy('cat_id')->paginate(10);
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