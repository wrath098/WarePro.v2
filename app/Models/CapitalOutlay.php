<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CapitalOutlay extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'capital_outlays';

    protected $fillable = [
        'year', 
        'cluster', 
        'amount', 
        'status', 
        'fund_id', 
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

    public function allocations(): HasMany
    {
        return $this->hasMany(FundAllocation::class, 'cap_id');
    }
}
