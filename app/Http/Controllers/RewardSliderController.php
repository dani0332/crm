<?php

namespace App\Http\Controllers;

use App\Http\Requests\RewardSliderRequest;
use App\Models\RewardSlider;
use Illuminate\Http\Request;
use DataTables;

class RewardSliderController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:reward-sliders-list|reward-sliders-create|reward-sliders-edit|reward-sliders-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:reward-sliders-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:reward-sliders-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:reward-sliders-delete', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $rewardSliders = RewardSlider::select('*')->orderBy('sort_order','asc');
            return Datatables::of($rewardSliders)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return view('rewardslider.actions', compact('row'))->render();
                })
                ->rawColumns(['action'])
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
    public function store(RewardSliderRequest $request)
    {
        $rewardSlider = new RewardSlider();
        if ($request->file()) {
            $fileName = time() . '_' . $request->image->getClientOriginalName();
            $filePath = $request->file('image')->storeAs('/', $fileName, 'azure');
            $rewardSlider->image = $fileName;
        }
        $rewardSlider->link = $request->link;
        $rewardSlider->sort_order = $request->sort_order;
        $rewardSlider->is_active = $request->is_active == 'on' ? 1 : 0;
        $rewardSlider->save();

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
            $filePath = $request->file('image')->storeAs('/', $fileName, 'azure');
            $rewardSlider->image = $fileName;
        }
        $rewardSlider->link = $request->link;
        $rewardSlider->sort_order = $request->sort_order;
        $rewardSlider->is_active = $request->is_active == 'on' ? 1 : 0;
        $rewardSlider->save();

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
        //
    }
}
