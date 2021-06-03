@extends('layouts.app')
@section('title','Edit Claim')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Edit Claim</h2>
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
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="first_name">First Name <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="first_name" name="first_name" value="{{ $claim->first_name }}"  class="form-control ">
                            @if ($errors->has('first_name'))
                                <span class="text-danger">{{ $errors->first('first_name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="last_name">Last Name <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="last_name" name="last_name" value="{{ $claim->last_name }}"  class="form-control">
                            @if ($errors->has('last_name'))
                                <span class="text-danger">{{ $errors->first('last_name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="email_address">Email Address <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="email_address" name="email_address" value="{{ $claim->email_address }}"  class="form-control">
                            @if ($errors->has('email_address'))
                                <span class="text-danger">{{ $errors->first('email_address') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="phone_number">Phone Number <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="phone_number" name="phone_number" value="{{ $claim->phone_number }}"  class="form-control">
                            @if ($errors->has('phone_number'))
                                <span class="text-danger">{{ $errors->first('phone_number') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="insurance_company">Insurance Company <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="insurance_company" name="insurance_company" value="{{ $claim->insurance_company }}"  class="form-control">
                            @if ($errors->has('insurance_company'))
                                <span class="text-danger">{{ $errors->first('insurance_company') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="policy_number">Policy Number <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="policy_number" name="policy_number" value="{{ $claim->policy_number }}"  class="form-control">
                            @if ($errors->has('policy_number'))
                                <span class="text-danger">{{ $errors->first('policy_number') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="additional_notes">Additional Notes <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <textarea id="additional_notes" name="additional_notes" rows="4" cols="50" class="form-control">{{ $claim->additional_notes }}</textarea>
                            @if ($errors->has('additional_notes'))
                                <span class="text-danger">{{ $errors->first('additional_notes') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="typeofinsurance_id">Type of Insurance <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <select class="form-control" id='typeofinsurance_id' name='typeofinsurance_id'>
                                <option value=''>Choose Type of Insuranc</option>
                                @foreach($typeofinsurances as $typeofinsurance)
                                    <option {{ $claim->typeofinsurance_id == $typeofinsurance->id ? 'selected' : '' }} value="{{ $typeofinsurance->id }}">{{ $typeofinsurance->text }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('typeofinsurance_id'))
                                <span class="text-danger">{{ $errors->first('typeofinsurance_id') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="subtypeofinsurance_id">Sub Type of Insurance</label>
                        <div class="col-md-6 col-sm-6 ">
                            <select class="form-control" id='subtypeofinsurance_id' name='subtypeofinsurance_id'>
                                <option value=''>Choose Sub Type of Insurance</option>
                                @foreach($subtypeofinsurances as $subtypeofinsurance)
                                    <option {{ $claim->subtypeofinsurance_id == $subtypeofinsurance->id ? 'selected' : '' }} value="{{ $subtypeofinsurance->id }}">{{ $subtypeofinsurance->text }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('subtypeofinsurance_id'))
                                <span class="text-danger">{{ $errors->first('subtypeofinsurance_id') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="car_make_id">Car Make</label>
                        <div class="col-md-6 col-sm-6 ">
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
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="car_model_id">Car Model</label>
                        <div class="col-md-6 col-sm-6 ">
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
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="claimsstatus_id">Claim Status <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6 ">
                            <select class="form-control" id='claimsstatus_id' name='claimsstatus_id'>
                                <option value=''>Choose Claim Status</option>
                                @foreach($claimsstatuses as $claimsstatus)
                                    <option {{ $claim->claimsstatus_id == $claimsstatus->id ? 'selected' : '' }} value="{{ $claimsstatus->id }}">{{ $claimsstatus->text }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('claimsstatus_id'))
                                <span class="text-danger">{{ $errors->first('claimsstatus_id') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="carrepaircoverage_id">Car Repair Coverage <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6 ">
                            <select class="form-control" id='carrepaircoverage_id' name='carrepaircoverage_id'>
                                <option value=''>Choose Car Repair Coverage</option>
                                @foreach($carrepaircoverages as $carrepaircoverage)
                                    <option {{ $claim->carrepaircoverage_id == $carrepaircoverage->id ? 'selected' : '' }} value="{{ $carrepaircoverage->id }}">{{ $carrepaircoverage->text }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('carrepaircoverage_id'))
                                <span class="text-danger">{{ $errors->first('carrepaircoverage_id') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="carrepairtype_id">Car Repair Type <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6 ">
                            <select class="form-control" id='carrepairtype_id' name='carrepairtype_id'>
                                <option value=''>Choose Car Repair Type</option>
                                @foreach($carrepairtypes as $carrepairtype)
                                    <option {{ $claim->carrepairtype_id == $carrepairtype->id ? 'selected' : '' }} value="{{ $carrepairtype->id }}">{{ $carrepairtype->text }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('carrepairtype_id'))
                                <span class="text-danger">{{ $errors->first('carrepairtype_id') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="rentacar_id">Rent a Car <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6 ">
                            <select class="form-control" id='rentacar_id' name='rentacar_id'>
                                <option value=''>Choose Rent a Car</option>
                                @foreach($rentacars as $rentacar)
                                    <option {{ $claim->rentacar_id == $rentacar->id ? 'selected' : '' }} value="{{ $rentacar->id }}">{{ $rentacar->text }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('rentacar_id'))
                                <span class="text-danger">{{ $errors->first('rentacar_id') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="assigned_to_id">Assigned To <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6 ">
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
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="ticket_number">Ticket Number <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="ticket_number" name="ticket_number" value="{{ $claim->ticket_number }}"  class="form-control">
                            @if ($errors->has('ticket_number'))
                                <span class="text-danger">{{ $errors->first('ticket_number') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="plate_number">Plate Number <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="plate_number" name="plate_number" value="{{ $claim->plate_number }}"  class="form-control">
                            @if ($errors->has('plate_number'))
                                <span class="text-danger">{{ $errors->first('plate_number') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="standard_excess_payable">Standard Excess payable <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="standard_excess_payable" name="standard_excess_payable" value="{{ $claim->standard_excess_payable }}"  class="form-control">
                            @if ($errors->has('standard_excess_payable'))
                                <span class="text-danger">{{ $errors->first('standard_excess_payable') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="liability">Liability <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="liability" name="liability" value="{{ $claim->liability }}"  class="form-control">
                            @if ($errors->has('liability'))
                                <span class="text-danger">{{ $errors->first('liability') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="workshop">Workshop <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="workshop" name="workshop" value="{{ $claim->workshop }}"  class="form-control">
                            @if ($errors->has('workshop'))
                                <span class="text-danger">{{ $errors->first('workshop') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="insurer_reference">Insurer Reference <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="insurer_reference" name="insurer_reference" value="{{ $claim->insurer_reference }}"  class="form-control">
                            @if ($errors->has('insurer_reference'))
                                <span class="text-danger">{{ $errors->first('insurer_reference') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="date_of_loss">Date of loss<span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="datepicker" name="date_of_loss" value="{{ $claim->date_of_loss  }}"  class="form-control">
                            @if ($errors->has('date_of_loss'))
                                <span class="text-danger">{{ $errors->first('date_of_loss') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="claim_amount">Claim Amount <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="claim_amount" name="claim_amount" value="{{ $claim->claim_amount }}"  class="form-control">
                            @if ($errors->has('claim_amount'))
                                <span class="text-danger">{{ $errors->first('claim_amount') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="ln_solid"></div>
                    <div class="item form-group">
                        <div class="col-md-6 col-sm-6 offset-md-3">
                          <button type="submit" class="btn btn-warning">Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection