@extends('layouts.app')
@section('title','Update Transaction')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Edit Transaction</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('transaction.index') }}" class="btn btn-warning btn-sm">Transactions List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                <form id="demo-form2" method='post' action="{{ route('transaction.update',['transaction'=>$transaction->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                    {{csrf_field()}}
                    @method('PUT')
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Insurance Company">Insurance Company <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <select class="form-control" id="insurance_company" name="insurance_company">
                                <option value="">Select</option>
                                @foreach ($insuranceCompanies as $insuranceCompany )
                                    <option {{ $transaction->insurance_company_id == $insuranceCompany->id ? 'selected' : ''}} value="{{ $insuranceCompany->id }}">{{ $insuranceCompany->name }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('insurance_company'))
                                <span class="text-danger">{{ $errors->first('insurance_company') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Customer Name">Customer Name <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input type="text" id="customer_name" name="customer_name" name="customer_name" class="form-control" value="{{ $transaction->customer_name }}"/>
                            @if ($errors->has('customer_name'))
                                <span class="text-danger">{{ $errors->first('customer_name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Handler">Handler<span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <select class="form-control" id="assigned_to_id" name="assigned_to_id">
                                <option value="">Select</option>
                                @foreach ($handlers as $handler )
                                    <option {{ $transaction->assigned_to_id == $handler->id  ? 'selected' : ''}} value="{{ $handler->id }}">{{ $handler->name }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('assigned_to_id'))
                                <span class="text-danger">{{ $errors->first('assigned_to_id') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Mode Of Paymet">Mode Of Paymet<span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <select class="form-control" name="id" name="paymentmode">
                                <option value="">Select</option>
                                @foreach ($paymentmodes as $paymentmode )
                                    <option {{ $transaction->payment_mode_id == $paymentmode->id  ? 'selected' : ''}}  value="{{ $paymentmode->id }}">{{ $paymentmode->name }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('paymentmode'))
                                <span class="text-danger">{{ $errors->first('paymentmode') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Amount Paid">Amount Paid <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input type="number" id="amount_paid" name="amount_paid" class="form-control" value="{{ $transaction->amount_paid }}"/>
                            @if ($errors->has('amount_paid'))
                                <span class="text-danger">{{ $errors->first('amount_paid') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Risk Details">Risk Details <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input id="risk_detail" name="risk_detail" class="form-control" value="{{ $transaction->risk_details }}"/>
                            @if ($errors->has('risk_detail'))
                                <span class="text-danger">{{ $errors->first('risk_detail') }}</span>
                            @endif
                        </div>
                    </div>
                    <div id='redirect_to_view_div'></div>
                    <div class="ln_solid"></div>
                    <div class="item form-group">
                        <div class="col-md-6 col-sm-6 offset-md-3">
                        <button type="submit" class="btn btn-warning">Update & Continue Updating</button> <button type="submit" class="btn btn-warning" id="return_to_view" >Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
