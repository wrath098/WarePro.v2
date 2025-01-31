<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductInventoryTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'product_inventory_transactions';

    protected $fillable = [
        'type',
        'qty',
        'stock_qty',
        'notes',
        'ref_no',
        'prod_id',
        'date_expiry',
        'dispatch',
        'current_stock',
        'prodInv_id',
        'created_by',
    ];

    protected $casts = [
        'created_by' => 'integer',
    ];

    public function mainInventory(): BelongsTo
    {
        return $this->belongsTo(ProductInventory::class, 'id');
    }
}
