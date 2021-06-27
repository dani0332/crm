<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Reward extends Model implements AuditableContract
{
    use HasFactory, Auditable;
    protected $table = 'reward';
    public $timestamps = false;

    public function sgetCreatedAtAttribute($value)
    {
        $this->attributes['created_at'] = (new Carbon($value))->format('Y-m-d');
    }

    public function rewardCategories()
    {
        return $this->belongsToMany(RewardCategory::class, 'reward_category_mapping');
    }

    public function rewardTags()
    {
        return $this->belongsToMany(RewardTag::class, 'reward_tag_mapping');
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function rewardTranslations()
    {
        return $this->hasMany(RewardTranslation::class);
    }

    public function rewardCustomers()
    {
        return $this->belongsToMany(Customer::class, 'reward_customer_viewed');
    }

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($reward) {

            $reward->rewardTranslations()->delete();
        });
    }
}
