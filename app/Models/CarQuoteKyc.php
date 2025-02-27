<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CarQuoteKyc extends BaseModel
{
    use HasFactory;

    protected $table = 'car_quote_kyc';
    public $access = [
        'write' => ['advisor', 'admin', 'oe'],
        'update' => ['advisor', 'admin', 'oe'],
        'delete' => ['admin'],
        'access' => [
            'pa' => [],
            'production_approval_manager' => [],
            'advisor' => ['car_quote_id', 'profession', 'organization', 'designation'],
            'oe' => ['car_quote_id', 'profession', 'organization', 'designation'],
            'admin' => ['car_quote_id', 'profession', 'organization', 'designation'],
            'invoicing' => [],
            'payment' => [],
        ],
        'list' => [
            'pa' => ['id', 'car_quote_id', 'profession', 'organization', 'designation'],
            'production_approval_manager' => ['id', 'car_quote_id', 'profession', 'organization', 'designation'],
            'advisor' => ['id', 'car_quote_id', 'profession', 'organization', 'designation'],
            'oe' => ['id', 'car_quote_id', 'profession', 'organization', 'designation'],
            'admin' => ['id', 'car_quote_id', 'profession', 'organization', 'designation'],
            'invoicing' => ['id', 'car_quote_id', 'profession', 'organization', 'designation'],
            'payment' => ['id', 'car_quote_id', 'profession', 'organization', 'designation'],
        ],
    ];
}
