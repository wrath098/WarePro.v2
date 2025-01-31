<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'prod_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(ProductInventoryTransaction::class, 'prodInv_id');
    }
}
