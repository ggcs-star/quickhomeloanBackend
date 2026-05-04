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
        'event_date',
        'start_time',
        'end_time',
        'is_all_day',
        'reminder_time',
        'repeat_type',     // none | daily | weekly | monthly | yearly
        'is_completed',    // for task
        'is_notified',
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'reminder_time' => 'datetime',
        'is_all_day' => 'boolean',
        'is_completed' => 'boolean',
        'is_notified' => 'boolean',
    ];
}