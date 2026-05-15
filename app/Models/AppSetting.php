<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class AppSetting extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'app_settings';

    protected $fillable = [
        'app_name',
        'app_logo',
        'splash_logo',
        'header_logo',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}