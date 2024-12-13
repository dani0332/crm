<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class VehicleType extends BaseModel
{
    use HasFactory;

    protected $table = 'vehicle_type';
    public $access = [

        'write' => ['advisor', 'oe'],
        'update' => ['advisor', 'oe'],
        'delete' => ['advisor', 'oe'],
        'access' => [
            'pa' => ['id', 'category', 'text', 'is_active'],
            'invoicing' => ['id', 'category', 'text', 'is_active'],
            'advisor' => ['id', 'category', 'text', 'is_active'],
            'oe' => ['id', 'category', 'text', 'is_active'],
            'admin' => ['id', 'category', 'text', 'is_active'],
        ],
        'list' => [
            'pa' => ['id', 'category', 'text', 'is_active'],
            'invoicing' => ['id', 'category', 'text', 'is_active'],
            'advisor' => ['id', 'category', 'text', 'is_active'],
            'oe' => ['id', 'category', 'text', 'is_active'],
            'admin' => ['id', 'category', 'text', 'is_active'],
        ],
    ];

    public function relations()
    {
        return [];
    }

    public function scopeWithActive($query)
    {
        return $query->where('is_active', 1);
    }
}
