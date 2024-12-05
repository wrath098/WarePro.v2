<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductInventory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'product_inventories';

    protected $fillable = [
        'qty_on_stock',
        'qty_physical_count',
        'qty_purchase',
        'qty_issued',
        'location',
        'reorder_level',
        'prod_id',
        'updated_by',
    ];
}
