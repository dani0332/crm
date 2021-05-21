<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RewardTag;

class RewardTagController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $rewardTags = RewardTag::all();
        return view('rewardtag.view',compact('rewardTags'));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('rewardtag.add');
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
            'text' => 'required|max:120',
            'text_ar' => 'required|max:120',
        ]);

        $rewardTag = new RewardTag();
        $rewardTag->text=  $request->text;
        $rewardTag->text_ar=  $request->text_ar;
        $rewardTag->is_active =  $request->is_active == 'on' ? 1 : 0; 
        $rewardTag->sort_order =  $request->sort_order;    
        $rewardTag->save();
        return back()
            ->with('success','Reward Tag has been stored');
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Rewardtag  $rewardTag
     * @return \Illuminate\Http\Response
     */
    public function show(RewardTag $rewardTag)
    {
        return view('rewardtag.show',compact('rewardTag'));
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\RewardTag  $rewardTag
     * @return \Illuminate\Http\Response
     */
    public function edit(RewardTag $rewardTag)
    {
        return view('rewardtag.edit',compact('rewardTag'));
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\RewardTag  $rewardTag
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Rewardtag $rewardTag)
    {
      
        $this->validate($request,[
            'text' => 'required|max:120',
            'text_ar' => 'required|max:120',
        ]);
        $rewardTag->text=  $request->text;
        $rewardTag->text_ar=  $request->text_ar;
        $rewardTag->is_active =  $request->is_active == 'on' ? 1 : 0; 
        $rewardTag->sort_order =  $request->sort_order;    
        $rewardTag->save();
        return back()
            ->with('success','Reward Tag has been Updated');
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Rewardtag  $rewardTag
     * @return \Illuminate\Http\Response
     */
    public function destroy(RewardTag $rewardTag)
    {
        $rewardTag->delete();
        return back()
            ->with('success','reward Tag has been Deleted');
    }
}
