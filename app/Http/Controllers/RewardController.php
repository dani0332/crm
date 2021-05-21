<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reward;
use App\Models\Partner;
use App\Models\RewardCategory;
use App\Models\RewardTag;
use App\Services\RewardCategoryMapping;
use App\Services\RewardTagMapping;



class RewardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $rewards = Reward::all();
        return view('reward.view',compact('rewards'));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $partners = Partner::all();
        $rewardCategories = RewardCategory::all();
        $rewardTags = RewardTag::all();
        return view('reward.add',compact('partners','rewardTags','rewardCategories'));
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request,[
            'coupon_code' => 'required',
            'partner_id' => 'required',
            'discount' => 'required',
            'start_date'=> 'required',
            'end_date'=> 'required',
        ]);
        $reward = new Reward();
        $reward->coupon_code  = $request->coupon_code;
        $reward->partner_id = $request->partner_id;
        $reward->discount = $request->discount;
        $reward->start_date = $request->start_date;
        $reward->end_date = $request->end_date;
        $reward->is_flat_discount =$request->is_flat_discount == 'on' ? 1 : 0;
        $reward->is_active = $request->is_active == 'on' ? 1 : 0;
        $reward->save();
        if(isset($request->reward_categories)){
            foreach($request->reward_categories as $rewardCategory){
                $rewardCategoryMapping = new RewardCategoryMapping;
                $rewardCategoryMapping->mapRewardCategory($rewardCategory,$reward->id);
            }
        }
        if(isset($request->reward_tags)){
            foreach($request->reward_tags as $rewardTag){
                $rewardTagMapping = new RewardTagMapping;
                $rewardTagMapping->mapRewardTag($rewardTag,$reward->id);
            }
        }
        return back()
            ->with('success','reward has been stored');
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Reward  $reward
     * @return \Illuminate\Http\Response
     */
    public function show(Reward $reward)
    {
        //
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Reward  $reward
     * @return \Illuminate\Http\Response
     */
    public function edit(Reward $reward)
    {
        $partners = Partner::all();
        $rewardCategories = RewardCategory::all();
        $rewardTags = RewardTag::all();
        return view('reward.edit',compact('partners','reward','rewardCategories','rewardTags'));
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Reward  $reward
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, reward $reward)
    {
        $this->validate($request,[
            'coupon_code' => 'required|max:120',
            'partner_id' => 'required|max:120',
            'discount' => 'required|max:120',
            'start_date'=> 'required|max:120',
            'end_date'=> 'required|max:120',
        ]);
        $reward->coupon_code  = $request->coupon_code;
        $reward->partner_id = $request->partner_id;
        $reward->discount = $request->discount;
        $reward->start_date = $request->start_date;
        $reward->end_date = $request->end_date;
        $reward->is_flat_discount =$request->is_flat_discount == 'on' ? 1 : 0;
        $reward->is_active = $request->is_active == 'on' ? 1 : 0;
        $reward->save();
        if(isset($request->reward_categories)){
            $rewardCategoryMapping = new RewardCategoryMapping;
            $rewardCategoryMapping->unMapRewardCategory($reward->id);
            foreach($request->reward_categories as $rewardCategory){
                $rewardCategoryMapping = new RewardCategoryMapping;
                $rewardCategoryMapping->mapRewardCategory($rewardCategory,$reward->id);
            }
        }

        if(isset($request->reward_tags)){
            $rewardTagMapping = new RewardTagMapping;
            $rewardTagMapping->unMapRewardTag($reward->id);
            foreach($request->reward_tags as $rewardTag){
                $rewardTagMapping = new RewardTagMapping;
                $rewardTagMapping->mapRewardTag($rewardTag,$reward->id);
            }
        }
        return back()
            ->with('success','reward has been stored');
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Reward  $reward
     * @return \Illuminate\Http\Response
     */
    public function destroy(Reward $reward)
    {
        $reward->delete();
        return back()
            ->with('success','reward has been stored');
    }
}
