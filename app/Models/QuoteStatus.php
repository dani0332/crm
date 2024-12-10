<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class QuoteStatus extends BaseModel
{
    protected $table = 'quote_status';

    use HasFactory;

    public $access = [

        'write' => ['admin'],
        'update' => ['admin'],
        'delete' => ['admin'],
        'access' => [
            'pa' => ['id', 'code', 'text'],
            'advisor' => ['id', 'code', 'text'],
            'oe' => ['id', 'code', 'text'],
            'admin' => ['id', 'code', 'text'],
            'invoicing' => ['id', 'code', 'text'],

        ],
        'list' => [
            'pa' => ['id', 'code', 'text'],
            'advisor' => ['id', 'code', 'text'],
            'oe' => ['id', 'code', 'text'],
            'admin' => ['id', 'code', 'text'],
            'invoicing' => ['id', 'code', 'text'],
        ],
    ];

    public function scopeWithActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function relations()
    {
        return [];
    }

    public function quoteStatusMap()
    {
        return $this->hasMany(QuoteStatusMap::class);
    }
}
