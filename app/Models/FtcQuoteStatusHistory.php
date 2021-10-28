<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;

class FtcQuoteStatusHistory extends BaseModel
{
    use HasFactory;
    protected $table = 'ftc_quote_status_history';
    public $access = [

        'write' => [''],
        'update' => [''],
        'delete' => [''],
        'access' => [
            "pa" => [],
            "production_approval_manager" => [],
            "invoicing" => [ ],
            "advisor" => [ ],
            "admin" => [ ],
        ],
        "list" => [
            "pa" => ['id' , 'quote_status_id' , 'notes', 'created_by','created_at' ],
            "production_approval_manager" => ['id' , 'quote_status_id' , 'notes', 'created_by','created_at' ],
            "invoicing" => ['id' , 'quote_status_id' , 'notes', 'created_by','created_at' ],
            "advisor" => ['id' , 'quote_status_id' , 'notes', 'created_by' ,'created_at'],
            "admin" => ['id' , 'quote_status_id' , 'notes', 'created_by','created_at' ]
        ]
    ];

    public function quote_status_id()
    {
        return $this->hasOne(QuoteStatus::class, 'id', 'quote_status_id');
    }

    public function relations() {
         return ["quote_status_id"];
    }

    public function processGetDSL($filters) {
        return self::processGetBaseDSL($filters, false);
    }
}
