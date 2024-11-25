<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrParticular extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pr_particulars';

    protected $fillable = [
        'prod_id',
        'unitPrice',
        'unitMeasure',
        'qty',
        'revised_specs',
        'status',
        'pr_id',
        'conpar_id',
        'remarks',
        'updated_by',
    ];

    protected $cast = [
        'updated_by' => 'integer',
    ];

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PrTransaction::class, 'pr_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function ppmpController(): BelongsTo
    {
        return $this->belongsTo(PpmpConsolidated::class, 'conpar_id');
    }
}
