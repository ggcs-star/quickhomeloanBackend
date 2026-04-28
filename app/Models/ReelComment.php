<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ReelComment extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'reel_comments';

    protected $fillable = [
        'reel_id',
        'user_id',
        'comment'
    ];

  
    public function reel()
    {
        return $this->belongsTo(Reel::class, 'reel_id');
    }
    public function user()
{
    return $this->belongsTo(\App\Models\User::class, 'user_id');
}
}