<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RisTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ris_transactions';

    protected $fillable = [
        'ris_no',
        'qty',
        'unit',
        'remarks',
        'issued_to',
        'prod_id',
        'office_id',
        'ppmp_ref_no',
        'created_by',
        'attachment',
        'created_at',
    ];

    protected $cast = [
        'created_by' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function productDetails(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'prod_id');
    }

    public function requestee(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'office_id');
    }

    public function releasedBasis(): BelongsTo
    {
        return $this->belongsTo(PpmpParticular::class, 'ppmp_ref_no');
    }
}
