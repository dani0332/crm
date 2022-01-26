@extends('layouts.app')
@section('title','Edit TM Lead')
@section('content')
<?php
if($isUserTmAdvisor == "1") {
    $readonlyFieldCss = "pointer-events: none;background-color: #f6f6f6;";
}
else {
    $readonlyFieldCss = "";
}
?>
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Edit TM Lead</h2>
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
                <form id="demo-form2" method="post" action="{{ route('tmleads.update', ['tmlead' => $tmlead->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                {{csrf_field()}}
                @method('PUT')
                    <div class="item form-group">
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Lead Type <span class="required">*</span></span>
                            <select class="form-control" id="tm_lead_types_id" name="tm_lead_types_id" data-toggle="tooltip" data-placement="top" title="Please select lead type" style="{{ $readonlyFieldCss }}">
                            <option value=""></option>
                            @foreach($tmLeadTypes as $tmLeadType)
                                <option value="{{$tmLeadType->id}}"
                                {{ $tmLeadType->id == old('tm_lead_types_id',$tmlead->tm_lead_types_id) ? 'selected' : ''}}>
		                        {{ $tmLeadType->text }}
                                </option>
                            @endforeach
                            </select>
                            @if ($errors->has('tm_lead_types_id'))
                                <span class="text-danger">{{ $errors->first('tm_lead_types_id') }}</span>
                            @endif
                        </div>
                        <div class="col">

                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Customer Name <span class="required">*</span></span>
                            <input type="text" id="customer_name" name="customer_name" value="{{ old('customer_name', $tmlead->customer_name) }}" class="form-control" data-toggle="tooltip" data-placement="top" title="Please enter customer name" style="{{ $readonlyFieldCss }}">
                            @if ($errors->has('customer_name'))
                                <span class="text-danger">{{ $errors->first('customer_name') }}</span>
                            @endif
                        </div>
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Insurance Type <span class="required">*</span></span>
                            <select class="form-control" id="tm_insurance_types_id" name="tm_insurance_types_id" data-toggle="tooltip" data-placement="top" title="Please select insurance type">
                            <option value=""></option>
                            @foreach($tmInsuranceTypes as $tmInsuranceType)
                                <option value="{{$tmInsuranceType->id}}" data-id="{{ $tmInsuranceType->code }}"
                                {{ $tmInsuranceType->id == old('tm_insurance_types_id',$tmlead->tm_insurance_types_id) ? 'selected' : ''}}>
		                        {{ $tmInsuranceType->text }}
                                </option>
                            @endforeach
                            </select>
                            @if ($errors->has('tm_insurance_types_id'))
                                <span class="text-danger">{{ $errors->first('tm_insurance_types_id') }}</span>
                            @endif
                        </div>

                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Email Address <span class="required">*</span></span>
                            <input type="text" id="email_address" name="email_address" value="{{ old('email_address', $tmlead->email_address) }}" class="form-control" data-toggle="tooltip" data-placement="top" title="Please enter email address" style="{{ $readonlyFieldCss }}">
                            @if ($errors->has('email_address'))
                                <span class="text-danger">{{ $errors->first('email_address') }}</span>
                            @endif
                        </div>
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Phone number <span class="required">*</span></span>
                            <input type="text" id="phone_number" name="phone_number" value="{{ old('phone_number', $tmlead->phone_number) }}" class="form-control" data-toggle="tooltip" data-placement="top" title="Please enter 11 digit phone number. Example: 0563264418" style="{{ $readonlyFieldCss }}">
                            @if ($errors->has('phone_number'))
                                <span class="text-danger">{{ $errors->first('phone_number') }}</span>
                            @endif
                        </div>
                        <a href="javascript:void(0);" class="" id="add_additional_btn" title="Add field"><img src="/image/add-icon.png"/></a>
                    </div>

                        <div id="additional_info">
                        @if(count($tmlead->additionalInformation) > 0)
                            @foreach($tmlead->additionalInformation as $info)
                                <div class="item form-group">
                                    <div class="col">
                                        <span class="col-form-label col-md-6 col-sm-6"><b> Email Address (Optional)</b> </span>
                                        <input type="email" name="emails[]" class="form-control" data-toggle="tooltip" data-placement="top" title="Please enter email address" value="{{$info->email_address}}">
                                    </div>
                                    <div class="col">
                                        <span class="col-form-label col-md-6 col-sm-6"><b>Phone number (Optional)</b> </span>
                                        <input type="text" name="phones[]"class="form-control" data-toggle="tooltip" data-placement="top" title="Please enter 11 digit phone number. Example: 0563264418"  value="{{$info->phone_number}}">
                                    </div>
                                    <a href="javascript:void(0);" class="remove_additional_btn" title="Add field"><img src="/image/remove-icon.png"/></a>
                                </div>
                            @endforeach
                            @endif
                        </div>


                    <div class="item form-group">
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Enquiry date <span class="required">*</span></span>
                            <input type="text" id="enquiry_date" name="enquiry_date" value="{{ old('enquiry_date', $tmlead->enquiry_date) }}" class="form-control" data-toggle="tooltip" data-placement="top" title="Please select enquiry date">
                            @if ($errors->has('enquiry_date'))
                                <span class="text-danger">{{ $errors->first('enquiry_date') }}</span>
                            @endif
                        </div>
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Allocation date <span class="required">*</span></span>
                            <input type="text" id="allocation_date" name="allocation_date" value="{{ old('allocation_date', $tmlead->allocation_date) }}" class="form-control" data-toggle="tooltip" data-placement="top" title="Please select allocation date">
                            @if ($errors->has('allocation_date'))
                                <span class="text-danger">{{ $errors->first('allocation_date') }}</span>
                            @endif
                        </div>
                    </div>
                    <div id="tm_dob_field">
                        <div class="item form-group">
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Date of Birth</span>
                                <input type="text" id="dob" name="dob" value="{{ old('dob', $tmlead->dob) }}" class="form-control" data-toggle="tooltip" data-placement="top" title="Please select date of birth">
                                @if ($errors->has('dob'))
                                    <span class="text-danger">{{ $errors->first('dob') }}</span>
                                @endif
                            </div>
                            <div class="col">

                            </div>
                        </div>
                    </div>
                    <div id="tm_car_fields">
                    <div class="item form-group">
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Car Type of Insurance</span>
                            <select class="form-control" id="car_type_insurance_id" name="car_type_insurance_id" data-toggle="tooltip" data-placement="top" title="Please select car type of insurance">
                            <option value=""></option>
                            @foreach($carTypeInsurances as $carTypeInsurance)
                                <option value="{{$carTypeInsurance->id}}"
                                {{ $carTypeInsurance->id == old('car_type_insurance_id',$tmlead->car_type_insurance_id) ? 'selected' : ''}}>
		                        {{ $carTypeInsurance->text }}
                                </option>
                            @endforeach
                            </select>
                        </div>
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Years of driving</span>
                            <select class="form-control" id="years_of_driving_id" name="years_of_driving_id" data-toggle="tooltip" data-placement="top" title="Please select years of driving">
                            <option value=""></option>
                            @foreach($yearsOfDrivings as $yearsOfDriving)
                                <option value="{{$yearsOfDriving->id}}"
                                {{ $yearsOfDriving->id == old('years_of_driving_id',$tmlead->years_of_driving_id) ? 'selected' : ''}}>
		                        {{ $yearsOfDriving->text }}
                                </option>
                            @endforeach
                            </select>
                        </div>
                    </div>
                    <div id="edit_car_make_model">
                        <div class="item form-group">
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Car Make</span>
                                <select class="form-control" id="car_make_id" name="car_make_id" data-toggle="tooltip" data-placement="top" title="Please select car make">
                                <option value=""></option>
                                @foreach($carMakes as $carMake)
                                    <option value="{{$carMake->id}}" data-id="{{ $carMake['code'] }}"
                                    {{ $carMake->id == old('car_make_id',$tmlead->car_make_id) ? 'selected' : ''}}>
                                    {{ $carMake->text }}
                                    </option>
                                @endforeach
                                </select>
                            </div>
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Car Model</span>
                                <input type="hidden" value="{{ $tmlead->car_model_id }}" id="old_car_model_id" />
                                <select class="form-control" id="car_model_id" name="car_model_id" data-toggle="tooltip" data-placement="top" title="Please select car model">
                                @foreach($carModels as $carModel)
                                    <option value="{{$carModel->id}}" data-id="{{ $carModel['code'] }}"
                                    {{ $carModel->id == old('car_model_id',$tmlead->car_model_id) ? 'selected' : ''}}>
                                    {{ $carModel->text }}
                                    </option>
                                @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Year of Manufacture</span>
                            <select class="form-control" id="year_of_manufacture" name="year_of_manufacture" data-toggle="tooltip" data-placement="top" title="Please select year of manufacture">
                                <option value=""></option>
                                <option value="2022" {{ $tmlead->year_of_manufacture == '2022' ? 'selected' : '' }}>2022</option>
                                <option value="2021" {{ $tmlead->year_of_manufacture == '2021' ? 'selected' : '' }}>2021</option>
                                <option value="2020" {{ $tmlead->year_of_manufacture == '2020' ? 'selected' : '' }}>2020</option>
                                <option value="2019" {{ $tmlead->year_of_manufacture == '2019' ? 'selected' : '' }}>2019</option>
                                <option value="2018" {{ $tmlead->year_of_manufacture == '2018' ? 'selected' : '' }}>2018</option>
                                <option value="2017" {{ $tmlead->year_of_manufacture == '2017' ? 'selected' : '' }}>2017</option>
                                <option value="2016" {{ $tmlead->year_of_manufacture == '2016' ? 'selected' : '' }}>2016</option>
                                <option value="2015" {{ $tmlead->year_of_manufacture == '2015' ? 'selected' : '' }}>2015</option>
                                <option value="2014" {{ $tmlead->year_of_manufacture == '2014' ? 'selected' : '' }}>2014</option>
                                <option value="2013" {{ $tmlead->year_of_manufacture == '2013' ? 'selected' : '' }}>2013</option>
                                <option value="2012" {{ $tmlead->year_of_manufacture == '2012' ? 'selected' : '' }}>2012</option>
                                <option value="2011" {{ $tmlead->year_of_manufacture == '2011' ? 'selected' : '' }}>2011</option>
                                <option value="2010" {{ $tmlead->year_of_manufacture == '2010' ? 'selected' : '' }}>2010</option>
                                <option value="2009" {{ $tmlead->year_of_manufacture == '2009' ? 'selected' : '' }}>2009</option>
                                <option value="2008" {{ $tmlead->year_of_manufacture == '2008' ? 'selected' : '' }}>2008</option>
                                <option value="2007" {{ $tmlead->year_of_manufacture == '2007' ? 'selected' : '' }}>2007</option>
                                <option value="2006" {{ $tmlead->year_of_manufacture == '2006' ? 'selected' : '' }}>2006</option>
                                <option value="2005" {{ $tmlead->year_of_manufacture == '2005' ? 'selected' : '' }}>2005</option>
                                <option value="2004" {{ $tmlead->year_of_manufacture == '2004' ? 'selected' : '' }}>2004</option>
                                <option value="2003" {{ $tmlead->year_of_manufacture == '2003' ? 'selected' : '' }}>2003</option>
                                <option value="2002" {{ $tmlead->year_of_manufacture == '2002' ? 'selected' : '' }}>2002</option>
                                <option value="2001" {{ $tmlead->year_of_manufacture == '2001' ? 'selected' : '' }}>2001</option>
                                <option value="2000" {{ $tmlead->year_of_manufacture == '2000' ? 'selected' : '' }}>2000</option>
                                <option value="1999" {{ $tmlead->year_of_manufacture == '1999' ? 'selected' : '' }}>1999</option>
                                <option value="1998 or older" {{ $tmlead->year_of_manufacture == '1998 or older' ? 'selected' : '' }}>1998 or older</option>
                            </select>
                        </div>
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Emirates of Registration</span>
                            <select class="form-control" id="emirates_of_registration_id" name="emirates_of_registration_id" data-toggle="tooltip" data-placement="top" title="Please select emirates of registration">
                            <option value=""></option>
                            @foreach($emiratesOfRegistrations as $emiratesOfRegistration)
                                <option value="{{$emiratesOfRegistration->id}}"
                                {{ $emiratesOfRegistration->id == old('emirates_of_registration_id',$tmlead->emirates_of_registration_id) ? 'selected' : ''}}>
		                        {{ $emiratesOfRegistration->text }}
                                </option>
                            @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Car Value</span>
                            <input type="number" id="car_value" name="car_value" value="{{ old('car_value', $tmlead->car_value) }}" class="form-control" data-toggle="tooltip" data-placement="top" title="Please enter car value">
                        </div>
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Nationality</span>
                            <select class="form-control" id="nationality_id" name="nationality_id" data-toggle="tooltip" data-placement="top" title="Please select nationality">
                            <option value=""></option>
                            @foreach($nationalities as $nationality)
                                <option value="{{$nationality->id}}"
                                {{ $nationality->id == old('nationality_id',$tmlead->nationality_id) ? 'selected' : ''}}>
		                        {{ $nationality->text }}
                                </option>
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
                            <button type="submit" class="btn btn-warning btn-sm" id="return_to_view" onClick="this.disabled=true;">Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
