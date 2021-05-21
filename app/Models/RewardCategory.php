<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RewardCategory extends Model
{
    use HasFactory;
    protected $table = 'reward_category';

    public function Rewards()
    {
        return $this->belongsToMany(Reward::class,'reward_category_mapping','reward_id','reward_category_id');
    }
}
