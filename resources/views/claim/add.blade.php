@extends('layouts.app')
@section('title','Add Claim')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Create Claim</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('claims.index') }}" class="btn btn-warning btn-sm">Claims List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">
                        {{ session()->get('success') }}
                    </div>
                @endif
                <form id="demo-form2" method='post' action="{{ url('claim/claims') }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                {{csrf_field()}}
                     <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="first_name">First Name <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}"  class="form-control ">
                            @if ($errors->has('first_name'))
                                <span class="text-danger">{{ $errors->first('first_name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="last_name">Last Name <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}"  class="form-control ">
                            @if ($errors->has('last_name'))
                                <span class="text-danger">{{ $errors->first('last_name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="email_address">Email Address <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="email_address" name="email_address" value="{{ old('email_address') }}"  class="form-control ">
                            @if ($errors->has('email_address'))
                                <span class="text-danger">{{ $errors->first('email_address') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="phone_number">Phone Number <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="phone_number" name="phone_number" value="{{ old('phone_number') }}"  class="form-control ">
                            @if ($errors->has('phone_number'))
                                <span class="text-danger">{{ $errors->first('phone_number') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="insurance_company">Insurance Company <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="insurance_company" name="insurance_company" value="{{ old('insurance_company') }}"  class="form-control ">
                            @if ($errors->has('insurance_company'))
                                <span class="text-danger">{{ $errors->first('insurance_company') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="policy_number">Policy Number <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="policy_number" name="policy_number" value="{{ old('policy_number') }}"  class="form-control ">
                            @if ($errors->has('policy_number'))
                                <span class="text-danger">{{ $errors->first('policy_number') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="additional_notes">Additional Notes <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <textarea id="additional_notes" name="additional_notes" rows="4" cols="50" class="form-control">{{ old('additional_notes') }}</textarea>
                            @if ($errors->has('additional_notes'))
                                <span class="text-danger">{{ $errors->first('additional_notes') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="type_of_insurances_id">Type of Insurance <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <select class="form-control" id='type_of_insurances_id' name='type_of_insurances_id'>
                                <option value=''>Choose Type of Insurance</option>
                                @foreach($typeofinsurances as $typeofinsurance)
                                @if (old('type_of_insurances_id') == $typeofinsurance->id)
                                    <option value="{{ $typeofinsurance->id }}" selected>{{ $typeofinsurance->text }}</option>
                                @else
                                    <option value="{{ $typeofinsurance->id }}">{{ $typeofinsurance->text }}</option>
                                @endif
                                @endforeach
                            </select>
                            @if ($errors->has('type_of_insurances_id'))
                                <span class="text-danger">{{ $errors->first('type_of_insurances_id') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="sub_type_of_insurance_id">Sub Type of Insurance</label>
                        <div class="col-md-6 col-sm-6 ">
                            <select class="form-control" id='sub_type_of_insurance_id' name='sub_type_of_insurance_id'>
                                <option value=''>Choose Sub Type of Insurance</option>
                                @foreach($subtypeofinsurances as $subtypeofinsurance)
                                @if (old('sub_type_of_insurance_id') == $subtypeofinsurance->id)
                                    <option value="{{ $subtypeofinsurance->id }}" selected>{{ $subtypeofinsurance->text }}</option>
                                @else
                                    <option value="{{ $subtypeofinsurance->id }}">{{ $subtypeofinsurance->text }}</option>
                                @endif
                                @endforeach
                            </select>
                            @if ($errors->has('sub_type_of_insurance_id'))
                                <span class="text-danger">{{ $errors->first('sub_type_of_insurance_id') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="car_make_id">Car Make</label>
                        <div class="col-md-6 col-sm-6 ">
                            <select class="form-control" id='car_make_id' name='car_make_id'>
                                <option value=''>Choose Car Make</option>
                                @foreach($carmakes as $carmake)
                                @if (old('car_make_id') == $carmake->id)
                                    <option value="{{ $carmake->id }}" data-id="{{ $carmake->code }}" selected>{{ $carmake->text }}</option>
                                @else
                                    <option value="{{ $carmake->id }}" data-id="{{ $carmake->code }}">{{ $carmake->text }}</option>
                                @endif
                                @endforeach
                            </select>
                            @if ($errors->has('car_make_id'))
                                <span class="text-danger">{{ $errors->first('car_make_id') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="car_model_id">Car Model</label>
                        <div class="col-md-6 col-sm-6 ">
                            <select class="form-control" id='car_model_id' name='car_model_id'>
                                <option value=""></option>
                            </select>
                            @if ($errors->has('car_model_id'))
                                <span class="text-danger">{{ $errors->first('car_model_id') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="claims_status_id">Claim Status <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <select class="form-control" id='claims_status_id' name='claims_status_id'>
                                <option value=''>Choose Claim Status</option>
                                @foreach($claimsstatuses as $claimsstatuse)
                                @if (old('claims_status_id') == $claimsstatuse->id)
                                    <option value="{{ $claimsstatuse->id }}" selected>{{ $claimsstatuse->text }}</option>
                                @else
                                    <option value="{{ $claimsstatuse->id }}">{{ $claimsstatuse->text }}</option>
                                @endif
                                @endforeach
                            </select>
                            @if ($errors->has('claims_status_id'))
                                <span class="text-danger">{{ $errors->first('claims_status_id') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="car_repair_coverage_id">Car Repair Coverage <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <select class="form-control" id='car_repair_coverage_id' name='car_repair_coverage_id'>
                                <option value=''>Choose Car Repair Coverage</option>
                                @foreach($carrepaircoverages as $carrepaircoverage)
                                @if (old('car_repair_coverage_id') == $carrepaircoverage->id)
                                    <option value="{{ $carrepaircoverage->id }}" selected>{{ $carrepaircoverage->text }}</option>
                                @else
                                    <option value="{{ $carrepaircoverage->id }}">{{ $carrepaircoverage->text }}</option>
                                @endif
                                @endforeach
                            </select>
                            @if ($errors->has('car_repair_coverage_id'))
                                <span class="text-danger">{{ $errors->first('car_repair_coverage_id') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="car_repair_type_id">Car Repair Type <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <select class="form-control" id='car_repair_type_id' name='car_repair_type_id'>
                                <option value=''>Choose Car Repair Type</option>
                                @foreach($carrepairtypes as $carrepairtype)
                                @if (old('car_repair_type_id') == $carrepairtype->id)
                                    <option value="{{ $carrepairtype->id }}" selected>{{ $carrepairtype->text }}</option>
                                @else
                                    <option value="{{ $carrepairtype->id }}">{{ $carrepairtype->text }}</option>
                                @endif
                                @endforeach
                            </select>
                            @if ($errors->has('car_repair_type_id'))
                                <span class="text-danger">{{ $errors->first('car_repair_type_id') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="rent_a_car_id">Rent a car <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <select class="form-control" id='rent_a_car_id' name='rent_a_car_id'>
                                <option value=''>Choose Rent a Car</option>
                                @foreach($rentacars as $rentacar)
                                @if (old('rent_a_car_id') == $rentacar->id)
                                    <option value="{{ $rentacar->id }}" selected>{{ $rentacar->text }}</option>
                                @else
                                    <option value="{{ $rentacar->id }}">{{ $rentacar->text }}</option>
                                @endif
                                @endforeach
                            </select>
                            @if ($errors->has('rent_a_car_id'))
                                <span class="text-danger">{{ $errors->first('rent_a_car_id') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="assigned_to_id">Assigned To <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <select class="form-control" id='assigned_to_id' name='assigned_to_id'>
                                <option value=''>Choose Advisor</option>
                                @foreach($advisors as $advisor)
                                @if (old('assigned_to_id') == $advisor->id)
                                    <option value="{{ $advisor->id }}" selected>{{ $advisor->name }}</option>
                                @else
                                    <option value="{{ $advisor->id }}">{{ $advisor->name }}</option>
                                @endif
                                @endforeach
                            </select>
                            @if ($errors->has('assigned_to_id'))
                                <span class="text-danger">{{ $errors->first('assigned_to_id') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="ticket_number">Ticket Number <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="ticket_number" name="ticket_number" value="{{ old('ticket_number') }}"  class="form-control ">
                            @if ($errors->has('ticket_number'))
                                <span class="text-danger">{{ $errors->first('ticket_number') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="plate_number">Plate Number <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="plate_number" name="plate_number" value="{{ old('plate_number') }}"  class="form-control ">
                            @if ($errors->has('plate_number'))
                                <span class="text-danger">{{ $errors->first('plate_number') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="standard_excess_payable">Standard Excess payable <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="standard_excess_payable" name="standard_excess_payable" value="{{ old('standard_excess_payable') }}"  class="form-control ">
                            @if ($errors->has('standard_excess_payable'))
                                <span class="text-danger">{{ $errors->first('standard_excess_payable') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="liability">Liability <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="liability" name="liability" value="{{ old('liability') }}"  class="form-control ">
                            @if ($errors->has('liability'))
                                <span class="text-danger">{{ $errors->first('liability') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="workshop">Workshop <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="workshop" name="workshop" value="{{ old('workshop') }}"  class="form-control ">
                            @if ($errors->has('workshop'))
                                <span class="text-danger">{{ $errors->first('workshop') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="insurer_reference">Insurer Reference <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="insurer_reference" name="insurer_reference" value="{{ old('insurer_reference') }}"  class="form-control ">
                            @if ($errors->has('insurer_reference'))
                                <span class="text-danger">{{ $errors->first('insurer_reference') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="date_of_loss">Date of loss<span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="datepicker" name="date_of_loss" value="{{ old('date_of_loss') }}"  class="form-control">
                            @if ($errors->has('date_of_loss'))
                                <span class="text-danger">{{ $errors->first('date_of_loss') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="claim_amount">Claim Amount <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="claim_amount" name="claim_amount" value="{{ old('claim_amount') }}"  class="form-control ">
                            @if ($errors->has('claim_amount'))
                                <span class="text-danger">{{ $errors->first('claim_amount') }}</span>
                            @endif
                        </div>
                    </div>
                   <div id='redirect_to_view_div'></div>
                    <div class="ln_solid"></div>
                    <div class="item form-group">
                        <div class="col-md-6 col-sm-6 offset-md-3">
                          <button type="submit" class="btn btn-warning">Create & Add New</button> <button type="submit" class="btn btn-warning" id="return_to_view" >Create</button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
@endsection
