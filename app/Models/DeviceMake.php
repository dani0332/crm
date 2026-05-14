<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceMake extends Model
{
    protected $table = 'device_make';

    public function deviceModels()
    {
        return $this->hasMany(DeviceModel::class, 'make_id', 'id');
    }

}
