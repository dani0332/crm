<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class RewardTranslation extends Model implements AuditableContract
{
    use HasFactory,Auditable;
    protected $table = 'reward_translation';
    public $timestamps = false;
    
    public function reward()
    {
        return $this->belongsTo(Reward::class,'id','reward_id');
    }
}
