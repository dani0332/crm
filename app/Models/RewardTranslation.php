<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RewardTranslation extends Model
{
    use HasFactory;
    protected $table = 'reward_translation';
    public $timestamps = false;
    
    public function reward()
    {
        return $this->belongsTo(Reward::class,'id','reward_id');
    }
}
