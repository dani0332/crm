@extends('layouts.app')
@section('title','Edit Reward')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Edit Reward Translation</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('reward.index') }}/{{$reward->id}}" class="btn btn-warning btn-sm">Got back to Reward</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                <form id="demo-form2" method='post' action="{{ route('reward.reward-translation.update',['reward'=>$reward->id,'reward_translation'=>$rewardTranslation->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                    {{csrf_field()}}
                    @method('PUT')
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Title">Title <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input type="text" id="title" name="title" value="{{ $rewardTranslation->title }}" class="form-control">
                            @if ($errors->has('title'))
                                <span class="text-danger">{{ $errors->first('title') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Description 1">Description 1 <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <textarea id='editor1' name="description1">{{ $rewardTranslation->description1 }}</textarea>
                            @if ($errors->has('description1'))
                                <span class="text-danger">{{ $errors->first('description1') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Description 2">Description 2 <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <textarea id='editor2' name="description2">{{ $rewardTranslation->description2 }}</textarea>
                            @if ($errors->has('description2'))
                                <span class="text-danger">{{ $errors->first('description2') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Instructions">Instructions<span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <textarea id='editor3' name="instructions">{{ $rewardTranslation->instructions }}</textarea>
                            @if ($errors->has('instructions'))
                                <span class="text-danger">{{ $errors->first('instructions') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Product Image">Product Image <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <img src="{{ \Config::get('constants.azure_storage_url').'myrewards/'.$rewardTranslation->product_image }}" style='width:200px;'/> <hr />
                            <input type="file" id="product_image" name="product_image" /> <br/>
                            <small class="text-muted">
                            Max upload size: 2MB<br>
                            Dimensions: 640 x 640 px<br>
                            Formats: jpeg, png, jpg, gif, svg</small>
                            @if ($errors->has('product_image'))
                                <span class="text-danger">{{ $errors->first('product_image') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Full Width Banner Image">Full Width Banner Image</label>
                        <div class="col-md-6 col-sm-6">
                            <img src="{{ \Config::get('constants.azure_storage_url').'myrewards/'.$rewardTranslation->full_width_banner_image }}" style='width:200px;'/> <hr />
                            <input type="file" id="full_width_banner_image" name="full_width_banner_image" /> <br/>
                            <small class="text-muted">
                            Max upload size: 2MB<br>
                            Dimensions: 1920 x 320 px<br>
                            Formats: jpeg, png, jpg, gif, svg</small>
                            @if ($errors->has('full_width_banner_image'))
                                <span class="text-danger">{{ $errors->first('full_width_banner_image') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Terms And Conditions">Terms And Conditions<span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <textarea id='editor4' name="terms_and_conditions">{{ $rewardTranslation->terms_and_conditions }}</textarea>
                            @if ($errors->has('terms_and_conditions'))
                                <span class="text-danger">{{ $errors->first('terms_and_conditions') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Language">Language <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <select class="form-control" name='lang' readonly>
                                <option selected value='{{ $rewardTranslation->lang }}'>{{ $rewardTranslation->lang }}</option>
                            </select>
                            @if ($errors->has('lang'))
                                <span class="text-danger">{{ $errors->first('lang') }}</span>
                            @endif
                        </div>
                    </div>
                    <div id='redirect_to_view_div'></div>
                    <div class="ln_solid"></div>
                    <div class="row">
                        <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-warning btn-sm">Update & Continue Updating</button> <button type="submit" class="btn btn-warning btn-sm" id="return_to_view">Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
