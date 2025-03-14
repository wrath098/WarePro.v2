<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArchiveInventory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'archive_inventories';

    protected $fillable = [
        'prod_id', 
        'qty', 
        'year', 
    ];
}
