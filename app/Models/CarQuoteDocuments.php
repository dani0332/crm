<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CarQuoteDocuments extends BaseModel
{
    use HasFactory;

    protected $table = 'car_quote_documents';
    public $access = [
        'write' => ['advisor', 'oe'],
        'update' => ['advisor', 'oe'],
        'delete' => ['advisor', 'oe'],
        'access' => [
            'pa' => ['code', 'text', 'text_ar'],
            'advisor' => ['code', 'text', 'text_ar'],
            'oe' => ['code', 'text', 'text_ar'],
            'admin' => ['code', 'text', 'text_ar'],
            'invoicing' => ['code', 'text', 'text_ar'],
        ],
        'list' => ['id', 'code', 'text', 'text_ar'],
    ];
}
