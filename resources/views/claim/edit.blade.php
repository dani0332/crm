@extends('layouts.app')
@section('title','Edit Claim')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Edit Claim</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('claims.index') }}" class="btn btn-warning">Claim List</a></li>
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
                <form id="demo-form2" method='post' action="{{ route('claims.update', ['claim' => $claim->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                {{csrf_field()}}
                @method('PUT')

                <div class="item form-group">
                    <div class="col">
                        <input type="text" id="first_name" name="first_name" value="{{ $claim->first_name }}" class="form-control" placeholder="First name" aria-label="First name">
                        @if ($errors->has('first_name'))
                        <span class="text-danger">{{ $errors->first('first_name') }}</span>
                        @endif
                    </div>
                    <div class="col">
                        <input type="text" id="last_name" name="last_name" value="{{ $claim->last_name }}" class="form-control" placeholder="Last name" aria-label="Last name">
                        @if ($errors->has('last_name'))
                        <span class="text-danger">{{ $errors->first('last_name') }}</span>
                        @endif
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <input type="text" id="email_address" name="email_address" value="{{ $claim->email_address }}" class="form-control" placeholder="Email Address" aria-label="Email Address">
                        @if ($errors->has('email_address'))
                        <span class="text-danger">{{ $errors->first('email_address') }}</span>
                        @endif
                    </div>
                    <div class="col">
                        <input type="text" id="phone_number" name="phone_number" value="{{ $claim->phone_number }}" class="form-control" placeholder="Phone Number" aria-label="Phone Number">
                        @if ($errors->has('phone_number'))
                        <span class="text-danger">{{ $errors->first('phone_number') }}</span>
                        @endif
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <input type="text" id="insurance_company" name="insurance_company" value="{{ $claim->insurance_company }}" class="form-control" class="form-control" placeholder="Insurance Company" aria-label="Insurance Company">
                        @if ($errors->has('insurance_company'))
                        <span class="text-danger">{{ $errors->first('insurance_company') }}</span>
                        @endif
                    </div>
                    <div class="col">
                        <input type="text" id="policy_number" name="policy_number" value="{{ $claim->policy_number }}" class="form-control" placeholder="Policy Number" aria-label="Policy Number">
                        @if ($errors->has('policy_number'))
                        <span class="text-danger">{{ $errors->first('policy_number') }}</span>
                        @endif
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <textarea id="additional_notes" name="additional_notes" rows="4" cols="50" class="form-control" placeholder="Additional Notes" aria-label="Additional Notes">{{ $claim->additional_notes }}</textarea>
                        @if ($errors->has('additional_notes'))
                        <span class="text-danger">{{ $errors->first('additional_notes') }}</span>
                        @endif
                    </div>
                    <div class="col">

                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <select class="form-control" id='type_of_insurances_id' name='type_of_insurances_id'>
                        <option value=''>Choose Type of Insurance</option>
                        @foreach($typeofinsurances as $typeofinsurance)
                        <option {{ $claim->type_of_insurances_id == $typeofinsurance->id ? 'selected' : '' }} value="{{ $typeofinsurance->id }}">{{ $typeofinsurance->text }}</option>
                        @endforeach
                        </select>
                        @if ($errors->has('type_of_insurances_id'))
                        <span class="text-danger">{{ $errors->first('type_of_insurances_id') }}</span>
                        @endif
                    </div>
                    <div class="col">
                        <select class="form-control" id='sub_type_of_insurance_id' name='sub_type_of_insurance_id'>
                        <option value=''>Choose Sub Type of Insurance</option>
                        @foreach($subtypeofinsurances as $subtypeofinsurance)
                        <option {{ $claim->sub_type_of_insurance_id == $subtypeofinsurance->id ? 'selected' : '' }} value="{{ $subtypeofinsurance->id }}">{{ $subtypeofinsurance->text }}</option>
                        @endforeach
                        </select>
                        @if ($errors->has('sub_type_of_insurance_id'))
                        <span class="text-danger">{{ $errors->first('sub_type_of_insurance_id') }}</span>
                        @endif
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <select class="form-control" id='claims_status_id' name='claims_status_id'>
                        <option value=''>Choose Claim Status</option>
                        @foreach($claimsstatuses as $claimsstatus)
                        <option {{ $claim->claims_status_id == $claimsstatus->id ? 'selected' : '' }} value="{{ $claimsstatus->id }}">{{ $claimsstatus->text }}</option>
                        @endforeach
                        </select>
                        @if ($errors->has('claims_status_id'))
                        <span class="text-danger">{{ $errors->first('claims_status_id') }}</span>
                        @endif
                    </div>
                    <div class="col">
                        <select class="form-control" id='assigned_to_id' name='assigned_to_id'>
                        <option value=''>Choose Advisor</option>
                        @foreach($advisors as $advisor)
                        <option {{ $claim->assigned_to_id == $advisor->id ? 'selected' : '' }} value="{{ $advisor->id }}">{{ $advisor->name }}</option>
                        @endforeach
                        </select>
                        @if ($errors->has('assigned_to_id'))
                        <span class="text-danger">{{ $errors->first('assigned_to_id') }}</span>
                        @endif
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <input type="text" id="insurer_reference" name="insurer_reference" value="{{ $claim->insurer_reference }}" class="form-control" placeholder="Insurer Reference" aria-label="Insurer Reference">
                        @if ($errors->has('insurer_reference'))
                        <span class="text-danger">{{ $errors->first('insurer_reference') }}</span>
                        @endif
                    </div>
                    <div class="col">
                        <input type="text" id="datepicker" name="date_of_loss" value="{{ $claim->date_of_loss  }}" class="form-control" placeholder="Date of loss" aria-label="Date of loss">
                        @if ($errors->has('date_of_loss'))
                        <span class="text-danger">{{ $errors->first('date_of_loss') }}</span>
                        @endif
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <input type="text" id="claim_amount" name="claim_amount" value="{{ $claim->claim_amount }}" class="form-control" placeholder="Claim Amount" aria-label="Claim Amount">
                        @if ($errors->has('claim_amount'))
                        <span class="text-danger">{{ $errors->first('claim_amount') }}</span>
                        @endif
                    </div>
                    <div class="col">

                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <select class="form-control" id='car_make_id' name='car_make_id'>
                        <option value=''>Choose Car Make</option>
                        @foreach($carmakes as $carmake)
                        <option {{ $claim->car_make_id == $carmake->id ? 'selected' : '' }} data-id="{{ $carmake['code'] }}" value="{{ $carmake->id }}">{{ $carmake->text }}</option>
                        @endforeach
                        </select>
                        @if ($errors->has('car_make_id'))
                        <span class="text-danger">{{ $errors->first('car_make_id') }}</span>
                        @endif
                    </div>
                    <div class="col">
                        <input type="hidden" value="{{ $claim->car_model_id }}" id="old_car_model_id" />
                        <select class="form-control" id='car_model_id' name='car_model_id'>
                        @foreach($carmodels as $carmodel)
                        <option data-id="{{ $carmodel['code'] }}" value="{{ $carmodel->id }}">{{ $carmake->text }}</option>
                        @endforeach
                        </select>
                        @if ($errors->has('car_model_id'))
                        <span class="text-danger">{{ $errors->first('car_model_id') }}</span>
                        @endif
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <select class="form-control" id='car_repair_coverage_id' name='car_repair_coverage_id'>
                        <option value=''>Choose Car Repair Coverage</option>
                        @foreach($carrepaircoverages as $carrepaircoverage)
                        <option {{ $claim->car_repair_coverage_id == $carrepaircoverage->id ? 'selected' : '' }} value="{{ $carrepaircoverage->id }}">{{ $carrepaircoverage->text }}</option>
                        @endforeach
                        </select>
                        @if ($errors->has('car_repair_coverage_id'))
                        <span class="text-danger">{{ $errors->first('car_repair_coverage_id') }}</span>
                        @endif
                    </div>
                    <div class="col">
                        <select class="form-control" id='car_repair_type_id' name='car_repair_type_id'>
                        <option value=''>Choose Car Repair Type</option>
                        @foreach($carrepairtypes as $carrepairtype)
                        <option {{ $claim->car_repair_type_id == $carrepairtype->id ? 'selected' : '' }} value="{{ $carrepairtype->id }}">{{ $carrepairtype->text }}</option>
                        @endforeach
                        </select>
                        @if ($errors->has('car_repair_type_id'))
                        <span class="text-danger">{{ $errors->first('car_repair_type_id') }}</span>
                        @endif
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <select class="form-control" id='rent_a_car_id' name='rent_a_car_id'>
                        <option value=''>Choose Rent a Car</option>
                        @foreach($rentacars as $rentacar)
                        <option {{ $claim->rent_a_car_id == $rentacar->id ? 'selected' : '' }} value="{{ $rentacar->id }}">{{ $rentacar->text }}</option>
                        @endforeach
                        </select>
                        @if ($errors->has('rent_a_car_id'))
                        <span class="text-danger">{{ $errors->first('rent_a_car_id') }}</span>
                        @endif
                    </div>
                    <div class="col">
                        <input type="text" id="ticket_number" name="ticket_number" value="{{ $claim->ticket_number }}" class="form-control" placeholder="Ticket Number" aria-label="Ticket Number">
                        @if ($errors->has('ticket_number'))
                        <span class="text-danger">{{ $errors->first('ticket_number') }}</span>
                        @endif
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <input type="text" id="plate_number" name="plate_number" value="{{ $claim->plate_number }}" class="form-control" placeholder="Plate Number" aria-label="Plate Number">
                        @if ($errors->has('plate_number'))
                        <span class="text-danger">{{ $errors->first('plate_number') }}</span>
                        @endif
                    </div>
                    <div class="col">
                        <input type="text" id="standard_excess_payable" name="standard_excess_payable" value="{{ $claim->standard_excess_payable }}" class="form-control" placeholder="Standard Excess payable" aria-label="Standard Excess payable">
                        @if ($errors->has('standard_excess_payable'))
                        <span class="text-danger">{{ $errors->first('standard_excess_payable') }}</span>
                        @endif
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <input type="text" id="liability" name="liability" value="{{ $claim->liability }}" class="form-control" placeholder="Liability" aria-label="Liability">
                        @if ($errors->has('liability'))
                        <span class="text-danger">{{ $errors->first('liability') }}</span>
                        @endif
                    </div>
                    <div class="col">
                        <input type="text" id="workshop" name="workshop" value="{{ $claim->workshop }}" class="form-control" placeholder="Workshop" aria-label="Workshop">
                        @if ($errors->has('workshop'))
                        <span class="text-danger">{{ $errors->first('workshop') }}</span>
                        @endif
                    </div>
                </div>

                <div id='redirect_to_view_div'></div>
                <div class="ln_solid"></div>

                <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-warning btn-sm">Update & Continue Updating</button> <button type="submit" class="btn btn-warning btn-sm" id="return_to_view" >Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection