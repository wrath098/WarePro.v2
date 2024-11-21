<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PpmpConsolidated extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ppmp_consolidateds';

    protected $fillable = [
        'qty_first',
        'qty_second',
        'prod_id',
        'price_id',
        'trans_id',
        'created_by', 
        'updated_by',
    ];

    protected $casts = [
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];
    
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PpmpTransaction::class, 'trans_id');
    }

    public function purchaseRequest(): HasMany
    {
        return $this->hasMany(PrParticular::class, 'conpar_id');
    }
}
