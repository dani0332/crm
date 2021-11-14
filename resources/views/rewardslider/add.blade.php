@extends('layouts.app')
@section('title','Add Rewards Slider')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Create Rewards Slider</h2>
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
                <form id="demo-form2" method='post' action="{{ url('rewards/reward-sliders') }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                {{csrf_field()}}
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="image">Image <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="file" id="image" name="image" accept='.jpeg,.png,.jpg,.gif,.svg,.webp' data-toggle="tooltip" data-placement="top" title="Please select a image to upload" />
                            <br/>
                            @if ($errors->has('image'))
                                <span class="text-danger">{{ $errors->first('image') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="link">Link <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input type="text" id="link" name="link" value="{{ old('link') }}" class="form-control" data-toggle="tooltip" data-placement="top" title="Please enter link for redirection">
                            @if ($errors->has('link'))
                                <span class="text-danger">{{ $errors->first('link') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="sort_order">Sort Order <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order') }}" class="form-control" data-toggle="tooltip" data-placement="top" title="Please enter sort order">
                            @if ($errors->has('sort_order'))
                                <span class="text-danger">{{ $errors->first('sort_order') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="is_active">Is Active</label>
                        <div class="col-md-6 col-sm-6">
                            <div class="checkbox">
                                <input type="checkbox" class="flat" id='is_active' name='is_active' value="on" {{(old('is_active') == "on") ? 'checked': ''}}>
                            </div>
                        </div>
                    </div>
                   <div id='redirect_to_view_div'></div>
                    <div class="ln_solid"></div>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-warning btn-sm" id="return_to_view" onClick="this.disabled=true;">Create</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
