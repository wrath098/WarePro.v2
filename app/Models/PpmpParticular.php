<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PpmpParticular extends Model
{
    use HasFactory;

    protected $table = 'ppmp_particulars';

    protected $fillable = [
        'qty_first',
        'qty_second',
        'prod_id',
        'price_id',
        'trans_id',
        'remarks',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PpmpTransaction::class, 'trans_id');
    }
}
