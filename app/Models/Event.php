<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Event extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'events';

    protected $fillable = [
        'user_id',
        'type',            // event | task
        'title',
        'description',

        'start_datetime',  // event start
        'end_datetime',    // event end

        'is_all_day',

        'reminder_time',
        'repeat_type',     // none | daily | weekly | monthly | yearly

        'is_completed',
        'is_notified',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'reminder_time' => 'datetime',
        'is_all_day' => 'boolean',
        'is_completed' => 'boolean',
        'is_notified' => 'boolean',
    ];
}