@extends('layouts.app')
@section('title', 'Vehicle Depreciation')
@section('content')
    <div class="row">
        <div class="col-md-12 col-sm-12 ">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Create Vehicle Depreciation</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li><a href="{{ url('valuation/vehicledepreciation') }}"
                                class="btn btn-warning btn-sm">Depreciation List</a></li>
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
                    <form id="demo-form2" method='post' action="{{ url('valuation/vehicledepreciation') }}"
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
                                <span class="col-form-label col-md-6 col-sm-6">First Year <span
                                        class="required">*</span></span>
                                <input type="text" id="first_year" name="first_year" value="{{ old('first_year') }}"
                                    class="form-control">
                                @if ($errors->has('first_year'))
                                    <span class="text-danger">{{ $errors->first('first_year') }}</span>
                                @endif
                            </div>
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Second Year<span
                                        class="required">*</span></span>
                                <input type="text" id="second_year" name="second_year" value="{{ old('second_year') }}"
                                    class="form-control">
                                @if ($errors->has('second_year'))
                                    <span class="text-danger">{{ $errors->first('second_year') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Third Year<span class="required"> *
                                    </span></span>
                                <input type="text" class="form-control" type="number" value="{{ old('third_year') }}"
                                    name="third_year" id="third_year">
                                @if ($errors->has('third_year'))
                                    <span class="text-danger">{{ $errors->first('third_year') }}</span>
                                @endif
                            </div>
                            <div class="col">
                                <span class="col-form-label col-md-2 col-sm-2">Fourth Year<span class="required"> *
                                    </span></span>
                                <input type="text" class="form-control" type="number" value="{{ old('fourth_year') }}"
                                    name="fourth_year" id="fourth_year">
                                @if ($errors->has('fourth_year'))
                                    <span class="text-danger">{{ $errors->first('fourth_year') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <span class="col-form-label col-md-2 col-sm-2">Fifth Year<span class="required"> *
                                    </span></span>
                                <input type="text" class="form-control" type="number" value="{{ old('fifth_year') }}"
                                    name="fifth_year" id="fifth_year">
                                @if ($errors->has('fifth_year'))
                                    <span class="text-danger">{{ $errors->first('fifth_year') }}</span>
                                @endif
                            </div>
                            <div class="col">
                                <span class="col-form-label col-md-2 col-sm-2">Sixth Year<span class="required"> *
                                    </span></span>
                                <input type="text" class="form-control" type="number" value="{{ old('sixth_year') }}"
                                    name="sixth_year" id="sixth_year">
                                @if ($errors->has('sixth_year'))
                                    <span class="text-danger">{{ $errors->first('sixth_year') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <span class="col-form-label col-md-2 col-sm-2">Seventh Year<span class="required"> *
                                    </span></span>
                                <input type="text" class="form-control" type="number" value="{{ old('seventh_year') }}"
                                    name="seventh_year" id="seventh_year">
                                @if ($errors->has('seventh_year'))
                                    <span class="text-danger">{{ $errors->first('seventh_year') }}</span>
                                @endif
                            </div>
                            <div class="col">
                                <span class="col-form-label col-md-2 col-sm-2">Eighth Year<span class="required"> *
                                    </span></span>
                                <input type="text" class="form-control" type="number" value="{{ old('eighth_year') }}"
                                    name="eighth_year" id="eighth_year">
                                @if ($errors->has('eighth_year'))
                                    <span class="text-danger">{{ $errors->first('eighth_year') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <span class="col-form-label col-md-2 col-sm-2">Ninth Year<span class="required"> *
                                    </span></span>
                                <input type="text" class="form-control" type="number" value="{{ old('ninth_year') }}"
                                    name="ninth_year" id="ninth_year">
                                @if ($errors->has('ninth_year'))
                                    <span class="text-danger">{{ $errors->first('ninth_year') }}</span>
                                @endif
                            </div>
                            <div class="col">
                                <span class="col-form-label col-md-2 col-sm-2">Tenth Year<span class="required"> *
                                    </span></span>
                                <input type="text" class="form-control" type="number" value="{{ old('tenth_year') }}"
                                    name="tenth_year" id="tenth_year">
                                @if ($errors->has('tenth_year'))
                                    <span class="text-danger">{{ $errors->first('tenth_year') }}</span>
                                @endif
                            </div>
                        </div>

                        <div id='redirect_to_view_div'></div>
                        <div class="ln_solid"></div>
                        <div class="row">
                            <div class="col-auto mr-auto"></div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-warning btn-sm">Create & Add New</button> <button
                                    type="submit" class="btn btn-warning btn-sm" id="return_to_view">Create</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
