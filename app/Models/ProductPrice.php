<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductPrice extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'product_prices';

    protected $fillable = [
        'prod_price',
        'prod_id'
    ];

    public function pricesBy(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'prod_id');
    }
}
