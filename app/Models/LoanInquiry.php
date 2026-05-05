<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class LoanInquiry extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'loan_inquiries';

    protected $fillable = [
        'full_name',
        'mobile_number',
        'email',
        'loan_purpose',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}