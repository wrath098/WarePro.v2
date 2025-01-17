<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class IarParticular extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'iar_particulars';

    protected $fillable = [
        'item_no',
        'stock_no',
        'unit',
        'description',
        'qty',
        'price',
        'status',
        'date_expiry',
        'remarks',
        'air_id',
        'updated_by',
    ];

    protected $casts = [
        'updated_by' => 'integer',
    ];

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function iarTransaction(): BelongsTo
    {
        return $this->belongsTo(IarTransaction::class, 'air_id');
    }
}
