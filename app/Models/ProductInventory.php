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
        'qtyOnStock',
        'location',
        'reorderLevel',
        'prod_id',
        'updated_by',
    ];
}
