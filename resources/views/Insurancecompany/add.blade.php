@extends('layouts.app')
@section('title','Add Insurance Company')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Create Insurance Company</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('insurancecompany.index') }}" class="btn btn-warning btn-sm">Insurance Companies List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                <form id="demo-form2" method='post' action="{{ route('insurancecompany.store') }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                {{csrf_field()}}
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Name">Name <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input type="text" id="name" name="name" value="{{ old('name') }}"  class="form-control">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Is Active">Is Active</label>
                        <div class="col-md-6 col-sm-6">
                        <div class="checkbox">
                            <label><input type="checkbox" id="is_active" name="is_active" class="flat"></label>
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
