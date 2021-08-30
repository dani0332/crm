@extends('layouts.app')
@section('title','Add TM Lead')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Create TM Lead</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('tmleads.index') }}" class="btn btn-warning btn-sm">TM Lead List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                <form id="demo-form2" method="post" action="{{ url('telemarketing/tmleads') }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                {{csrf_field()}}
                    <div class="item form-group">
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Lead Type <span class="required">*</span></span>
                            <select class="form-control" id="tm_lead_types_id" name="tm_lead_types_id" data-toggle="tooltip" data-placement="top" title="Please select lead type">
                                <option value=""></option>
                                @foreach($tmLeadTypes as $tmLeadType)
                                    @if (old('tm_lead_types_id') == $tmLeadType->id)
                                    <option value="{{ $tmLeadType->id }}" selected>{{ $tmLeadType->text }}</option>
                                    @else
                                    <option value="{{ $tmLeadType->id }}">{{ $tmLeadType->text }}</option>
                                    @endif
                                @endforeach
                            </select>
                            @if ($errors->has('tm_lead_types_id'))
                                <span class="text-danger">{{ $errors->first('tm_lead_types_id') }}</span>
                            @endif
                        </div>
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Lead Status <span class="required">*</span></span>
                            <select class="form-control" id="tm_lead_statuses_id" name="tm_lead_statuses_id" data-toggle="tooltip" data-placement="top" title="Please select lead status">
                                <option value=""></option>
                                @foreach($tmLeadStatuses as $tmLeadStatus)
                                    @if (old('tm_lead_statuses_id') == $tmLeadStatus->id)
                                    <option value="{{ $tmLeadStatus->id }}" selected>{{ $tmLeadStatus->text }}</option>
                                    @else
                                    <option value="{{ $tmLeadStatus->id }}">{{ $tmLeadStatus->text }}</option>
                                    @endif
                                @endforeach
                            </select>
                            @if ($errors->has('tm_lead_statuses_id'))
                                <span class="text-danger">{{ $errors->first('tm_lead_statuses_id') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Call Status <span class="required">*</span></span>
                            <select class="form-control" id="tm_call_statuses_id" name="tm_call_statuses_id" data-toggle="tooltip" data-placement="top" title="Please select call status">
                                <option value=""></option>
                                @foreach($tmCallStatuses as $tmCallStatus)
                                    @if (old('tm_call_statuses_id') == $tmCallStatus->id)
                                    <option value="{{ $tmCallStatus->id }}" data-id="{{ $tmCallStatus->code }}" selected>{{ $tmCallStatus->text }}</option>
                                    @else
                                    <option value="{{ $tmCallStatus->id }}" data-id="{{ $tmCallStatus->code }}">{{ $tmCallStatus->text }}</option>
                                    @endif
                                @endforeach
                            </select>
                            @if ($errors->has('tm_call_statuses_id'))
                                <span class="text-danger">{{ $errors->first('tm_call_statuses_id') }}</span>
                            @endif
                        </div>
                        <div class="col">
                            <div id="next_followup_date_field">
                                <span class="col-form-label col-md-6 col-sm-6">Next Follow-up Date <span class="required">*</span></span>
                                <input type="text" id="next_followup_date" name="next_followup_date" value="{{ old('next_followup_date') }}" class="form-control" data-toggle="tooltip" data-placement="top" title="Please select next follow-up date">
                                @if ($errors->has('next_followup_date'))
                                <span class="text-danger">{{ $errors->first('next_followup_date') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Customer Name <span class="required">*</span></span>
                            <input type="text" id="customer_name" name="customer_name" value="{{ old('customer_name') }}" class="form-control" data-toggle="tooltip" data-placement="top" title="Please enter customer name">
                            @if ($errors->has('customer_name'))
                                <span class="text-danger">{{ $errors->first('customer_name') }}</span>
                            @endif
                        </div>
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Phone number <span class="required">*</span></span>
                            <input type="text" id="phone_number" name="phone_number" value="{{ old('phone_number') }}" class="form-control" data-toggle="tooltip" data-placement="top" title="Please enter 11 digit phone number. Example: 0563264418">
                            @if ($errors->has('phone_number'))
                                <span class="text-danger">{{ $errors->first('phone_number') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Email Address <span class="required">*</span></span>
                            <input type="text" id="email_address" name="email_address" value="{{ old('email_address') }}" class="form-control" data-toggle="tooltip" data-placement="top" title="Please enter email address">
                            @if ($errors->has('email_address'))
                                <span class="text-danger">{{ $errors->first('email_address') }}</span>
                            @endif
                        </div>
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Insurance Type <span class="required">*</span></span>
                            <select class="form-control" id="tm_insurance_types_id" name="tm_insurance_types_id" data-toggle="tooltip" data-placement="top" title="Please select insurance type">
                                <option value=""></option>
                                @foreach($tmInsuranceTypes as $tmInsuranceType)
                                    @if (old('tm_insurance_types_id') == $tmInsuranceType->id)
                                    <option value="{{ $tmInsuranceType->id }}" data-id="{{ $tmInsuranceType->code }}" selected>{{ $tmInsuranceType->text }}</option>
                                    @else
                                    <option value="{{ $tmInsuranceType->id }}" data-id="{{ $tmInsuranceType->code }}">{{ $tmInsuranceType->text }}</option>
                                    @endif
                                @endforeach
                            </select>
                            @if ($errors->has('tm_insurance_types_id'))
                                <span class="text-danger">{{ $errors->first('tm_insurance_types_id') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Enquiry date <span class="required">*</span></span>
                            <input type="text" id="enquiry_date" name="enquiry_date" value="{{ old('enquiry_date') }}" class="form-control" data-toggle="tooltip" data-placement="top" title="Please select enquiry date">
                            @if ($errors->has('enquiry_date'))
                                <span class="text-danger">{{ $errors->first('enquiry_date') }}</span>
                            @endif
                        </div>
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Allocation date <span class="required">*</span></span>
                            <input type="text" id="allocation_date" name="allocation_date" value="{{ old('allocation_date') }}" class="form-control" data-toggle="tooltip" data-placement="top" title="Please select allocation date">
                            @if ($errors->has('allocation_date'))
                                <span class="text-danger">{{ $errors->first('allocation_date') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Notes</span>
                            <textarea id="notes" name="notes" rows="4" cols="50" class="form-control" data-toggle="tooltip" data-placement="top" title="Please enter notes">{{ old('notes') }}</textarea>
                            @if ($errors->has('notes'))
                                <span class="text-danger">{{ $errors->first('notes') }}</span>
                            @endif
                        </div>
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Assigned To</span>
                            <select class="form-control" id="assigned_to_id" name="assigned_to_id" data-toggle="tooltip" data-placement="top" title="Please select user">
                                <option value=""></option>
                                @foreach($handlers as $handler)
                                    @if (old('assigned_to_id') == $handler->id)
                                    <option value="{{ $handler->id }}" selected>{{ $handler->name }}</option>
                                    @else
                                    <option value="{{ $handler->id }}">{{ $handler->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div id="tm_car_fields">
                    <div class="item form-group">
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Car Type of Insurance</span>
                            <select class="form-control" id="car_type_insurance_id" name="car_type_insurance_id" data-toggle="tooltip" data-placement="top" title="Please select car type of insurance">
                                <option value=""></option>
                                @foreach($carTypeInsurances as $carTypeInsurance)
                                    @if (old('car_type_insurance_id') == $carTypeInsurance->id)
                                    <option value="{{ $carTypeInsurance->id }}" selected>{{ $carTypeInsurance->text }}</option>
                                    @else
                                    <option value="{{ $carTypeInsurance->id }}">{{ $carTypeInsurance->text }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Years of driving</span>
                            <select class="form-control" id="years_of_driving_id" name="years_of_driving_id" data-toggle="tooltip" data-placement="top" title="Please select years of driving">
                                <option value=""></option>
                                @foreach($yearsOfDrivings as $yearsOfDriving)
                                    @if (old('years_of_driving_id') == $yearsOfDriving->id)
                                    <option value="{{ $yearsOfDriving->id }}" selected>{{ $yearsOfDriving->text }}</option>
                                    @else
                                    <option value="{{ $yearsOfDriving->id }}">{{ $yearsOfDriving->text }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Car Make</span>
                            <select class="form-control" id="car_make_id" name="car_make_id" data-toggle="tooltip" data-placement="top" title="Please select car make">
                            <option value=""></option>
                            @foreach($carMakes as $carMake)
                                @if (old('car_make_id') == $carMake->id)
                                    <option value="{{ $carMake->id }}" data-id="{{ $carMake->code }}" selected>{{ $carMake->text }}</option>
                                @else
                                    <option value="{{ $carMake->id }}" data-id="{{ $carMake->code }}">{{ $carMake->text }}</option>
                                @endif
                            @endforeach
                            </select>
                        </div>
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Car Model</span>
                            <select class="form-control" id="car_model_id" name="car_model_id" data-toggle="tooltip" data-placement="top" title="Please select car model">
                            <option value=""></option>
                            </select>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Year of Manufacture</span>
                            <select class="form-control" id="year_of_manufacture" name="year_of_manufacture" data-toggle="tooltip" data-placement="top" title="Please select year of manufacture">
                                <option value=""></option>
                                <option value="2022">2022</option>
                                <option value="2021">2021</option>
                                <option value="2020">2020</option>
                                <option value="2019">2019</option>
                                <option value="2018">2018</option>
                                <option value="2017">2017</option>
                                <option value="2016">2016</option>
                                <option value="2015">2015</option>
                                <option value="2014">2014</option>
                                <option value="2013">2013</option>
                                <option value="2012">2012</option>
                                <option value="2011">2011</option>
                                <option value="2010">2010</option>
                                <option value="2009">2009</option>
                                <option value="2008">2008</option>
                                <option value="2007">2007</option>
                                <option value="2006">2006</option>
                                <option value="2005">2005</option>
                                <option value="2004">2004</option>
                                <option value="2003">2003</option>
                                <option value="2002">2002</option>
                                <option value="2001">2001</option>
                                <option value="2000">2000</option>
                                <option value="1999">1999</option>
                                <option value="1998 or older">1998 or older</option>
                            </select>
                        </div>
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Emirates of Registration</span>
                            <select class="form-control" id="emirates_of_registration_id" name="emirates_of_registration_id" data-toggle="tooltip" data-placement="top" title="Please select emirates of registration">
                            <option value=""></option>
                            @foreach($emiratesOfRegistrations as $emiratesOfRegistration)
                                @if (old('emirates_of_registration_id') == $emiratesOfRegistration->id)
                                    <option value="{{ $emiratesOfRegistration->id }}" selected>{{ $emiratesOfRegistration->text }}</option>
                                @else
                                    <option value="{{ $emiratesOfRegistration->id }}">{{ $emiratesOfRegistration->text }}</option>
                                @endif
                            @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Car Value</span>
                            <input type="text" id="car_value" name="car_value" value="{{ old('car_value') }}" class="form-control" data-toggle="tooltip" data-placement="top" title="Please enter car value">
                        </div>
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Nationality</span>
                            <select class="form-control" id="nationality_id" name="nationality_id" data-toggle="tooltip" data-placement="top" title="Please select nationality">
                                <option value=""></option>
                                @foreach($nationalities as $nationality)
                                    @if (old('nationality_id') == $nationality->id)
                                    <option value="{{ $nationality->id }}" selected>{{ $nationality->text }}</option>
                                    @else
                                    <option value="{{ $nationality->id }}">{{ $nationality->text }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                    </div>
                   <div id="redirect_to_view_div"></div>
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
