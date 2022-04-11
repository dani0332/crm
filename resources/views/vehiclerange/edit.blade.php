@extends('layouts.app')
@section('title', 'Edit Vehicle Range')
@section('content')
    <div class="row">
        <div class="col-md-12 col-sm-12 ">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Edit Vehicle Range</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li><a href="{{ route('vehiclerange.index') }}" id="" class="btn btn-warning">Vehicle
                                Range List</a></li>
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
                    <form id="demo-form2" method='post'
                        action="{{ route('vehiclerange.update', ['vehiclerange' => $vehiclerange->id]) }}"
                        enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left"
                        autocomplete="off">
                        {{ csrf_field() }}
                        @method('PUT')
                        <div class="item form-group">
                            <div class="col">
                                <div id="car_model">
                                    <span class="col-form-label col-md-6 col-sm-6">Insurance Provider</span>
                                    <select class="form-control" id='insurance_provider' name='insurance_provider_value'>
                                        <option value=''>Select</option>
                                        @foreach ($insuranceProviders as $item)
                                            <option value="{{ $item->id }}"
                                                {{ $item->id == old('insurance_provider_value', $vehiclerange->insurance_provider_id) ? 'selected' : '' }}>
                                                {{ $item->text }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <span class="text-danger" id="car_model_value_msg"></span>
                                    @if ($errors->has('insurance_provider_value'))
                                        <span
                                            class="text-danger">{{ $errors->first('insurance_provider_value') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col">
                                <div id="car_make">
                                    <span class="col-form-label col-md-6 col-sm-6">Car Make</span>
                                    <select class="form-control" id='car_make_value' name='car_make_value'>
                                        <option value=''>Select</option>
                                        @foreach ($carMakes as $item)
                                            <option data-id="{{ $item->code }}" value="{{ $item->id }}"
                                                {{ $item->id == old('car_make_value', $vehiclerange->car_make_id) ? 'selected' : '' }}>
                                                {{ $item->text }}
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
                                <div id="car_model">
                                    <span class="col-form-label col-md-6 col-sm-6">Car Model</span>
                                    <select class="form-control" id='car_model_value' name='car_model_value'>
                                        <option value=''>Select</option>
                                        @foreach ($carModels as $item)
                                            <option value="{{ $item->id }}"
                                                {{ $item->id == old('car_model_value', $vehiclerange->car_model_id) ? 'selected' : '' }}>
                                                {{ $item->text }}
                                            </option>
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
                                <span class="col-form-label col-md-2 col-sm-2">Lower Limit<span class="required"> *
                                    </span></span>
                                <input type="text" class="form-control" type="number"
                                    value="{{ old('lower_limit', $vehiclerange->lower_limit) }}"
                                    name="lower_limit" id="lower_limit">
                                @if ($errors->has('lower_limit'))
                                    <span class="text-danger">{{ $errors->first('lower_limit') }}</span>
                                @endif
                            </div>
                            <div class="col">
                                <span class="col-form-label col-md-2 col-sm-2">Upper Limit<span class="required"> *
                                    </span></span>
                                <input type="text" class="form-control" type="number"
                                    value="{{ old('upper_limit', $vehiclerange->upper_limit) }}"
                                    name="upper_limit" id="upper_limit">
                                @if ($errors->has('upper_limit'))
                                    <span class="text-danger">{{ $errors->first('upper_limit') }}</span>
                                @endif
                            </div>
                        </div>
                        <div id='redirect_to_view_div'></div>
                        <div class="ln_solid"></div>
                        <div class="row">
                            <div class="col-auto mr-auto"></div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-warning btn-sm">Update & Continue Editing</button>
                                <button type="submit" class="btn btn-warning btn-sm" id="return_to_view">Update</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
