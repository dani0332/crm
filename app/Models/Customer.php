<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Customer extends Model implements AuditableContract
{
    use HasFactory, Auditable;
    protected $table = 'customer';

    public function nationality()
    {
        return $this->hasOne(Nationality::class, 'id', 'nationality_id');
    }

    public function Rewards()
    {
        return $this->belongsToMany(Reward::class, 'reward_customer_viewed', 'reward_id', 'customer_id');
    }
}
