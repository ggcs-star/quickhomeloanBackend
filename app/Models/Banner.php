<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Banner extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'banners';

    protected $fillable = [
        'title',
        'image',
        'link',
        'order',
        'status',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        if (!$this->image)
            return null;

        return rtrim(config('app.admin_domain'), '/') . '/storage/' . $this->image;
    }
}