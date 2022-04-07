@extends('layouts.app')
@section('title', 'Vehicle Depreciation')
@section('content')
    <div class="row">
        <div class="col-md-12 col-sm-12 ">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Create Vehicle Value</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li><a href="{{ url('valuation/vehiclevalue') }}"
                                class="btn btn-warning btn-sm">Vechile Range List</a></li>
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
                    <form id="demo-form2" method='post' action="{{ url('valuation/vehiclevalue') }}"
                        data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                        {{ csrf_field() }}
                        <div class="item form-group">
                            <div class="col">
                                <div id="car_make">
                                    <span class="col-form-label col-md-6 col-sm-6">Insurance Provider</span>
                                    <select class="form-control" id='insurance_provider_id'
                                        name='insurance_provider_value'>
                                        <option value=''>Select</option>
                                        @foreach ($insuranceProviders as $item)
                                            <option value="{{ $item->id }}"
                                                @if (old('insurance_provider_value') == $item->id) selected @endif>{{ $item->text }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <span class="text-danger" id="car_make_value_msg"></span>
                                    @if ($errors->has('car_make_value'))
                                        <span class="text-danger">{{ $errors->first('car_make_value') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col">
                                <div id="car_make">
                                    <span class="col-form-label col-md-6 col-sm-6">Car Make</span>
                                    <select class="form-control" id='car_make_value' name='car_make_value'>
                                        <option value=''>Select</option>
                                        @foreach ($carmakes as $item)
                                            @if (old('car_make_value') == $item->id)
                                                <option data-id="{{ $item->code }}" value="{{ $item->id }}" selected>
                                                    {{ $item->text }}</option>
                                            @else
                                                <option data-id="{{ $item->code }}" value="{{ $item->id }}">
                                                    {{ $item->text }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <span class="text-danger" id="car_make_value_msg"></span>
                                    @if ($errors->has('car_make_value'))
                                        <span class="text-danger">{{ $errors->first('car_make_value') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col">
                                <div id="car_make">
                                    <span class="col-form-label col-md-6 col-sm-6">Car Model</span>
                                    <select class="form-control" id='car_model_value' name='car_model_value'>
                                        <option value="">Select</option>
                                        @foreach ($carmodels as $item)
                                            @if (old('car_model_value') == $item->id)
                                                <option data-id="{{ $item->code }}" value="{{ $item->id }}"
                                                    selected>{{ $item->text }}</option>
                                            @else
                                                <option data-id="{{ $item->code }}" value="{{ $item->id }}">
                                                    {{ $item->text }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <span class="text-danger" id="car_model_value_msg"></span>
                                    @if ($errors->has('car_model_value'))
                                        <span class="text-danger">{{ $errors->first('car_model_value') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <div id="car_make">
                                    <span class="col-form-label col-md-6 col-sm-6">Car Model</span>
                                    <select class="form-control" id='car_trim_value' name='car_trim_value'>
                                        <option value="">Select Car Trim</option>
                                    </select>
                                    <span class="text-danger" id="car_trim_value_msg"></span>
                                    @if ($errors->has('car_trim_value'))
                                        <span class="text-danger">{{ $errors->first('car_trim_value') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Current Value<span
                                        class="required">*</span></span>
                                <input type="text" id="current_value" name="current_value" value="{{ old('current_value') }}"
                                    class="form-control">
                                @if ($errors->has('current_value'))
                                    <span class="text-danger">{{ $errors->first('current_value') }}</span>
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
