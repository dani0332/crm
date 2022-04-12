@extends('layouts.app')
@section('title', 'Edit Group Medical')
@section('content')
    <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Edit Group Medical Lead</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li><a href="{{ url('medical/amt') }}" class="btn btn-warning btn-sm">Group Medical List</a></li>
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
                    <form id="demo-form2" method='post' action="{{ route('amt.update', $record->uuid) }}" enctype="multipart/form-data"
                        data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                        {{ csrf_field() }}
                        @method('PUT')
                        <div class="item form-group">
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">First Name<span
                                        class="required">*</span></span>
                                <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $record->first_name) }}"
                                    class="form-control">
                                @if ($errors->has('first_name'))
                                    <span class="text-danger">{{ $errors->first('first_name') }}</span>
                                @endif
                            </div>
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Last Name<span
                                        class="required">*</span></span>
                                <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $record->last_name) }}"
                                    class="form-control">
                                @if ($errors->has('last_name'))
                                    <span class="text-danger">{{ $errors->first('last_name') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="item form-group">
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Email<span
                                    class="required">*</span></span>
                            <input type="text" id="email" name="email" disabled="disabled" value="{{ old('email', $record->email) }}"
                                class="form-control">
                            @if ($errors->has('email'))
                                <span class="text-danger">{{ $errors->first('email') }}</span>
                            @endif
                            </div>
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Mobile Number<span
                                        class="required">*</span></span>
                                <input type="text" id="mobile_no" name="mobile_no" disabled="disabled" value="{{ old('mobile_no', $record->mobile_no) }}"
                                    class="form-control">
                                @if ($errors->has('mobile_no'))
                                    <span class="text-danger">{{ $errors->first('mobile_no') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="item form-group">
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Company Name<span
                                    class="required">*</span></span>
                            <input type="text" id="company_name" name="company_name" value="{{ old('company_name', $record->company_name) }}"
                                class="form-control">
                            @if ($errors->has('company_name'))
                                <span class="text-danger">{{ $errors->first('company_name') }}</span>
                            @endif
                            </div>
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Number Of Employees<span
                                        class="required">*</span></span>
                                <input type="number" id="number_of_employees" name="number_of_employees" value="{{ old('number_of_employees', $record->number_of_employees) }}"
                                    class="form-control">
                                @if ($errors->has('number_of_employees'))
                                    <span class="text-danger">{{ $errors->first('number_of_employees') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="item form-group">
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Business Insurance Type<span
                                    class="required">*</span></span>
                                    <select class="form-control" id='business_type_of_insurance_id' name='business_type_of_insurance_id'>
                                        @foreach($businessInsuranceType as $item)
                                            <option value="{{ $item->id }}" data-id="{{ $item->text }}" selected>{{ $item->text }}</option>
                                        @endforeach
                                    </select>
                            @if ($errors->has('business_type_of_insurance_id'))
                                <span class="text-danger">{{ $errors->first('business_type_of_insurance_id') }}</span>
                            @endif
                            </div>
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6" style="width: 155px;">Group Medical Type<span
                                    class="required">*</span></span> <i class="fa fa-info-circle" id="tooltipGm" title="" style="display: none;margin-top:8px;"></i>
                                    <select class="form-control" id='group_medical_type_id' name='group_medical_type_id'>
                                        <option value="" >Select Group Medical Type</option>
                                        @foreach($gmTypes as $item)
                                            <option @if($item->text == $selectedGmType) selected @endif value="{{ $item->id }}" data-id="{{ $item->description }}">{{ $item->text }}</option>
                                        @endforeach
                                    </select>

                            @if ($errors->has('group_medical_type_id'))
                                <span class="text-danger">{{ $errors->first('group_medical_type_id') }}</span>
                            @endif
                            </div>
                        </div>

                        <div class="item form-group">
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Premium<span
                                    class="required">*</span></span>
                                <input type="number" id="premium"  name="premium" value="{{ old('premium', $record->premium) }}"
                                    class="form-control">
                                @if ($errors->has('premium'))
                                    <span class="text-danger">{{ $errors->first('premium') }}</span>
                                @endif
                            </div>
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Brief Details<span
                                        class="required">*</span></span>
                                <textarea type="text" id="brief_details" name="brief_details"
                                    class="form-control">{{ old('brief_details', $record->brief_details) }}</textarea>
                                @if ($errors->has('brief_details'))
                                    <span class="text-danger">{{ $errors->first('brief_details') }}</span>
                                @endif
                            </div>

                           
                        </div>

                        <div id='redirect_to_view_div'></div>
                        <div class="ln_solid"></div>
                        <div class="row">
                            <div class="col-auto mr-auto"></div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-warning btn-sm">Update</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
