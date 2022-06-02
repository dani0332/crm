<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;

class QuoteStatus extends BaseModel
{
    protected $table = 'quote_status';
    use HasFactory;

    public $access = [

        'write' => ['admin'],
        'update' => ['admin'],
        'delete' => ['admin'],
        'access' => [
            "pa" => ['id', 'code', 'text'],
            "advisor" => [ 'id', 'code', 'text'],
            "oe" => [ 'id', 'code', 'text'],
            "admin" => [ 'id', 'code', 'text' ],
            "invoicing" => [ 'id', 'code', 'text']

        ],
        "list" => [
            "pa" => ['id', 'code', 'text' ],
            "advisor" => [ 'id', 'code', 'text'],
            "oe" => [ 'id', 'code', 'text'],
            "admin" => [ 'id', 'code', 'text'],
            "invoicing" => [ 'id', 'code', 'text']
        ]
    ];

    public function relations() {
        return [];
    }

    public function processGetDSL($filters) {
        return self::processGetBaseDSL($filters, false);
    }

    public function quoteStatusMap()
    {
        return $this->hasMany(quoteStatusMap::class, 'id', 'quote_status_id');
    }
}
