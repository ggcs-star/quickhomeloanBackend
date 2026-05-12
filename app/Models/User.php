<?php

namespace App\Models;

use MongoDB\Laravel\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $connection = 'mongodb';
    protected $collection = 'users'; 

    protected $fillable = [
        'full_name',
        'email',
        'mobile_number',
        'password',
        'city',
        'employment_type',
        'annual_income',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
   

public function fcmTokens()
{
    return $this->hasMany(FcmToken::class, 'user_id');
}

public function sentNotifications()
{
    return $this->hasMany(NotificationHistory::class, 'sent_by');
}
}
