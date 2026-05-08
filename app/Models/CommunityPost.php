<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class CommunityPost extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'community_posts';

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
        'saved_by' => 'array'
    ];

    // Yeh properties add karo red line hatane ke liye
    public $user_id;
    public $user_name;
    public $user_photo;
    public $content;
    public $likes;
    public $likes_count;
    public $shares;
    public $shares_count;
    public $saved_by;
    public $saved_count;
    public $comments_count;

    public function comments()
    {
        return $this->hasMany(CommunityComment::class, 'post_id');
    }
}