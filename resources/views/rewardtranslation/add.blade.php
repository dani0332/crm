@extends('layouts.app')
@section('title','Add Tranlsation')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Create Reward Translation</h2>
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
                <form id="demo-form2" method='post' action="{{ route('reward.reward-translation.store',['reward'=>$reward->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                {{csrf_field()}}
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Title">Title <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input type="text" id="title" name="title" value="{{ old('title') }}" class="form-control">
                            @if ($errors->has('title'))
                                <span class="text-danger">{{ $errors->first('title') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Description 1">Description 1 <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <textarea id='editor1' name="description1">{{ old('description1') }}</textarea>
                            @if ($errors->has('description1'))
                                <span class="text-danger">{{ $errors->first('description1') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Description 2">Description 2 <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <textarea id='editor2' name="description2">{{ old('description2') }}</textarea>
                            @if ($errors->has('description2'))
                                <span class="text-danger">{{ $errors->first('description2') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Instructions">Instructions<span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <textarea id='editor3' name="instructions">{{ old('instructions') }}</textarea>
                            @if ($errors->has('instructions'))
                                <span class="text-danger">{{ $errors->first('instructions') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Product Image">Product Image <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input type="file" id="product_image" name="product_image" /> <br/>
                            <small class="text-muted">
                            Max upload size: 2MB<br>
                            Dimensions: 240 x 240 px<br>
                            Formats: jpeg, png, jpg, gif, svg, webp</small>
                            @if ($errors->has('product_image'))
                                <span class="text-danger">{{ $errors->first('product_image') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Full Width Banner Image">Full Width Banner Image </label>
                        <div class="col-md-6 col-sm-6">
                            <input type="file" id="full_width_banner_image" name="full_width_banner_image" /> <br/>
                            <small class="text-muted">
                            Max upload size: 2MB<br>
                            Dimensions: 1100 x 320 px<br>
                            Formats: jpeg, png, jpg, gif, svg, webp</small>
                            @if ($errors->has('full_width_banner_image'))
                                <span class="text-danger">{{ $errors->first('full_width_banner_image') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Mobile Banner Image">Mobile Banner Image </label>
                        <div class="col-md-6 col-sm-6">
                            <input type="file" id="generic_banner_image" name="generic_banner_image" /> <br/>
                            <small class="text-muted">
                            Max upload size: 2MB<br>
                            Dimensions: 720 x 320 px<br>
                            Formats: jpeg, png, jpg, gif, svg, webp</small>
                            @if ($errors->has('generic_banner_image'))
                                <span class="text-danger">{{ $errors->first('generic_banner_image') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Terms And Conditions">Terms And Conditions<span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <textarea id='editor4' name="terms_and_conditions">{{ old('terms_and_conditions') }}</textarea>
                            @if ($errors->has('terms_and_conditions'))
                                <span class="text-danger">{{ $errors->first('terms_and_conditions') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Language">Language <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <select class="form-control" id='lang' name='lang'>
                                <option value=''>Choose Language</option>
                                <option value='en'>English</option>
                                <option value='ar'>Arabic</option>
                                {{--<option value='en' {{ old('lang') == 'en' ? 'selected' : '' }}>English</option>
                                <option value='ar' {{ old('lang') == 'ar' ? 'selected' : '' }}>Arabic</option>--}}
                            </select>
                            @if ($errors->has('lang'))
                                <span class="text-danger">{{ $errors->first('lang') }}</span>
                            @endif
                        </div>
                    </div>
                    <div id='redirect_to_view_div'></div>
                    <div id='active_reward'></div>
                    <div class="ln_solid"></div>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-warning btn-sm active_reward">Create & Active Reward</button> <button type="submit" class="btn btn-warning btn-sm">Create & Add New</button> <button type="submit" class="btn btn-warning btn-sm" id="return_to_view">Create</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
