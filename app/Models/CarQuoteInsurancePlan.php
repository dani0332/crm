<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CarQuoteInsurancePlan extends BaseModel
{
    use HasFactory;

    protected $table = 'car_quote_insurance_plan';
    public $access = [

        'write' => ['advisor', 'admin', 'oe'],
        'update' => ['advisor', 'admin', 'oe'],
        'delete' => ['advisor', 'admin', 'oe'],
        'access' => [
            'pa' => ['code', 'text', 'text_ar', 'is_deleted'],
            'invoicing' => ['code', 'text', 'text_ar', 'is_deleted'],
            'advisor' => ['code', 'text', 'text_ar', 'is_deleted'],
            'oe' => ['code', 'text', 'text_ar', 'is_deleted'],
            'admin' => ['code', 'text', 'text_ar', 'is_deleted'],
        ],
        'list' => [
            'pa' => ['id', 'code', 'text', 'text_ar'],
            'advisor' => ['id', 'code', 'text', 'text_ar'],
            'oe' => ['id', 'code', 'text', 'text_ar'],
            'admin' => ['id', 'code', 'text', 'text_ar'],
            'invoicing' => ['id', 'code', 'text', 'text_ar'],
        ],
    ];

    public function relations()
    {
        return [];
    }
}
