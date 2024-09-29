<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PpmpTransaction extends Model
{
    use HasFactory;

    protected $table = 'ppmp_transactions';

    protected $fillable = [
        'ppmp_code',
        'ppmp_type',
        'price_adjustment',
        'qty_adjustment',
        'ppmp_status',
        'ppmp_remarks',
        'office_id',
    ];

    public function officeParticulars(): HasMany
    {
        return $this->hasMany(PpmpParticular::class, 'trans_id');
    }
    public function consolidatedParticular(): HasMany
    {
        return $this->hasMany(PpmpConsolidated::class, 'office_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'office_id');
    }
}
