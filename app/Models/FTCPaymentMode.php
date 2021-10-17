<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;

class FTCPaymentMode extends BaseModel
{
    use HasFactory;
    protected $table = 'payment_modes';
    public $access = [

        'write' => [''],
        'update' => [''],
        'delete' => [''],
        'access' => [
            "pa" => [],
            "invoicing" => [ ],
            "advisor" => [],
            "admin" => [ ],
        ],
        "list" => [
            "pa" => ['id' , 'name'  ],
            "invoicing" => ['id' , 'name'  ],
            "advisor" => ['id' , 'name'  ],
            "admin" => ['id' , 'name'  ],
        ]
    ];

    public function relations() {
        return [];
    }

    public function processGetDSL($filters) {
        return self::processGetBaseDSL($filters, false);
    }
}
