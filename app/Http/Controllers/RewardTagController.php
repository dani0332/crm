<?php

namespace App\Http\Controllers;

use App\Models\RewardTag;
use DataTables;
use Illuminate\Http\Request;
use DB;

class RewardTagController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:reward-tags-list|reward-tags-create|reward-tags-edit|reward-tags-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:reward-tags-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:reward-tags-edit', ['only' => ['edit', 'update']]);
        //$this->middleware('permission:reward-tags-delete', ['only' => ['destroy']]);
        $this->middleware('permission:reward-tags-delete', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = RewardTag::select('*')->orderBy('sort_order','asc');
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return view('rewardtag.actions', compact('row'))->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('rewardtag.view');
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
        $this->validate($request, [
            'text' => 'required|max:120',
            'text_ar' => 'required|max:120',
            'sort_order' => 'required',
        ]);

        $rewardTag = new RewardTag();
        $rewardTag->text = $request->text;
        $rewardTag->text_ar = $request->text_ar;
        $rewardTag->is_active = $request->is_active == 'on' ? 1 : 0;
        $rewardTag->sort_order = $request->sort_order;
        $rewardTag->save();

        if(isset($request->return_to_view)) {
            return redirect("rewards/reward-tags/".$rewardTag->id)->with('success', 'Reward Tag has been stored');
        }
        return redirect()->back()->with('success', 'Reward Tag has been stored');
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Rewardtag  $rewardTag
     * @return \Illuminate\Http\Response
     */
    public function show(RewardTag $rewardTag)
    {
        return view('rewardtag.show', compact('rewardTag'));
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\RewardTag  $rewardTag
     * @return \Illuminate\Http\Response
     */
    public function edit(RewardTag $rewardTag)
    {
        return view('rewardtag.edit', compact('rewardTag'));
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
        $this->validate($request, [
            'text' => 'required|max:120',
            'text_ar' => 'required|max:120',
            'sort_order' => 'required',
        ]);
        $rewardTag->text = $request->text;
        $rewardTag->text_ar = $request->text_ar;
        $rewardTag->is_active = $request->is_active == 'on' ? 1 : 0;
        $rewardTag->sort_order = $request->sort_order;
        $rewardTag->save();

        if(isset($request->return_to_view)) {
            return redirect("rewards/reward-tags/".$rewardTag->id)->with('success', 'Reward Tag has been updated');
        }
        return redirect()->back()->with('success', 'Reward Tag has been updated');
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Rewardtag  $rewardTag
     * @return \Illuminate\Http\Response
     */
    public function destroy(RewardTag $rewardTag)
    {
        if($rewardTag->Rewards()->count()) {
            return redirect()->route('reward-tags.index')->with('message','Reward Tag is linked with Reward and cannot be deleted');
        }
        else {
            $rewardTag->delete();
            return redirect()->route('reward-tags.index')->with('message','Reward Tag has been deleted');
        }
    }
}
