<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class RewardTag extends Model implements AuditableContract
{
    use HasFactory, Auditable;
    protected $table = 'reward_tag';

    public function Rewards()
    {
        return $this->belongsToMany(Reward::class, 'reward_tag_mapping', 'reward_id', 'reward_tag_id');
    }
}
