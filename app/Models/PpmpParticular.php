<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class PpmpParticular extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ppmp_particulars';

    protected $fillable = [
        'qty_first',
        'qty_second',
        'tresh_first_qty',
        'tresh_second_qty',
        'released_qty',
        'prod_id',
        'price_id',
        'trans_id',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PpmpTransaction::class, 'trans_id');
    }
}
