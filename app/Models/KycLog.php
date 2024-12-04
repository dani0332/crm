<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class KycLog extends BaseModel
{
    use HasFactory;

    protected $table = 'kyc_logs';
    public $access = [
        'write' => ['admin'],
        'update' => ['admin'],
        'delete' => ['admin'],
        'access' => [
            'pa' => [],
            'production_approval_manager' => [],
            'advisor' => [],
            'oe' => [],
            'admin' => [],
            'invoicing' => [],
            'payment' => [],
        ],
        'list' => [
            'pa' => ['id', 'results', 'quote_request_id', 'results_found'],
            'production_approval_manager' => ['id', 'results', 'quote_request_id', 'results_found'],
            'advisor' => ['id', 'results', 'quote_request_id', 'results_found'],
            'oe' => ['id', 'results', 'quote_request_id', 'results_found'],
            'admin' => ['id', 'results', 'quote_request_id', 'results_found'],
            'invoicing' => ['id', 'results', 'quote_request_id', 'results_found'],
            'payment' => ['id', 'results', 'quote_request_id', 'results_found'],
        ],
    ];
}
