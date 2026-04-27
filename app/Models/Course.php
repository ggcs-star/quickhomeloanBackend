<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Course extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'courses';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'image',
        'status',
        'order',
    ];
    protected $appends = ['image_url'];
    public function modules()
    {
        return $this->hasMany(EducationModule::class, 'course_id');
    }

    public function getImageUrlAttribute()
    {
        if (!$this->image)
            return null;

        return rtrim(config('app.admin_domain'), '/') . '/storage/' . $this->image;
    }
}