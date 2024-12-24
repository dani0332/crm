<?php

namespace App\Models;

class FtcDocument extends BaseModel
{
    protected $table = 'car_quote_ftc_documents';
    public $access = [
        'write' => ['advisor', 'admin', 'oe'],
        'update' => ['advisor', 'admin', 'oe'],
        'delete' => ['advisor', 'admin', 'oe'],
        'access' => [
            'pa' => ['file_name', 'document', 'car_quote_id'],
            'production_approval_manager' => ['file_name', 'document', 'car_quote_id'],
            'invoicing' => [],
            'payment' => ['file_name', 'document', 'car_quote_id'],
            'advisor' => ['file_name', 'document', 'car_quote_id'],
            'oe' => ['file_name', 'document', 'car_quote_id'],
            'admin' => ['file_name', 'document'],
        ],
        'list' => [
            'pa' => ['id', 'file_name', 'document'],
            'production_approval_manager' => ['id', 'file_name', 'document'],
            'invoicing' => ['id', 'file_name', 'document'],
            'payment' => ['id', 'file_name', 'document'],
            'advisor' => ['id', 'file_name', 'document'],
            'oe' => ['id', 'file_name', 'document'],
            'admin' => ['id', 'file_name', 'document'],
        ],
    ];

    public function document()
    {
        return $this->hasOne(CarQuoteDocuments::class, 'id', 'document')->select(['id', 'code', 'text']);
    }

    public function relations()
    {
        return ['document'];
    }
}
