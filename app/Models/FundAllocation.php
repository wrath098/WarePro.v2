<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FundAllocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'description', 
        'semester', 
        'amount', 
        'status', 
        'cap_id', 
        'cat_id',
    ];

    public function generalFund(): BelongsTo
    {
        return $this->belongsTo(CapitalOutlay::class, 'cap_id');
    }

}
