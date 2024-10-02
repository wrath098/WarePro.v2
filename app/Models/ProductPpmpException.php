<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPpmpException extends Model
{
    use HasFactory;

    protected $table = 'product_ppmp_exceptions';

    protected $fillable = [
        'year',
        'prod_id',
        'status',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'prod_id');
    }
}
