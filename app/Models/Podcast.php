<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Podcast extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'podcasts';

    public $timestamps = true;

    protected $fillable = [
        'title',
        'thumbnail',
        'youtube_url',
        'episodes_count',
        'total_duration',
        'is_active'
    ];

    protected $casts = [
        'episodes_count' => 'integer',
        'is_active' => 'boolean'
    ];
}