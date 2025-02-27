<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarQuoteAMLStatusLookup extends Model
{
    use HasFactory;

    protected $table = 'car_quote_aml_status_lookup';
    public $access = [
        'write' => ['admin'],
        'update' => ['admin'],
        'delete' => ['admin'],
        'access' => [
            'pa' => [],
            'advisor' => [],
            'oe' => [],
            'admin' => ['code', 'text'],
            'invoicing' => [],

        ],
        'list' => [
            'pa' => ['id', 'code', 'text'],
            'advisor' => ['id', 'code', 'text'],
            'oe' => ['id', 'code', 'text'],
            'admin' => ['id', 'code', 'text'],
            'invoicing' => ['code', 'text'],
        ],
    ];
}
