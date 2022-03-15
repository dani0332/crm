@extends('layouts.app')
@section('title', 'Edit Vehicle Depreciation')
@section('content')
    <div class="row">
        <div class="col-md-12 col-sm-12 ">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Edit Vehicle Depreciation</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li><a href="{{ route('vehicledepreciation.index') }}" id="" class="btn btn-warning">Vehicle
                                Depreciation List</a></li>
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
                        action="{{ route('vehicledepreciation.update', ['vehicledepreciation' => $vehicledepreciation->id]) }}"
                        enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left"
                        autocomplete="off">
                        {{ csrf_field() }}
                        @method('PUT')
                        <div class="item form-group">
                            <div class="col">
                                <div id="car_make">
                                    <span class="col-form-label col-md-6 col-sm-6">Car Make</span>
                                    <select class="form-control" id='car_make_value' name='car_make_value'>
                                        <option value=''>Select</option>
                                        @foreach ($carMakes as $item)
                                            <option data-id={{ $item->code }} value="{{ $item->id }}"
                                                {{ $item->id == old('car_make_value', $vehicledepreciation->car_make_id) ? 'selected' : '' }}>
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
                                                {{ $item->id == old('car_model_value', $vehicledepreciation->car_model_id) ? 'selected' : '' }}>
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
                                <div id="car_model">
                                    <span class="col-form-label col-md-6 col-sm-6">Insurance Provider</span>
                                    <select class="form-control" id='insurance_provider' name='insurance_provider_value'>
                                        <option value=''>Select</option>
                                        @foreach ($insuranceProviders as $item)
                                            <option value="{{ $item->id }}"
                                                {{ $item->id == old('insurance_provider_value', $vehicledepreciation->insurance_provider_id) ? 'selected' : '' }}>
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
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">First Year <span
                                        class="required">*</span></span>
                                <input type="text" id="first_year" name="first_year"
                                    value="{{ old('first_year', $vehicledepreciation->first_year) }}"
                                    class="form-control">
                                @if ($errors->has('first_year'))
                                    <span class="text-danger">{{ $errors->first('first_year') }}</span>
                                @endif
                            </div>
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Second Year<span
                                        class="required">*</span></span>
                                <input type="text" id="second_year" name="second_year"
                                    value="{{ old('second_year', $vehicledepreciation->second_year) }}"
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
                                <input type="text" class="form-control" type="number"
                                    value="{{ old('third_year', $vehicledepreciation->third_year) }}" name="third_year"
                                    id="third_year">
                                @if ($errors->has('third_year'))
                                    <span class="text-danger">{{ $errors->first('third_year') }}</span>
                                @endif
                            </div>
                            <div class="col">
                                <span class="col-form-label col-md-2 col-sm-2">Fourth Year<span class="required"> *
                                    </span></span>
                                <input type="text" class="form-control" type="number"
                                    value="{{ old('fourth_year', $vehicledepreciation->fourth_year) }}"
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
                                <input type="text" class="form-control" type="number"
                                    value="{{ old('fifth_year', $vehicledepreciation->fifth_year) }}" name="fifth_year"
                                    id="fifth_year">
                                @if ($errors->has('fifth_year'))
                                    <span class="text-danger">{{ $errors->first('fifth_year') }}</span>
                                @endif
                            </div>
                            <div class="col">
                                <span class="col-form-label col-md-2 col-sm-2">Sixth Year<span class="required"> *
                                    </span></span>
                                <input type="text" class="form-control" type="number"
                                    value="{{ old('sixth_year', $vehicledepreciation->sixth_year) }}" name="sixth_year"
                                    id="sixth_year">
                                @if ($errors->has('sixth_year'))
                                    <span class="text-danger">{{ $errors->first('sixth_year') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <span class="col-form-label col-md-2 col-sm-2">Seventh Year<span class="required"> *
                                    </span></span>
                                <input type="text" class="form-control" type="number"
                                    value="{{ old('seventh_year', $vehicledepreciation->seventh_year) }}"
                                    name="seventh_year" id="seventh_year">
                                @if ($errors->has('seventh_year'))
                                    <span class="text-danger">{{ $errors->first('seventh_year') }}</span>
                                @endif
                            </div>
                            <div class="col">
                                <span class="col-form-label col-md-2 col-sm-2">Eighth Year<span class="required"> *
                                    </span></span>
                                <input type="text" class="form-control" type="number"
                                    value="{{ old('eighth_year', $vehicledepreciation->eighth_year) }}"
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
                                <input type="text" class="form-control" type="number"
                                    value="{{ old('ninth_year', $vehicledepreciation->ninth_year) }}" name="ninth_year"
                                    id="ninth_year">
                                @if ($errors->has('ninth_year'))
                                    <span class="text-danger">{{ $errors->first('ninth_year') }}</span>
                                @endif
                            </div>
                            <div class="col">
                                <span class="col-form-label col-md-2 col-sm-2">Tenth Year<span class="required"> *
                                    </span></span>
                                <input type="text" class="form-control" type="number"
                                    value="{{ old('tenth_year', $vehicledepreciation->tenth_year) }}" name="tenth_year"
                                    id="tenth_year">
                                @if ($errors->has('tenth_year'))
                                    <span class="text-danger">{{ $errors->first('tenth_year') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <span class="col-form-label col-md-2 col-sm-2">Upper Limit<span class="required"> *
                                    </span></span>
                                <input type="text" class="form-control" type="number"
                                    value="{{ old('upper_limit', $vehicledepreciation->upper_limit) }}"
                                    name="upper_limit" id="upper_limit">
                                @if ($errors->has('upper_limit'))
                                    <span class="text-danger">{{ $errors->first('upper_limit') }}</span>
                                @endif
                            </div>
                            <div class="col">
                                <span class="col-form-label col-md-2 col-sm-2">Lower Limit<span class="required"> *
                                    </span></span>
                                <input type="text" class="form-control" type="number"
                                    value="{{ old('lower_limit', $vehicledepreciation->lower_limit) }}"
                                    name="lower_limit" id="lower_limit">
                                @if ($errors->has('lower_limit'))
                                    <span class="text-danger">{{ $errors->first('lower_limit') }}</span>
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
