<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CarQuoteEmailUniqueLink extends BaseModel
{
    use HasFactory;

    protected $table = 'car_quote_email_unique_link';
    public $access = [
        'write' => ['advisor', 'admin', 'oe'],
        'update' => ['advisor', 'admin', 'oe'],
        'delete' => ['admin'],
        'access' => [
            'pa' => [],
            'advisor' => ['car_quote_id', 'hash', 'status'],
            'oe' => ['car_quote_id', 'hash', 'status'],
            'admin' => ['car_quote_id', 'hash', 'status'],
            'invoicing' => [],

        ],
        'list' => [
            'pa' => ['car_quote_id', 'status'],
            'advisor' => ['car_quote_id', 'status'],
            'oe' => ['car_quote_id', 'status'],
            'admin' => ['car_quote_id', 'hash', 'status'],
            'invoicing' => ['car_quote_id', 'status'],
        ],
    ];
}
