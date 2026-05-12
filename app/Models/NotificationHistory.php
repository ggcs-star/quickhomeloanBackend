<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class NotificationHistory extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'notification_histories';

    protected $fillable = [
        'title',
        'body',
        'image_url',
        'send_to',
        'user_ids',
        'user_names',
        'total_receivers',
        'success_count',
        'fail_count',
        'sent_by',
        'sent_at'
    ];

    protected $casts = [
        'user_ids' => 'array',
        'user_names' => 'array',
        'sent_at' => 'datetime',
    ];
}