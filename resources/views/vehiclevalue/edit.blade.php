@extends('layouts.app')
@section('title', 'Edit Vehicle Value')
@section('content')
    <div class="row">
        <div class="col-md-12 col-sm-12 ">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Edit Vehicle Value</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li><a href="{{ route('vehiclevalue.index') }}" id="" class="btn btn-warning">Vehicle
                            Value List</a></li>
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
                        action="{{ route('vehiclevalue.update', ['vehiclevalue' => $vehiclevalue->id]) }}"
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
                                                {{ $item->id == old('insurance_provider_value', $vehiclevalue->insurance_provider_id) ? 'selected' : '' }}>
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
                                            <option data-id={{ $item->code }} value="{{ $item->id }}"
                                                {{ $item->id == old('car_make_value', $vehiclevalue->car_make_id) ? 'selected' : '' }}>
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
                                                {{ $item->id == old('car_model_value', $vehiclevalue->car_model_id) ? 'selected' : '' }}>
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
                                <div id="car_model">
                                    <span class="col-form-label col-md-6 col-sm-6">Car Trim</span>
                                    <select class="form-control" id='car_trim_value' name='car_trim_value'>
                                        <option value=''>Select</option>
                                        @foreach ($carModelDetails as $item)
                                            <option value="{{ $item->id }}"
                                                {{ $item->id == old('car_model_value', $vehiclevalue->car_model_detail_id) ? 'selected' : '' }}>
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
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Current Value<span
                                        class="required">*</span></span>
                                <input type="text" id="current_value" name="current_value"
                                    value="{{ old('current_value', $vehiclevalue->current_value) }}"
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
