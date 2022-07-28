<?php

namespace App\Http\Controllers;

use App\Http\Requests\RewardRequest;
use App\Http\Traits\RewardsTrait;
use App\Models\Reward;
use App\Models\RewardTranslation;
use App\Services\RewardCategoryMapping;
use App\Services\RewardTagMapping;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RewardController extends Controller
{
    use RewardsTrait;

    public function __construct()
    {
        $this->middleware('permission:rewards-list|rewards-create|rewards-edit|rewards-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:rewards-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:rewards-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:rewards-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Reward::select('reward.*', 'partner.name as partner')
            ->leftjoin('partner', 'reward.partner_id', 'partner.id')->orderBy('reward.start_date', 'desc');

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return view('reward.actions', compact('row'))->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('reward.view');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $partners = $this->getPartners();
        $rewardCategories = $this->getRewardCategory();
        $rewardTags = $this->getRewardTag();

        return view('reward.add', compact('partners', 'rewardTags', 'rewardCategories'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(RewardRequest $request)
    {
        // if($request->is_active == 'on'){
        //     throw ValidationException::withMessages(['is_active' => 'There is no translation against this reward please create one first']);
        // }
        $reward = new Reward();
        $reward->coupon_code = $request->coupon_code;
        $reward->partner_id = $request->partner_id;
        $reward->discount = $request->discount;
        $reward->start_date = $request->start_date;
        $reward->end_date = $request->end_date;
        $reward->is_flat_discount = $request->is_flat_discount == 'on' ? 1 : 0;
        $reward->viewed_count = 0;
        $reward->viewed_count_unique = 0;
        $reward->save();
        if (isset($request->reward_categories)) {
            foreach ($request->reward_categories as $rewardCategory) {
                $rewardCategoryMapping = new RewardCategoryMapping;
                $rewardCategoryMapping->mapRewardCategory($rewardCategory, $reward->id);
            }
        }
        if (isset($request->reward_tags)) {
            foreach ($request->reward_tags as $rewardTag) {
                $rewardTagMapping = new RewardTagMapping;
                $rewardTagMapping->mapRewardTag($rewardTag, $reward->id);
            }
        }
        if (isset($request->return_to_view)) {
            return redirect('rewards/reward/'.$reward->id)->with('success', 'Reward has been stored');
        }

        return redirect()->back()->with('success', 'Reward has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Reward  $reward
     * @return \Illuminate\Http\Response
     */
    public function show(Reward $reward)
    {
        return view('reward.show', compact('reward'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Reward  $reward
     * @return \Illuminate\Http\Response
     */
    public function edit(Reward $reward)
    {
        $partners = $this->getPartners();
        $rewardCategories = $this->getRewardCategory();
        $rewardTags = $this->getRewardTag();

        return view('reward.edit', compact('partners', 'reward', 'rewardCategories', 'rewardTags'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Reward  $reward
     * @return \Illuminate\Http\Response
     */
    public function update(RewardRequest $request, reward $reward)
    {
        if ($request->is_active == 'on') {
            if (RewardTranslation::where('reward_id', $reward->id)->count() == 0) {
                throw ValidationException::withMessages(['is_active' => 'There is no translation against this reward please create one first']);
            }
        }

        $reward->coupon_code = $request->coupon_code;
        $reward->partner_id = $request->partner_id;
        $reward->discount = $request->discount;
        $reward->start_date = $request->start_date;
        $reward->end_date = $request->end_date;
        $reward->is_flat_discount = $request->is_flat_discount == 'on' ? 1 : 0;
        $reward->is_active = $request->is_active == 'on' ? 1 : 0;
        $reward->save();
        if (isset($request->reward_categories)) {
            $rewardCategoryMapping = new RewardCategoryMapping;
            $rewardCategoryMapping->unMapRewardCategory($reward->id);
            foreach ($request->reward_categories as $rewardCategory) {
                $rewardCategoryMapping = new RewardCategoryMapping;
                $rewardCategoryMapping->mapRewardCategory($rewardCategory, $reward->id);
            }
        }

        if (isset($request->reward_tags)) {
            $rewardTagMapping = new RewardTagMapping;
            $rewardTagMapping->unMapRewardTag($reward->id);
            foreach ($request->reward_tags as $rewardTag) {
                $rewardTagMapping = new RewardTagMapping;
                $rewardTagMapping->mapRewardTag($rewardTag, $reward->id);
            }
        }

        if (isset($request->return_to_view)) {
            return redirect('rewards/reward/'.$reward->id)->with('success', 'Reward has been updated');
        }

        return redirect()->back()->with('success', 'Reward has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Reward  $reward
     * @return \Illuminate\Http\Response
     */
    public function destroy(Reward $reward)
    {
        $reward->rewardTranslations()->delete();
        $reward->rewardCategories()->detach();
        $reward->rewardTags()->detach();
        $reward->rewardCustomers()->detach();
        $reward->delete();

        return redirect('rewards/reward')->with('message', 'Reward has been deleted');
    }
}
