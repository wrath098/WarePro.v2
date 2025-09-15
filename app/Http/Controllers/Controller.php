<?php

namespace App\Http\Controllers;

use App\Models\Fund;

abstract class Controller
{
    protected function getAllProducts_customType($customAdjustment)
    {
        $allProducts = collect();
        foreach($customAdjustment as $accountId => $value) {
                $fund = Fund::with([
                'categories' => function ($q) {
                    $q->where('cat_status', 'active')->select('id', 'fund_id');
                },
                'categories.items' => function ($q) {
                    $q->where('item_status', 'active')->select('id', 'cat_id', );
                },
                'categories.items.products' => function ($q) {
                    $q->where('prod_status', 'active')->select('id', 'item_id');
                }
            ])
            ->where('id', $accountId)
            ->where('fund_status', 'active')
            ->first(['id', 'fund_name']);

            $productIds = collect();
            if ($fund) {
                foreach ($fund->categories as $category) {
                    foreach ($category->items as $item) {
                        foreach ($item->products as $product) {
                            $productIds->push($product->id);
                        }
                    }
                }
                $allProducts->put($accountId, $productIds->unique()->values());
            }
        }

        return $allProducts;
    }

    protected function getAllFunProducts($customAdjustment)
    {
        $allProducts = collect();

        foreach ($customAdjustment as $accountId => $value) {
            $fund = Fund::with([
                'categories' => function ($q) {
                    $q->withTrashed()
                    ->select('id', 'fund_id');
                },
                'categories.items' => function ($q) {
                    $q->withTrashed()
                    ->select('id', 'cat_id');
                },
                'categories.items.products' => function ($q) {
                    $q->withTrashed()
                    ->select('id', 'item_id');
                },
            ])
            ->where('id', $accountId)
            ->first(['id', 'fund_name']);

            $productIds = collect();

            if ($fund) {
                foreach ($fund->categories as $category) {
                    foreach ($category->items as $item) {
                        foreach ($item->products as $product) {
                            $productIds->push($product->id);
                        }
                    }
                }

                $allProducts->put($accountId, $productIds->unique()->values());
            }
        }

        return $allProducts;
    }

    protected function getFundNames(array $fundIds)
    {
        return Fund::whereIn('id', $fundIds)->get()->pluck('fund_name');
    }
}
