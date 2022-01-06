@extends('layouts.app')
@section('title', 'Edit Base Discount')
@section('content')
    <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Edit Base Discount</h2>
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
                    <form id="demo-form2" method='post' action="{{ route('base.update', $basediscount) }}"
                        enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left"
                        autocomplete="off">
                        {{ csrf_field() }}
                        @method('PUT')
                        <div class="item form-group">
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Start Value<span
                                        class="required">*</span></span>
                                <input type="text" id="value_start" name="value_start"
                                    value="{{ old('value_start', $basediscount->value_start) }}" class="form-control">
                                @if ($errors->has('value_start'))
                                    <span class="text-danger">{{ $errors->first('value_start') }}</span>
                                @endif
                            </div>
                            <div class="col">

                                <span class="col-form-label col-md-6 col-sm-6">End Value<span
                                        class="required">*</span></span>
                                <input type="text" id="value_end" name="value_end"
                                    value="{{ old('value_end', $basediscount->value_end) }}" class="form-control">
                                @if ($errors->has('value_end'))
                                    <span class="text-danger">{{ $errors->first('value_end') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="item form-group">
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Vehicle Type</span>
                                <select class="form-control" id='vehicle_type_id' name='vehicle_type_id'>
                                    <option value="">Select Vehicle Type</option>
                                    @foreach ($vehicleTypes as $vehicleType)
                                        <option value="{{ $vehicleType->id }}"
                                            {{ $vehicleType->id == old('vehicle_type_id', $basediscount->vehicle_type_id) ? 'selected' : '' }}>
                                            {{ $vehicleType->text }}
                                        </option>
                                    @endforeach
                                </select>
                                @if ($errors->has('vehicle_type_id'))
                                    <span class="text-danger">{{ $errors->first('vehicle_type_id') }}</span>
                                @endif
                            </div>
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Comprehensive NON Agency Discount<span
                                        class="required">*</span></span>
                                <input type="text" id="comprehensive_discount" name="comprehensive_discount"
                                    value="{{ old('comprehensive_discount', $basediscount->comprehensive_discount) }}"
                                    class="form-control">
                                @if ($errors->has('comprehensive_discount'))
                                    <span class="text-danger">{{ $errors->first('comprehensive_discount') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="item form-group">
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Agency Discount<span
                                        class="required">*</span></span>
                                <input type="text" id="agency_discount" name="agency_discount"
                                    value="{{ old('agency_discount', $basediscount->agency_discount) }}"
                                    class="form-control">
                                @if ($errors->has('agency_discount'))
                                    <span class="text-danger">{{ $errors->first('agency_discount') }}</span>
                                @endif
                            </div>
                        </div>
                        <div id='redirect_to_view_div'></div>
                        <div class="ln_solid"></div>
                        <div class="row">
                            <div class="col-auto mr-auto"></div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-warning btn-sm" id="return_to_view">Update</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
