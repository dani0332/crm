
@extends('layouts.app')
@section('title','Reward Translation Detail')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Reward Translation Detail</h2>
                 <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('reward.reward-translation.index', ['reward_translation'=>$rewardTranslation->id,'reward'=>$reward->id]) }}" class="btn btn-warning btn-sm">Reward Translation List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">
                        {{ session()->get('success') }}
                    </div>
                @endif
                <form id="demo-form2" method='post'  enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">Title
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $rewardTranslation->title }}</p>
                    </div>

                </div>
                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">Description 1
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">
                            {{ $rewardTranslation->description1 }}
                        </p>
                    </div>
                </div>
                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">Description 2
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">
                            {{ $rewardTranslation->description2 }}
                        </p>
                    </div>
                </div>

                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">Instructions
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">
                            {{ $rewardTranslation->instructions }}
                        </p>
                    </div>
                </div>

                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">Product Image
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">
                            <img src="{{  \Config::get('constants.azure_storage_url').'myrewards/'.$rewardTranslation->product_image }}" style='width:200px;'/> <hr />
                        </p>
                    </div>
                </div>

                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">Full width Banner Image
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">
                            <img src="{{  \Config::get('constants.azure_storage_url').'myrewards/'.$rewardTranslation->full_width_banner_image }}" style='width:200px;'/> <hr />
                        </p>
                    </div>
                </div>

                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">Generic Banner Image
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">
                            <img src="{{  \Config::get('constants.azure_storage_url').'myrewards/'.$rewardTranslation->generic_banner_image }}" style='width:200px;'/> <hr />
                        </p>
                    </div>
                </div>

                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">Terms And Conditions
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">
                           {{  $rewardTranslation->terms_and_conditions }}
                        </p>
                    </div>
                </div>

                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">Lang
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">
                            {{ $rewardTranslation->lang }}
                        </p>
                    </div>
                </div>

                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">Terms And Conditions
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">
                            {{ $rewardTranslation->terms_and_conditions }}
                        </p>
                    </div>
                </div>


                <div class="ln_solid"></div>
                <div class="item form-group">
                    <div class="col-md-6 col-sm-6 offset-md-3">
                        <a href="{{ route('reward.reward-translation.edit', ['reward'=>$reward->id,'reward_translation' => $rewardTranslation->id]) }}" class='btn btn-warning btn-sm'>Edit </a>
                        <a href="#" date-route="{{ route('reward.reward-translation.destroy', ['reward'=>$reward->id,'reward_translation' => $rewardTranslation->id]) }}"   class='btn btn-warning btn-sm delete'>Delete</a>
                    </div>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>

@can('auditable')
    <div id="auditable">
        <button id='auditablebtn' class="btn btn-warning auditablebtn" data-id="{{ $rewardTranslation->id }}" data-model="App\Models\RewardTranslation">
            View Audit Logs
        </button>
    </div>
@endcan
@endsection

