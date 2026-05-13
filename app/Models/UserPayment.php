<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class UserPayment extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'user_payments';

    protected $fillable = [
        'user_id',
        'subscription_id',

        'status',

        // ✅ add this
        'amount',

        'start_date',
        'end_date'
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}