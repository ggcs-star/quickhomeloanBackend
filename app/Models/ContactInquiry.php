<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ContactInquiry extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'contact_inquiries';

    protected $fillable = [
        'full_name',
        'email',
        'subject',
        'message',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}