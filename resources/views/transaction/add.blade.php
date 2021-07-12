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
                <form id="demo-form2" method='post' action="{{ route('transaction.store') }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                {{csrf_field()}}
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Insurance Company Company">Insurance Company<span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <select class="form-control" id="insurance_company" name="insurance_company">
                                <option value="">Select</option>
                                @foreach ($insurancecompanies as $insurancecompany )
                                    <option {{ old('insurance_company') == $insurancecompany->id ? "selected":""  }} value="{{ $insurancecompany->id }}">{{ $insurancecompany->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Please select Insurance Company Name</small><br/>
                            @if ($errors->has('insurance_company'))
                                <span class="text-danger">{{ $errors->first('insurance_company') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Customer Name">Customer Name <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input type="text" id="customer_name" name="customer_name" class="form-control" value="{{ old('customer_name') }}"/>
                            <small class="text-muted">Please enter Customer Name</small><br/>
                            @if ($errors->has('customer_name'))
                                <span class="text-danger">{{ $errors->first('customer_name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Handler">Handler<span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <select class="form-control" id='assigned_to_id' name='assigned_to_id'>
                            <option value=''></option>
                            @foreach($handlers as $handler)
                                @if (old('assigned_to_id') == $handler->id || $handler->id == Auth::user()->id)
                                    <option value="{{ $handler->id }}" selected>{{ $handler->name }}</option>
                                @else
                                    <option value="{{ $handler->id }}">{{ $handler->name }}</option>
                                @endif
                            @endforeach
                            </select>
                            <small class="text-muted">Please select Handler name</small><br/>
                            @if ($errors->has('assigned_to_id'))
                                <span class="text-danger">{{ $errors->first('assigned_to_id') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Mode of payment">Mode of payment<span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <select class="form-control" id="paymentmode" name="paymentmode">
                                <option value="">Select</option>
                                @foreach ($paymentmodes as $paymentmode )
                                    <option {{ old('paymentmode') == $paymentmode->id ? "selected":""  }} value="{{ $paymentmode->id }}">{{ $paymentmode->name }}</option>
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
                            <input type="number" id="amount_paid" name="amount_paid" class="form-control" value="{{ old('amount_paid') }}"/>
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
                            <button type="submit" class="btn btn-warning btn-sm" id="return_to_view">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
