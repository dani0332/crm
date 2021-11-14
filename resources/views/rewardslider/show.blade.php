@extends('layouts.app')
@section('title','Rewards Slider')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-detail">
        <div class="x_panel">
            <div class="x_title">
                <h2>Rewards Slider Detail</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('reward-sliders.index') }}" class="btn btn-warning btn-sm">Rewards Slider List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="image"><b>Image</b></label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center"><img src="{{ \Config::get('constants.azure_storage_url').'myrewards/rewards-slider/'.$rewardSlider->image }}" style='width:200px;' /></p>
                    </div>
                </div>
                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="link"><b>Link</b></label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $rewardSlider->link }}</p>
                    </div>
                </div>
                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="sort_order"><b>Sort Order</b></label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $rewardSlider->sort_order }}</p>
                    </div>
                </div>
                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="is_active"><b>Is Active</b></label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $rewardSlider->is_active ? 'True' : 'False' }}</p>
                    </div>
                </div>
                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="created_at"><b>Created At</b></label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $rewardSlider->created_at }}</p>
                    </div>
                </div>
                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="updated_at"><b>Updated At</b></label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $rewardSlider->updated_at }}</p>
                    </div>
                </div>
                <div class="ln_solid"></div>
                <div class="row">
                <div class="col-auto mr-auto"></div>
                    <div class="col-auto">
                        @can('reward-sliders-edit')
                        <a id="texta" href="{{ route('reward-sliders.edit', ['reward_slider' => $rewardSlider->id]) }}" class='btn btn-warning btn-sm'>Edit </a>
                        @endcan
                        @can('reward-sliders-delete')
                        <a href="#" date-route="{{ route('reward-sliders.destroy', ['reward_slider' => $rewardSlider->id]) }}" class='btn btn-warning btn-sm delete'>Delete</a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@can('auditable')
    <div id="auditable">
        <button id='auditablebtn' class="btn btn-warning btn-sm auditablebtn" data-id="{{ $rewardSlider->id }}" data-model="App\Models\RewardSlider">
            View Audit Logs
        </button>
    </div>
@endcan
@endsection
