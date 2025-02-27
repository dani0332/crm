<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class CarAddOnOption extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'car_addon_option';
    protected $guarded = ['id'];
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
            'pa' => ['id', 'value', 'addon_id', 'price'],
            'advisor' => ['id', 'value', 'addon_id', 'price'],
            'oe' => ['id', 'value', 'addon_id', 'price'],
            'admin' => ['id', 'value', 'addon_id', 'price'],
            'invoicing' => ['id', 'value', 'addon_id', 'price'],
        ],
    ];

    public function addon_id()
    {
        return $this->hasOne(CarAddOn::class, 'id', 'addon_id')->select(['id', 'text', 'description']);
    }

    public function relations()
    {
        return ['addon_id'];
    }
}
