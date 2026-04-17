<?php
namespace App\Models;
use MongoDB\Laravel\Eloquent\Model;

class EducationContent extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'education_contents';

    protected $fillable = [
        'module_id',
        'title',
        'slug',
        'type',
        'file',
        'thumbnail',
        'duration',
        'description',
        'order',
        'status'
    ];
    protected $appends = ['file_url', 'thumbnail_url'];

    public function module()
    {
        return $this->belongsTo(EducationModule::class, 'module_id');
    }


    public function getFileUrlAttribute()
    {
        if (!$this->file)
            return null;

        return rtrim(config('app.admin_domain'), '/') . '/storage/' . $this->file;
    }

    public function getThumbnailUrlAttribute()
    {
        if (!$this->thumbnail)
            return null;

        return rtrim(config('app.admin_domain'), '/') . '/storage/' . $this->thumbnail;
    }
}