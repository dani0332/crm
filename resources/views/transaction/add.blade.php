@extends('layouts.app')
@section('title','Add Transaction')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Create Transaction</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                @if(session()->has('message'))
                    <div class="alert alert-danger">{{ session()->get('message') }}</div>
                @endif
                <form id="demo-form2" method='post' action="{{ route('transaction.store') }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                {{csrf_field()}}
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Insurance Company Company">Insurance Company<span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <select class="form-control" id="insurance_company" name="insurance_company" {{ isset($carQuote['insurance_coverage']["id"] ) ? 'readonly' : '' }}>
                                <option value="">Select</option>
                                @foreach ($insuranceCompanies as $insuranceCompany )
                                    <option {{ old('insurance_company') == $insuranceCompany->id ? "selected":""  }} value="{{ $insuranceCompany->id }}">{{ $insuranceCompany->name }}</option>
                                    @if ( isset($carQuote['insurance_coverage']['insurance_company_id']['insurance_company_id']) && $carQuote['insurance_coverage']['insurance_company_id']['insurance_company_id'] == $insuranceCompany->id )
                                        <option   selected="selected"   value="{{ $insuranceCompany->id }}">{{ $insuranceCompany->name }}</option>
                                    @endif
                                @endforeach
                            </select>

                            @if (isset($carQuote['insurance_coverage']))
                                <input type="hidden" name="car_quote_id" value="{{$carQuote['id']}}" />
                            @endif
                            <small class="text-muted">Please select Insurance Company Name</small><br/>
                            @if ($errors->has('insurance_company'))
                                <span class="text-danger">{{ $errors->first('insurance_company') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="First Name">First Name <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input type="text" id="first_name" {{ isset($carQuote['first_name'] ) ? 'readonly' : '' }} name="first_name" class="form-control" value="{{ $carQuote['first_name'] ?? old('first_name') }}"/>
                            <small class="text-muted">Please enter Customer First Name</small><br/>
                            @if ($errors->has('first_name'))
                                <span class="text-danger">{{ $errors->first('first_name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Last Name">Last Name <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input type="text" id="last_name" {{ isset($carQuote['last_name'] ) ? 'readonly' : '' }}  name="last_name" class="form-control"  value="{{ $carQuote['last_name'] ?? old('last_name') }}"/>
                            <small class="text-muted">Please enter Customer Last Name</small><br/>
                            @if ($errors->has('last_name'))
                                <span class="text-danger">{{ $errors->first('last_name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="email">Email <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input type="text" id="email" type="email" {{ isset($carQuote['email'] ) ? 'readonly' : '' }} name="email" class="form-control" value="{{ $carQuote['email'] ?? old('email') }}"/>
                            <small class="text-muted">Please enter Customer Email</small><br/>
                            @if ($errors->has('email'))
                                <span class="text-danger">{{ $errors->first('email') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Advisor">Advisor<span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                        @if(Auth::user()->hasRole('TRANSAPP_APPROVER'))
                        <input type="text" value="{{ Auth::user()->name }}" class="form-control" readonly/>
                        <input type="hidden" id="assigned_to_id" name="assigned_to_id" value="{{ Auth::user()->id }}" />
                        @else
                            <select class="form-control" id='assigned_to_id' name='assigned_to_id' {{ isset($carQuote['advisor_id']["id"] ) ? 'readonly' : '' }}>
                            <option value=''>Please select Advisor</option>
                            @foreach($handlers as $handler)
                                @if (old('assigned_to_id') == $handler->id || $handler->id == Auth::user()->id || ( isset($carQuote["advisor_id"]["id"]) && $carQuote["advisor_id"]["id"] == $handler->id ) )
                                    <option value="{{ $handler->id }}" selected="selected"  >{{ $handler->name }}</option>
                                @else
                                    <option value="{{ $handler->id }}">{{ $handler->name }}</option>
                                @endif
                            @endforeach
                            </select>
                            @endif
                            <small class="text-muted">Please select Advisor</small><br/>
                            @if ($errors->has('assigned_to_id'))
                                <span class="text-danger">{{ $errors->first('assigned_to_id') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Mode of payment">Internal Payment Modes<span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <select class="form-control" id="paymentmode" name="paymentmode" {{ isset($carQuote['payment_detail']["mode_id"] ) ? 'readonly' : '' }}>
                                <option value="">Select</option>
                                @foreach ($paymentModes as $paymentMode)
                                    <option {{ old('paymentmode') == $paymentMode->id ? "selected":"" }} value="{{ $paymentMode->id }}">{{ $paymentMode->name }}</option>
                                    @if (isset($carQuote['payment_detail']['mode_id']) && $carQuote['payment_detail']['mode_id'] == $paymentMode->id )
                                        <option  selected="selected"   value="{{ $paymentMode->id }}">{{ $paymentMode->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <small class="text-muted">Please select Mode Of Payment</small><br/>
                            @if ($errors->has('paymentmode'))
                                <span class="text-danger">{{ $errors->first('paymentmode') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Amount Paid">Amount Paid <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input type="text" id="amount_paid" name="amount_paid" class="form-control" value="{{ old('amount_paid') }}"/>
                            <small class="text-muted">Please enter the Amount Paid</small><br/>
                            @if ($errors->has('amount_paid'))
                                <span class="text-danger">{{ $errors->first('amount_paid') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Risk Details">Risk Details <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <textarea id="risk_detail" name="risk_detail" class="form-control" >{{ old('risk_detail') }}</textarea>
                            <small class="text-muted">Please enter Risk Details</small><br/>
                            @if ($errors->has('risk_detail'))
                                <span class="text-danger">{{ $errors->first('risk_detail') }}</span>
                            @endif
                        </div>
                    </div>
                    <div id='redirect_to_view_div'></div>
                    <div class="ln_solid"></div>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-warning btn-sm" id="return_to_view" onclick="$('button').hide();">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
