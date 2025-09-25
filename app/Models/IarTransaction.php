<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class IarTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'iar_transactions';

    protected $fillable = [
        'sdi_iar_id',
        'iar_no',
        'po_no',
        'supplier',
        'date',
        'status',
        'remark',
        'pr_id',
        'updated_by',
    ];

    protected $casts = [
        'updated_by' => 'integer',
    ];

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function iarParticulars(): HasMany
    {
        return $this->hasMany(IarParticular::class, 'air_id');
    }
}
