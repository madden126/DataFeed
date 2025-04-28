<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'gtin',
        'language',
        'title',
        'picture',
        'description',
        'price',
        'stock'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'date_add' => 'datetime',
        'date_upd' => 'datetime'
    ];

    public $timestamps = false;
}
