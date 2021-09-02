@extends('layouts.app')
@section('title','Add TM Call Status')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Create TM Call Status</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('tmcallstatus.index') }}" class="btn btn-warning btn-sm">TM Call Status List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                <form id="demo-form2" method='post' action="{{ url('telemarketing/tmcallstatus') }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                {{csrf_field()}}
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="code">Code <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="code" name="code" value="{{ old('code') }}" class="form-control" maxlength="100">
                            @if ($errors->has('code'))
                                <span class="text-danger">{{ $errors->first('code') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text">Text En <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="text" name="text" value="{{ old('text') }}" class="form-control" maxlength="100">
                            @if ($errors->has('text'))
                                <span class="text-danger">{{ $errors->first('text') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text_ar">Text Ar <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input type="text" id="text_ar" name="text_ar" value="{{ old('text_ar') }}" class="form-control" maxlength="100">
                            @if ($errors->has('text_ar'))
                                <span class="text-danger">{{ $errors->first('text_ar') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="sort_order">Sort Order <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order') }}" class="form-control" onKeyPress="if(this.value.length==5) return false;">
                            @if ($errors->has('sort_order'))
                                <span class="text-danger">{{ $errors->first('sort_order') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="is_active">Is Active</label>
                        <div class="col-md-6 col-sm-6">
                            <div class="checkbox">
                                <input type="checkbox" class="flat" id='is_active' name='is_active' @if(old("is_active")) checked @endif>
                            </div>
                        </div>
                    </div>
                   <div id='redirect_to_view_div'></div>
                    <div class="ln_solid"></div>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                        <button type="submit" class="btn btn-warning btn-sm">Create & Add New</button> <button type="submit" class="btn btn-warning btn-sm" id="return_to_view">Create</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
