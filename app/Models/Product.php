<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'prod_newNo',
        'prod_desc',
        'prod_unit',
        'prod_status',
        'prod_remarks',
        'prod_oldNo',
        'item_id',
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

    public function itemClass(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
