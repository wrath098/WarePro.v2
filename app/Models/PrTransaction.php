<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pr_transactions';

    protected $fillable = [
        'pr_no',
        'qty_adjustment',
        'pr_status',
        'trans_id',
        'created_by',
        'updated_by',
    ];

    protected $cast = [
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

    public function particulars(): HasMany
    {
        return $this->hasMany(PrTransaction::class, 'pr_id');
    }
}
