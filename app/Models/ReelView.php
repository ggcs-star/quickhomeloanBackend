<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ReelView extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'reel_views';

    protected $fillable = [
        'reel_id',
        'user_id',
        'ip_address'
    ];

   
    protected $casts = [
        'reel_id' => 'string',
        'user_id' => 'string',
    ];

    public function reel()
    {
        return $this->belongsTo(Reel::class, 'reel_id');
    }
}