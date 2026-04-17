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
        'status'
    ];
    public function contents()
    {
        return $this->hasMany(EducationContent::class, 'module_id');
    }
}