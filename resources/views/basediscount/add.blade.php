@extends('layouts.app')
@section('title', 'Add Base Discount')
@section('content')
    <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Create Base Discount</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li><a href="{{ url('discount/base') }}" class="btn btn-warning btn-sm">Base Discount
                                List</a></li>
                    </ul>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <br />
                    @if (session()->has('success'))
                        <div class="alert alert-success">{{ session()->get('success') }}</div>
                    @endif
                    @if (session()->has('message'))
                        <div class="alert alert-danger">{{ session()->get('message') }}</div>
                    @endif
                    <form id="demo-form2" method='post' action="{{ url('discount/base') }}" enctype="multipart/form-data"
                        data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                        {{ csrf_field() }}
                        <div class="item form-group">
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Start Value<span
                                        class="required">*</span></span>
                                <input type="text" id="start_value" name="start_value" value="{{ old('start_value') }}"
                                    class="form-control">
                                @if ($errors->has('start_value'))
                                    <span class="text-danger">{{ $errors->first('start_value') }}</span>
                                @endif
                            </div>
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">End Value </span>
                                <input type="text" id="end_value" name="end_value" value="{{ old('end_value') }}"
                                    class="form-control">
                            </div>
                        </div>

                        <div class="item form-group">
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Vehicle Type<span
                                        class="required">*</span></span>
                                <select class="form-control" name="vehicle_type" id="vehicle_type">
                                    <option value="">Select Vehicle Type</option>
                                    @foreach ($vehicleTypes as $vehicleType)
                                        <option value="{{ $vehicleType->id }}">{{ $vehicleType->text }}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('vehicle_type'))
                                    <span class="text-danger">{{ $errors->first('vehicle_type') }}</span>
                                @endif
                            </div>
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Comprehensive NON Agency Discount<span
                                        class="required">*</span></span>
                                <input type="text" id="non_agency" name="non_agency" value="{{ old('non_agency') }}"
                                    class="form-control">
                                @if ($errors->has('non_agency'))
                                    <span class="text-danger">{{ $errors->first('non_agency') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="item form-group">
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Agency Discount<span
                                        class="required">*</span></span>
                                <input type="text" id="agency" name="agency" value="{{ old('agency') }}"
                                    class="form-control">
                                @if ($errors->has('agency'))
                                    <span class="text-danger">{{ $errors->first('agency') }}</span>
                                @endif
                            </div>
                        </div>
                        <div id='redirect_to_view_div'></div>
                        <div class="ln_solid"></div>
                        <div class="row">
                            <div class="col-auto mr-auto"></div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-warning btn-sm" id="return_to_view">Create</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
