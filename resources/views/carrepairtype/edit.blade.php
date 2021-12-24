@extends('layouts.app')
@section('title','Edit Car Repair Type')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Edit Car Repair Type</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('carrepairtype.index') }}" class="btn btn-warning btn-sm">Car Repair Types List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                <form id="demo-form2" method='post' action="{{ route('carrepairtype.update', ['carrepairtype' => $carrepairtype->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                {{csrf_field()}}
                @method('PUT')
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text">Text En <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6">
                            <input type="text" id="text" name="text" value="{{ $carrepairtype->text }}" class="form-control">
                            @if ($errors->has('text'))
                                <span class="text-danger">{{ $errors->first('text') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text_ar">Text Ar <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6">
                            <input type="text" id="text_ar" name="text_ar" value="{{ $carrepairtype->text_ar }}" class="form-control">
                            @if ($errors->has('text_ar'))
                                <span class="text-danger">{{ $errors->first('text_ar') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="sort_order">Sort Order <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6">
                            <input type="number" id="text_ar" name="sort_order" value="{{ $carrepairtype->sort_order }}" class="form-control">
                            @if ($errors->has('sort_order'))
                                <span class="text-danger">{{ $errors->first('sort_order') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="is_active">Is Active</label>
                        <div class="col-md-6 col-sm-6">
                            <div class="checkbox">
                                <input type="checkbox" {{ $carrepairtype->is_active ? 'checked' : '' }} class="flat" id='is_active' name='is_active' value="1">
                            </div>
                        </div>
                    </div>
                    <div id='redirect_to_view_div'></div>
                    <div class="ln_solid"></div>
                    <div class="row">
                        <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-warning btn-sm">Update & Continue Updating</button> <button type="submit" class="btn btn-warning btn-sm" id="return_to_view" >Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection