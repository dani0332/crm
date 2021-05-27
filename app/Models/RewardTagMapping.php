<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class RewardTagMapping extends Model implements AuditableContract
{
    use HasFactory,Auditable;
    protected $table = 'reward_tag_mapping';
    public $timestamps = false;
}
