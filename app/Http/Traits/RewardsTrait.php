<?php

namespace App\Http\Traits;

use App\Models\Partner;
use App\Models\RewardCategory;
use App\Models\RewardTag;

trait RewardsTrait
{
    public function getPartners()
    {
        return Partner::where('is_active', '=', 1)->orderBy('name', 'asc')->get();
    }

    public function getRewardCategory()
    {
        return RewardCategory::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
    }

    public function getRewardTag()
    {
        return RewardTag::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
    }
}
