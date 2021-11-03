<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Auth;

class CarQuotePolicy extends BaseModel
{
    use HasFactory;
    protected $table = 'car_quote_policy';
    public $access = [
        'write'  => ['advisor'],
        'update' => ['advisor'],
        'delete' => [ 'advisor'],
        'access' => [
            "pa" => [],
            "production_approval_manager" => [],
            "advisor" => [ 'car_quote_id', 'quote_number', 'policy_number', 'issue_date', 'start_date', 'end_date' ],
            "admin" => [ 'car_quote_id', 'quote_number', 'policy_number', 'issue_date', 'start_date', 'end_date' ],
            "invoicing" => []
        ],
        "list" => [
            "pa" => ['id', 'car_quote_id', 'quote_number', 'policy_number', 'issue_date', 'start_date', 'end_date' ],
            "production_approval_manager" => ['id', 'car_quote_id', 'quote_number', 'policy_number', 'issue_date', 'start_date', 'end_date' ],
            "advisor" => ['id', 'car_quote_id', 'quote_number', 'policy_number', 'issue_date', 'start_date', 'end_date' ],
            "admin" => ['id', 'car_quote_id', 'quote_number', 'policy_number', 'issue_date', 'start_date', 'end_date' ],
            "invoicing" => ['id', 'car_quote_id', 'quote_number', 'policy_number', 'issue_date', 'start_date', 'end_date' ]
        ]
    ];

    public function relations() {
        return [];
    }

    public function processGetDSL($filters, $request) {
        return self::processGetBaseDSL($filters, false);
    }
}
