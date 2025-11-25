<?php

namespace App\Models;

use App\Traits\Optionable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Nationality extends BaseModel
{
    use HasFactory, Optionable;

    protected $table = 'nationality';
    public $access = [
        'write' => ['advisor', 'oe'],
        'update' => ['advisor', 'oe'],
        'delete' => ['advisor', 'oe'],
        'access' => [
            'pa' => ['code', 'text'],
            'advisor' => ['code', 'text'],
            'oe' => ['code', 'text'],
            'admin' => ['code', 'text'],
            'invoicing' => ['code', 'text'],

        ],
        'list' => [
            'pa' => ['id', 'code', 'text'],
            'advisor' => ['id', 'code', 'text'],
            'oe' => ['id', 'code', 'text'],
            'admin' => ['id', 'code', 'text'],
            'invoicing' => ['id', 'code', 'text'],
        ],
    ];
    public $casts = [
        'rsa_country_code' => 'string',
    ];

    public function delete()
    {
        $this->setKeysForSaveQuery($this->newModelQuery())->update(['is_deleted' => true]);

        return true;
    }

    public function scopeWithActive($query)
    {
        return $query->where('is_active', 1);
    }
}
