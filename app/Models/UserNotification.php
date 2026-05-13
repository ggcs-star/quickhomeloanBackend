<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class UserNotification extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'user_notifications';

    protected $fillable = [
        'user_id',
        'title',
        'body',
        'image_url',
        'is_read',
        'notification_history_id',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];
}