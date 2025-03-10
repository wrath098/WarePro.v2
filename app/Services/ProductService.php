<?php

namespace App\Services;

use App\Models\CapitalOutlay;
use App\Models\Category;
use App\Models\Fund;
use App\Models\ItemClass;
use App\Models\PpmpConsolidated;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\ProductPpmpException;
use App\Models\ProductPrice;

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
        $latestProduct  = $itemClass->products()->withTrashed()->orderBy('prod_newNo', 'desc')->first();

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

    public function getDeactivatedCategory()
    {
        return Category::withTrashed()->where('cat_status', 'deactivated')->orderBy('cat_code')->get();
    }

    public function getActiveItemclass()
    {
        return ItemClass::with('category')
        ->where('item_status', 'active')
        ->orderBy('cat_id', 'asc')
        ->orderBy('item_name', 'asc')
        ->paginate(10);
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

    public function getAllProduct_FundModel()
    {
        $funds = Fund::withTrashed()->with([
            'categories' => function ($query) {
                $query->withTrashed()->select('id', 'fund_id', 'cat_code', 'cat_name');
            },
            'categories.items' => function ($query) {
                $query->withTrashed()->select('id', 'cat_id', 'item_code', 'item_name');
            },
            'categories.items.products' => function ($query) {
                $query->withTrashed()->select('id', 'item_id', 'prod_newNo', 'prod_desc', 'prod_unit', 'prod_oldNo');
            }
        ])
        ->get(['id', 'fund_name']);

        return $funds;
    }

    public function getAllProduct_Category()
    {
        $categories = Category::withTrashed()->with([
            'items' => function ($query) {
                $query->withTrashed()->select('id', 'cat_id', 'item_code', 'item_name');
            },
            'items.products' => function ($query) {
                $query->withTrashed()->select('id', 'item_id', 'prod_newNo', 'prod_desc', 'prod_unit', 'prod_oldNo');
            }
        ])
        ->get(['id', 'cat_code', 'cat_name']);

        return $categories;
    }

    public function getAllActiveProduct_Category()
    {
        $categories = Category::with([
            'items' => function ($query) {
                $query->where('item_status', 'active')->select('id', 'cat_id', 'item_code', 'item_name');
            },
            'items.products' => function ($query) {
                $query->where('prod_status', 'active')->select('id', 'item_id', 'prod_newNo', 'prod_desc', 'prod_unit', 'prod_oldNo');
            },
        ])
        ->get(['id', 'cat_code', 'cat_name']);

        return $categories;
    }

    public function getCategoryName($id)
    {
        $categoryName = Category::withTrashed()->findOrFail($id);
        return $categoryName ? $categoryName->cat_name : null;
    }

    public function getItemName($id)
    {
        $itemName = ItemClass::withTrashed()->findOrFail($id);
        return $itemName ? $itemName->item_name : null;
    }

    public function getItemId(int $catId, int $itemCode)
    {
        $itemId = ItemClass::where('cat_id', $catId)
                ->where('item_code', $itemCode)
                ->first();

        return $itemId ? $itemId->id : null;
    }

    public function getLatestPrice($id)
    {
        $product = Product::withTrashed()->findOrFail($id);
        $priceResult = $product->prices()->orderBy('created_at', 'desc')->first();
        return $priceResult->prod_price ?? null;
    }

    public function getLatestPriceId($id)
    {
        $priceResult = ProductPrice::findOrFail($id);
        return $priceResult->prod_price ?? null;
    }

    public function getLatestPriceIdentification($id)
    {
        $product = Product::findOrFail($id);
        $priceResult = $product->prices()->orderBy('created_at', 'desc')->first();
        return $priceResult->id ?? null;
    }

    public function getFiveLatestPrice($id)
    {
        $product = Product::withTrashed()->findOrFail($id);
        $priceResult = $product->prices()->orderBy('created_at', 'desc')->limit(5)->get();
        return $priceResult->id ?? null;
    }

    public function getProductName($id)
    {
        $product = Product::withTrashed()->findOrFail($id);
        $productName = $product->prod_desc;
        return $productName ?? '';
    }

    public function getProductUnit($id)
    {
        $product = Product::withTrashed()->findOrFail($id);
        $productUnit = $product->prod_unit;
        return $productUnit ?? '';
    }

    public function getProductCode($id)
    {
        $product = Product::withTrashed()->findOrFail($id);
        $productCode = $product->prod_newNo;
        return $productCode ?? '';
    }

    public function getProductInConso($prodId, $transId) {
        $product = PpmpConsolidated::where('prod_id', $prodId)
        ->where('trans_id', $transId)
        ->first();

        return $product ?? null;
    }

    public function getCapitalOutlay($year, $fundId)
    {
        $capital = CapitalOutlay::where('year', $year)
            ->where('fund_id', $fundId)
            ->first();
        return optional($capital)->amount;
    }

    public function getCapitalOutlayContingency($year, $fundId, $sem)
    {
        $capital = CapitalOutlay::with(['allocations' => function($query) use ($sem) {
            $query->where('description', 'Contingency')
                ->where('semester', $sem);
        }])->where('year', $year)
            ->where('fund_id', $fundId)
            ->first();

        $amount = $capital ? $capital->allocations->first()->amount : null;
        return $amount;
    }

    public function getProductReorderPoint(int $id)
    {
        $product = ProductInventory::withTrashed()->where('prod_id', $id)->first();
        return $product ? $product->reorder_level : 0;
    }

    public function validateCategoryExistence($fundId, $name)
    {
        $category = Category::where('fund_id', $fundId)
            ->whereRaw('LOWER(cat_name) = ?', [strtolower($name)])
            ->first();
        return $category;
    }

    public function validateProductExcemption($prodId)
    {
        return ProductPpmpException::where('prod_id', $prodId)
            ->where('status', 'active')
            ->exists();
    }

    public function validateProduct($id)
    {
        $column = preg_match("/^\d{4}$/", $id) ? 'prod_oldNo' : 
                (preg_match("/^\d{2}-\d{2}-\d{2,4}$/", $id) ? 'prod_newNo' : null);

        if($column) {
            $product = Product::where($column, $id)
                        ->where('prod_status', 'active')
                        ->first();

            if ($product) {
                return [
                    'prodId' => $product->id,
                    'priceId' => $this->getLatestPriceIdentification($product->id),
                ];
            }
        }
        return false;
    }

    public function verifyProductIfActive(int $id): bool
    {
        return Product::where('id', $id)->where('prod_status', 'active')->exists();
    }
}