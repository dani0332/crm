<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class FTCPaymentMode extends BaseModel
{
    use HasFactory;

    protected $table = 'payment_modes';
    public $access = [

        'write' => [''],
        'update' => [''],
        'delete' => [''],
        'access' => [
            'pa' => [],
            'invoicing' => [],
            'payment' => [],
            'advisor' => [],
            'oe' => [],
            'admin' => [],
        ],
        'list' => [
            'pa' => ['id', 'name'],
            'invoicing' => ['id', 'name'],
            'payment' => ['id', 'name'],
            'advisor' => ['id', 'name'],
            'oe' => ['id', 'name'],
            'admin' => ['id', 'name'],
        ],
    ];

    public function relations()
    {
        return [];
    }
}
