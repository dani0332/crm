<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RewardTag extends Model
{
    use HasFactory;
    protected $table = 'reward_tag';

    public function Rewards()
    {
        return $this->belongsToMany(Reward::class,'reward_tag_mapping','reward_id','reward_tag_id');
    }
}
