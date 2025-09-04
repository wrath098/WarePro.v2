<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PpmpTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ppmp_transactions';

    protected $fillable = [
        'ppmp_code',
        'ppmp_type',
        'description',
        'office_ppmp_ids',
        'baseline_adjustment_type',
        'init_qty_adjustment',
        'price_adjustment',
        'qty_adjustment',
        'tresh_adjustment',
        'ppmp_status',
        'ppmp_version',
        'ppmp_year',
        'office_id',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function requestee(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'office_id');
    }

    public function particulars(): HasMany
    {
        return $this->hasMany(PpmpParticular::class, 'trans_id');
    }

    public function consolidated(): HasMany
    {
        return $this->hasMany(PpmpConsolidated::class, 'trans_id');
    }

    public function purchaseRequests(): HasMany
    {
        return $this->hasMany(PrTransaction::class, 'trans_id');
    }
}
