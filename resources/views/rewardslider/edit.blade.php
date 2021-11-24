@extends('layouts.app')
@section('title','Edit Rewards Slider')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Edit Rewards Slider</h2>
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
                <form id="demo-form2" method='post' action="{{ route('reward-sliders.update', ['reward_slider' => $rewardSlider->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                {{csrf_field()}}
                @method('PUT')
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="image">Image <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input type="file" id="image" name="image"><br />
                            <img src="{{ \Config::get('constants.azure_storage_url').'myrewards/rewards-slider/'.$rewardSlider->image }}" style='width:200px;' /> <br /><br />
                            @if ($errors->has('image'))
                                <span class="text-danger">{{ $errors->first('image') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text_ar">Link <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input type="text" id="link" name="link" value="{{ old('link', $rewardSlider->link) }}" class="form-control" data-placement="top" title="Please enter link for redirection">
                            @if ($errors->has('link'))
                                <span class="text-danger">{{ $errors->first('link') }}</span>
                            @endif
                        </div>
                    </div>
                    <div id="rewards_slider_start_end">
                        <div class="item form-group">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="start_date">Start Date & Time<span class="required">*</span></label>
                            <div class="col-md-6 col-sm-6">
                                <input type="hidden" id="rewardSliderEditStartDateTime" name="rewardSliderEditStartDateTime" value="{{ $rewardSlider->start_date }}">
                                <input type="text" id="start_date" name="start_date" value="{{ old('start_date', $rewardSlider->start_date) }}" class="form-control" data-toggle="tooltip" data-placement="top" title="Please select start date & time">
                                @if ($errors->has('start_date'))
                                    <span class="text-danger">{{ $errors->first('start_date') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="item form-group">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="end_date">End Date & Time<span class="required">*</span></label>
                            <div class="col-md-6 col-sm-6">
                                <input type="hidden" id="rewardSliderEditEndDateTime" name="rewardSliderEditEndDateTime" value="{{ $rewardSlider->end_date }}">
                                <input type="text" id="end_date" name="end_date" value="{{ old('end_date', $rewardSlider->end_date) }}" class="form-control" data-toggle="tooltip" data-placement="top" title="Please select end date & time">
                                @if ($errors->has('end_date'))
                                    <span class="text-danger">{{ $errors->first('end_date') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="sort_order">Sort Order <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $rewardSlider->sort_order) }}"  class="form-control">
                            @if ($errors->has('sort_order'))
                                <span class="text-danger">{{ $errors->first('sort_order') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="is_active">Is Active</label>
                        <div class="col-md-6 col-sm-6">
                            <select class="form-control" id='is_active' name="is_active" data-toggle="tooltip" data-placement="top" title="Please select active false/true">
                                <option value="0" {{ $rewardSlider->is_active == 0 ? 'selected="selected"' : '' }}>False</option>
                                <option value="1" {{ $rewardSlider->is_active == 1 ? 'selected="selected"' : '' }}>True</option>
                            </select>
                        </div>
                    </div>
                    <div id='redirect_to_view_div'></div>
                    <div class="ln_solid"></div>
                    <div class="row">
                        <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-warning btn-sm" id="return_to_view" onClick="this.disabled=true;">Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
