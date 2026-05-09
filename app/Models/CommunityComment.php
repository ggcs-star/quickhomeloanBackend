<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class CommunityComment extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'community_comments';

    public $timestamps = true;

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
        'likes' => 'array',
        'likes_count' => 'integer'
    ];

    public function post()
    {
        return $this->belongsTo(CommunityPost::class, 'post_id');
    }

    public function replies()
    {
        return $this->hasMany(CommunityComment::class, 'parent_id');
    }
}
