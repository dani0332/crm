<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class RewardCategory extends Model implements AuditableContract
{
    use HasFactory, Auditable;
    protected $table = 'reward_category';

    public function Rewards()
    {
        return $this->belongsToMany(Reward::class, 'reward_category_mapping', 'reward_id', 'reward_category_id');
    }
}
