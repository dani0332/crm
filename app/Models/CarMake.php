<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarMake extends BaseModel
{
    use HasFactory;

    protected $table = 'car_make';
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
            'invoicing' => ['code', 'text'],
        ],
    ];

    public function relations()
    {
        return [];
    }

    public function scopeActive($query)
    {
        $query->where('is_active', 1);
    }

    public function scopeActiveWithId($query, $id)
    {
        $query->whereId($id)->where('is_active', 1);
    }

    /**
     * get all models of a car function
     */
    public function carModels(): HasMany
    {
        return $this->hasMany(
            CarModel::class,
            'car_make_code',
            'code'
        );
    }
}
