@extends('layouts.app')
@section('title','Edit Status')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Edit Status</h2>
                 <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('status.index') }}" class="btn btn-warning btn-sm">Status List</a></li>
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
                <form id="demo-form2" method='post' action="{{ route('status.update', ['status' => $status->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                {{csrf_field()}}
                @method('PUT')
                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">Status description <span class="required">*</span>
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <input type="text" id="name" name="name" value="{{ $status->name }}"  class="form-control ">
                        @if ($errors->has('name'))
                            <span class="text-danger">{{ $errors->first('name') }}</span>
                        @endif
                    </div>

                </div>
                <div class="item form-group">
                    <label for="middle-name" class="col-form-label col-md-3 col-sm-3 label-align">Is Active</label>
                    <div class="col-md-6 col-sm-6 ">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox"  {{ $status->is_active ? 'checked' : '' }} class="flat" name='is_active'>
                        </label>
                        @if ($errors->has('is_active'))
                            <span class="text-danger">{{ $errors->first('is_active') }}</span>
                        @endif
                    </div>
                    </div>
                </div>
                <div id='redirect_to_view_div'></div>
                <div class="ln_solid"></div>
                <div class="item form-group">
                    <div class="col-md-6 col-sm-6 offset-md-3">
                        <button type="submit" class="btn btn-warning">Update & Continue Updating</button> <button type="submit" class="btn btn-warning" id="return_to_view" >Update</button>
                    </div>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
