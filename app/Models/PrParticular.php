<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrParticular extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pr_particulars';

    protected $fillable = [
        'prod_id',
        'qty',
        'revised_specs',
        'status',
        'pr_id',
    ];

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PrTransaction::class, 'pr_id');
    }
}
