<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Config;

class Customer extends Model implements AuditableContract
{
    use HasFactory, Auditable;
    protected $table = 'customer';
    protected $guarded = [];  

    public function nationality()
    {
        return $this->hasOne(Nationality::class, 'id', 'nationality_id');
    }

    public function Rewards()
    {
        return $this->belongsToMany(Reward::class, 'reward_customer_viewed', 'reward_id', 'customer_id');
    }

    public function MyAlfredUsers()
    {
        return $this->hasOne(MyAlFredUser::class);
    }


    public function getCreatedAtAttribute($table)
    {
        $dateTimeFormat = Config::get('constants.datetime_format');
        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($dateTimeFormat);
    }
    public function getUpdatedAtAttribute($table)
    {
        $dateTimeFormat = Config::get('constants.datetime_format');
        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($dateTimeFormat);
    }
}
