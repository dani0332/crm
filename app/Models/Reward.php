<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    use HasFactory;
    protected $table = 'reward';
    public $timestamps = false;

    public function sgetCreatedAtAttribute( $value ) {
        $this->attributes['created_at'] = (new Carbon($value))->format('Y-m-d');
    }


    public function rewardCategories()
    {
        return $this->belongsToMany(RewardCategory::class,'reward_category_mapping','reward_id','reward_category_id');
    }

    public function rewardTags()
    {
        return $this->belongsToMany(RewardTag::class,'reward_tag_mapping','reward_id','reward_tag_id');
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class,'id','partner_id');
    }

    public function rewardTranslations()
    {
        return $this->hasMany(RewardTranslation::class);
    }
}
