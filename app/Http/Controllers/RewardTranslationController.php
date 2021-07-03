<?php

namespace App\Http\Controllers;

use App\Models\Reward;
use App\Models\RewardTranslation;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RewardTranslationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Reward $reward)
    {
        $rewards = Reward::orderBy('created_at','desc')->get();
        return view('reward.view', compact('rewards'));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Reward $reward)
    {
        return view('rewardtranslation.add', compact('reward'));
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Reward $reward)
    {
        $this->validate($request, [
            'title' => 'required',
            'description1' => 'required|max:1000',
            'description2' => 'required|max:1000',
            'instructions' => 'required|max:1000',
            'terms_and_conditions' => 'required|max:1500',
            'product_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'full_width_banner_image' => 'image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048', // for desktop view
            'generic_banner_image' => 'image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048', // for mobile view
            'lang' => 'required',
        ]);
        if ($this->validateLang($request->lang, $reward->id)) {
            throw ValidationException::withMessages(['lang' => 'Please choose unique Language Field']);
        }

        $rewardTranslation = new RewardTranslation();
        $rewardTranslation->title = $request->title;
        $rewardTranslation->description1 = $request->description1;
        $rewardTranslation->description2 = $request->description2;
        $rewardTranslation->instructions = $request->instructions;
        $rewardTranslation->terms_and_conditions = $request->terms_and_conditions;
        $rewardTranslation->reward_id = $reward->id;
        $rewardTranslation->lang = $request->lang;
        if ($request->file('product_image')) {
            $fileName = time() . '_' . $request->product_image->getClientOriginalName();
            $filePath = $request->file('product_image')->storeAs('/', $fileName, 'azure');
            $rewardTranslation->product_image = $fileName;
        }
        if ($request->file('full_width_banner_image')) {
            $fileName = time() . '_' . $request->full_width_banner_image->getClientOriginalName();
            $filePath = $request->file('full_width_banner_image')->storeAs('/', $fileName, 'azure');
            $rewardTranslation->full_width_banner_image = $fileName;
        }
        if ($request->file('generic_banner_image')) {
            $fileName = time() . '_' . $request->generic_banner_image->getClientOriginalName();
            $filePath = $request->file('generic_banner_image')->storeAs('/', $fileName, 'azure');
            $rewardTranslation->generic_banner_image = $fileName;
        }
        $rewardTranslation->save();
        if(isset($request->active_reward)){
            $reward->is_active = 1;
            $reward->save();
            return redirect("rewards/reward/".$reward->id)->with('success', 'Reward Translation has been stored');
        }

        if(isset($request->return_to_view)) {
            return redirect("rewards/reward/".$reward->id."/"."reward-translation/".$rewardTranslation->id)->with('success', 'Reward Translation has been stored');
        }
        return redirect()->back()->with('success', 'Reward Translation has been stored');
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Reward  $reward
     * @return \Illuminate\Http\Response
     */
    public function show(Reward $reward, RewardTranslation $rewardTranslation)
    {
        return view('rewardtranslation.show', compact('reward', 'rewardTranslation'));
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Reward  $reward
     * @return \Illuminate\Http\Response
     */
    public function edit(Reward $reward, RewardTranslation $rewardTranslation)
    {
        return view('rewardtranslation.edit', compact('reward', 'rewardTranslation'));
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Reward  $reward
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, reward $reward, RewardTranslation $rewardTranslation)
    {
        $this->validate($request, [
            'title' => 'required',
            'description1' => 'required|max:1000',
            'description2' => 'required|max:1000',
            'instructions' => 'required|max:1000',
            'terms_and_conditions' => 'required|max:1500',
            'product_image' => 'image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'full_width_banner_image' => 'image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048', // for desktop view
            'generic_banner_image' => 'image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048', // for mobile view
            'lang' => 'required',
        ]);
        $rewardTranslation->title = $request->title;
        $rewardTranslation->description1 = $request->description1;
        $rewardTranslation->description2 = $request->description2;
        $rewardTranslation->instructions = $request->instructions;
        $rewardTranslation->terms_and_conditions = $request->terms_and_conditions;
        $rewardTranslation->reward_id = $reward->id;

        if ($request->file('product_image')) {
            $fileName = time() . '_' . $request->product_image->getClientOriginalName();
            $filePath = $request->file('product_image')->storeAs('/', $fileName, 'azure');
            $rewardTranslation->product_image = $fileName;
        }
        if ($request->file('full_width_banner_image')) {
            $fileName = time() . '_' . $request->full_width_banner_image->getClientOriginalName();
            $filePath = $request->file('full_width_banner_image')->storeAs('/', $fileName, 'azure');
            $rewardTranslation->full_width_banner_image = $fileName;
        }
        if ($request->file('generic_banner_image')) {
            $fileName = time() . '_' . $request->generic_banner_image->getClientOriginalName();
            $filePath = $request->file('generic_banner_image')->storeAs('/', $fileName, 'azure');
            $rewardTranslation->generic_banner_image = $fileName;
        }
        $rewardTranslation->save();

        if(isset($request->return_to_view)) {
            return redirect("rewards/reward/".$reward->id."/"."reward-translation/".$rewardTranslation->id)->with('success', 'Reward Translation has been updated');
        }
        return redirect()->back()->with('success', 'Reward Translation has been updated');
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Reward  $reward
     * @return \Illuminate\Http\Response
     */
    public function destroy(Reward $reward, RewardTranslation $rewardTranslation)
    {
        $rewardTranslation->delete();
        return redirect("rewards/reward/".$reward->id)->with('message','Reward Translation has been deleted');
    }

    public function validateLang($lang, $reward)
    {
        $isExist = RewardTranslation::where('lang', $lang)->where('reward_id', $reward)->count();
        if ($isExist == 0) {
            return false;
        } else {
            return true;
        }
    }
}
