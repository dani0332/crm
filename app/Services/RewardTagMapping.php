<?php 
namespace App\Services;
use App\Models\RewardTagMapping as RewardTagMappingModel;

class RewardTagMapping  {

    public function mapRewardTag($rewardTag,$reward){
        $rewardTagMapping = new RewardTagMappingModel;
        $rewardTagMapping->reward_Tag_id = $rewardTag;
        $rewardTagMapping->reward_id = $reward;
        $rewardTagMapping->save();
        return true;
    }


    public function unMapRewardTag($reward){
        $rewardTagMapping = RewardTagMappingModel::where('reward_id',$reward)->delete();
    }
}