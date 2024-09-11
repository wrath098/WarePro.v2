<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Fund;

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
}