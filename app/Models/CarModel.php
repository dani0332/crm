<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CarModel extends BaseModel
{
    use HasFactory;

    protected $table = 'car_model';
    public $access = [

        'write' => ['advisor', 'oe'],
        'update' => ['advisor', 'oe'],
        'delete' => ['advisor', 'oe'],
        'access' => [
            'pa' => ['code', 'text', 'car_make_code'],
            'advisor' => ['code', 'text', 'car_make_code'],
            'oe' => ['code', 'text', 'car_make_code'],
            'admin' => ['code', 'text', 'car_make_code'],
            'invoicing' => ['code', 'text', 'car_make_code'],

        ],
        'list' => [
            'pa' => ['id', 'code', 'text', 'car_make_code'],
            'advisor' => ['id', 'code', 'text', 'car_make_code'],
            'oe' => ['id', 'code', 'text', 'car_make_code'],
            'admin' => ['id', 'code', 'text', 'car_make_code'],
            'invoicing' => ['code', 'text', 'car_make_code'],
        ],
    ];

    public function relations()
    {
        return [];
    }

    /**
     * @return void
     */
    public function scopeActive($query)
    {
        $query->where('is_active', 1);
    }

    /**
     * @return void
     */
    public function scopeActiveWithCode($query, $code)
    {
        $query->where('is_active', 1)->where('car_make_code', $code);
    }
}
