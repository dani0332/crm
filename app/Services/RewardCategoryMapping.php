<?php

namespace App\Services;

use App\Models\RewardCategoryMapping as RewardCategoryMappingModel;

class RewardCategoryMapping
{
    public function mapRewardCategory($rewardCategory, $reward)
    {
        $rewardCategoeyMapping = new RewardCategoryMappingModel;
        $rewardCategoeyMapping->reward_category_id = $rewardCategory;
        $rewardCategoeyMapping->reward_id = $reward;
        $rewardCategoeyMapping->save();

        return true;
    }

    public function unMapRewardCategory($reward)
    {
        $rewardCategoeyMapping = RewardCategoryMappingModel::where('reward_id', $reward)->delete();
    }
}
