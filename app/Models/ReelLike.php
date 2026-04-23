<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ReelLike extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'reel_likes';

    protected $fillable = [
        'reel_id',
        'user_id'
    ];

    
    public function reel()
    {
        return $this->belongsTo(Reel::class, 'reel_id');
    }
}