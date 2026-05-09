<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class CommunityPost extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'community_posts';
    public $timestamps = true;
    protected $fillable = [
        'user_id',
        'user_name',
        'user_photo',
        'content',
        'likes',
        'likes_count',
        'shares',
        'shares_count',
        'saved_by',
        'saved_count',
        'comments_count'
    ];

    protected $casts = [
        'likes' => 'array',
        'shares' => 'array',
        'saved_by' => 'array',
        'likes_count' => 'integer',
        'shares_count' => 'integer',
        'saved_count' => 'integer',
        'comments_count' => 'integer'
    ];

    

    public function comments()
    {
        return $this->hasMany(CommunityComment::class, 'post_id');
    }
}