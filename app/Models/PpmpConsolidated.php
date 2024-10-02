<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PpmpConsolidated extends Model
{
    use HasFactory;

    protected $table = 'ppmp_consolidateds';

    protected $fillable = [
        'qty_first',
        'qty_second',
        'prod_id',
        'price_id',
        'trans_id',
    ];

    public function transactionNo(): BelongsTo
    {
        return $this->belongsTo(PpmpTransaction::class, 'trans_id');
    }
}
