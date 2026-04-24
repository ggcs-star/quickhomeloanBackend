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

    public function modules()
    {
        return $this->hasMany(EducationModule::class, 'course_id');
    }
}