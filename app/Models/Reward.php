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
        return $this->belongsToMany(RewardCategory::class,'reward_category_mapping');
    }

    public function rewardTags()
    {
        return $this->belongsToMany(RewardTag::class,'reward_tag_mapping');
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function rewardTranslations()
    {
        return $this->hasMany(RewardTranslation::class);
    }

    // this is a recommended way to declare event handlers
    public static function boot() {
        parent::boot();

        static::deleting(function($reward) { 
            // before delete() method call this
             $reward->rewardCategories()->detach($reward->id);
             $reward->rewardTags()->detach($reward->id);
             $reward->rewardTranslations()->delete();
             // do the rest of the cleanup...
        });
    }
}
