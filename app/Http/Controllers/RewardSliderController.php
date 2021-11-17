<?php

namespace App\Http\Controllers;

use App\Http\Requests\RewardSliderRequest;
use App\Models\RewardSlider;
use Illuminate\Http\Request;
use DataTables;
use Arr;

class RewardSliderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, RewardSlider $rewardSlider, Datatables $datatables)
    {
        if ($request->ajax()) {

            return $datatables::of($rewardSlider::query()->orderBy('sort_order','asc'))
                ->addIndexColumn()
                ->make(true);
        }
        return view('rewardslider.view');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('rewardslider.add');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(RewardSliderRequest $request, RewardSlider $rewardSlider)
    {
        if ($request->file()) {
            $fileName = time() . '_' . $request->image->getClientOriginalName();
            $filePath = $request->file('image')->storeAs('/rewards-slider/', $fileName, 'azure');
            $rewardSlider->image = $fileName;
        }

        $rewardSlider->create(Arr::except($request->validated(), ['image']) + [ 'image' => $fileName]);

        if(isset($request->return_to_view)) {
            return redirect("rewards/reward-sliders/".$rewardSlider->id)->with('success', 'Rewards Slider image has been stored');
        }
        return redirect()->back()->with('success', 'Rewards Slider image has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\RewardSlider  $rewardSlider
     * @return \Illuminate\Http\Response
     */
    public function show(RewardSlider $rewardSlider)
    {
        return view('rewardslider.show', compact('rewardSlider'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\RewardSlider  $rewardSlider
     * @return \Illuminate\Http\Response
     */
    public function edit(RewardSlider $rewardSlider)
    {
        return view('rewardslider.edit', compact('rewardSlider'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\RewardSlider  $rewardSlider
     * @return \Illuminate\Http\Response
     */
    public function update(RewardSliderRequest $request, RewardSlider $rewardSlider)
    {
        if ($request->file()) {
            $fileName = time() . '_' . $request->image->getClientOriginalName();
            $filePath = $request->file('image')->storeAs('/rewards-slider/', $fileName, 'azure');
            $rewardSlider->image = $fileName;
        }
        else {
            $fileName = $rewardSlider->image;
        }

        //dd($request->validated());
        $rewardSlider->update(Arr::except($request->validated(), ['image']) + [ 'image' => $fileName]);

        if(isset($request->return_to_view)) {
            return redirect("rewards/reward-sliders/".$rewardSlider->id)->with('success', 'Rewards Slider image has been updated');
        }
        return redirect()->back()->with('success', 'Rewards Slider image has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RewardSlider  $rewardSlider
     * @return \Illuminate\Http\Response
     */
    public function destroy(RewardSlider $rewardSlider)
    {
        $rewardSlider->delete();
        return redirect()->route('reward-sliders.index')->with('message','Rewards Slider has been deleted');
    }
}
