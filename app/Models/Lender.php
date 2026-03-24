<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Lender extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'lenders';

   protected $fillable = [
        'name',
        'type',
        'logo',
        'logo_url',
        'products',
    ];

    protected $casts = [
        'products' => 'array',
    ];
}
