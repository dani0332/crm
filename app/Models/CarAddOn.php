<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class CarAddOn extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'car_addon';
    protected $fillable = ['text', 'text_ar', 'type', 'created_at', 'updated_at', 'code'];
    public $access = [

        'write' => ['advisor', 'oe'],
        'update' => ['advisor', 'oe'],
        'delete' => ['advisor', 'oe'],
        'access' => [
            'pa' => [],
            'advisor' => [],
            'oe' => [],
            'admin' => [],
            'invoicing' => [],
        ],
        'list' => [
            'pa' => ['id', 'text', 'description'],
            'advisor' => ['id', 'text', 'description'],
            'oe' => ['id', 'text', 'description'],
            'admin' => ['id', 'text', 'description'],
            'invoicing' => ['id', 'text', 'description'],
        ],
    ];

    public function relations()
    {
        return [];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function carAddonOptions()
    {
        return $this->hasMany(CarAddOnOption::class, 'addon_id');
    }
}
