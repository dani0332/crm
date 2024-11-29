<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class KycStatus extends BaseModel
{
    use HasFactory;

    protected $table = 'kyc_statuses';
    public $access = [
        'write' => ['advisor'],
        'update' => ['advisor'],
        'delete' => ['advisor'],
        'access' => [
            'pa' => ['code', 'text'],
            'advisor' => ['code', 'text'],
            'admin' => ['code', 'text'],
            'invoicing' => ['code', 'text'],

        ],
        'list' => [
            'pa' => ['id', 'code', 'text'],
            'advisor' => ['id', 'code', 'text'],
            'admin' => ['id', 'code', 'text'],
            'invoicing' => ['id', 'code', 'text'],
        ],
    ];
}
