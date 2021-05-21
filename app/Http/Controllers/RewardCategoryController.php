<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RewardCategory;

class RewardCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $rewardCategories = RewardCategory::all();
        return view('rewardcategory.view',compact('rewardCategories'));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('rewardcategory.add');
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

        $rewardCategory = new RewardCategory();
        $rewardCategory->text=  $request->text;
        $rewardCategory->text_ar=  $request->text_ar;
        $rewardCategory->is_active =  $request->is_active == 'on' ? 1 : 0; 
        $rewardCategory->sort_order =  $request->sort_order;    
        $rewardCategory->save();
        return back()
            ->with('success','Reward Category has been stored');
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\RewardCategory  $rewardCategory
     * @return \Illuminate\Http\Response
     */
    public function show(RewardCategory $rewardCategory)
    {
        return view('rewardcategory.show',compact('rewardCategory'));
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\RewardCategory  $rewardCategory
     * @return \Illuminate\Http\Response
     */
    public function edit(RewardCategory $rewardCategory)
    {
        return view('rewardcategory.edit',compact('rewardCategory'));
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\RewardCategory  $rewardCategory
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, RewardCategory $rewardCategory)
    {
      
        $this->validate($request,[
            'text' => 'required|max:120',
            'text_ar' => 'required|max:120',
        ]);
        $rewardCategory->text=  $request->text;
        $rewardCategory->text_ar=  $request->text_ar;
        $rewardCategory->is_active =  $request->is_active == 'on' ? 1 : 0; 
        $rewardCategory->sort_order =  $request->sort_order;    
        $rewardCategory->save();
        return back()
            ->with('success','Reward Category has been Updated');
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\RewardCategory  $rewardCategory
     * @return \Illuminate\Http\Response
     */
    public function destroy(RewardCategory $rewardCategory)
    {
        $rewardCategory->delete();
        return back()
            ->with('success','rewardCategory has been Deleted');
    }
}
