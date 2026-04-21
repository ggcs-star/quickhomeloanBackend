<?php
namespace App\Models;
use MongoDB\Laravel\Eloquent\Model;

class EducationModule extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'education_modules';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'order',
        'status',
        'image',
        'color_code'
    ];
     protected $appends = ['image_url'];
    public function contents()
    {
        return $this->hasMany(EducationContent::class, 'module_id');
    }

    public function getImageUrlAttribute()
    {
        if (!$this->image)
            return null;

        return rtrim(config('app.admin_domain'), '/') . '/storage/' . $this->image;
    }
}