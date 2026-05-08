<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class CommunityComment extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'community_comments';

    protected $fillable = [
        'post_id',
        'user_id',
        'user_name',
        'user_photo',
        'comment',
        'is_admin_reply',
        'parent_id',
        'likes',
        'likes_count'
    ];

    protected $casts = [
        'is_admin_reply' => 'boolean',
        'likes' => 'array'
    ];
    public $post_id;
public $user_id;
public $user_name;
public $user_photo;
public $comment;
public $is_admin_reply;
public $parent_id;
public $likes;
public $likes_count;

    public function post()
    {
        return $this->belongsTo(CommunityPost::class, 'post_id');
    }

    public function replies()
    {
        return $this->hasMany(CommunityComment::class, 'parent_id');
    }
}