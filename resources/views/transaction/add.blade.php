@extends('layouts.app')
@section('title','Add Transaction')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Create Transaction</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('transaction.index') }}" class="btn btn-warning btn-sm">Transaction List</a></li>
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
                <form id="demo-form2" method='post' action="{{ route('transaction.store') }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                {{csrf_field()}}
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="insurance_company">Insurance Company<span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <select class="form-control" name="insurance_company">
                                <option value="">Select</option>
                                @foreach ($insurancecompanies as $insurancecompany )
                                    <option {{ old('insurance_company') == $insurancecompany->id ? "selected":""  }} value="{{ $insurancecompany->id }}">{{ $insurancecompany->name }}</option>
                                @endforeach
                            </select>
                            <span class="text">Please select Insurance Company Name</span> <br/>
                            @if ($errors->has('insurance_company'))
                                <span class="text-danger">{{ $errors->first('insurance_company') }}</span>
                            @endif
                        </div>

                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="customer_name">Customer Name <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input name="customer_name" class="form-control" value="{{ old('customer_name') }}"/>
                            <span class="text">Please enter Customer Name</span> <br/>
                            @if ($errors->has('customer_name'))
                                <span class="text-danger">{{ $errors->first('customer_name') }}</span>
                            @endif
                        </div>

                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="handler">Handler<span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <select class="form-control" name="handler">
                                <option value="">Select</option>
                                @foreach ($handlers as $handler )
                                    <option {{ old('handler') == $handler->id ? "selected":""  }} value="{{ $handler->id }}">{{ $handler->name }}</option>
                                @endforeach
                            </select>
                            <span class="text">Please select Handler name</span> <br/>
                            @if ($errors->has('handler'))
                                <span class="text-danger">{{ $errors->first('handler') }}</span>
                            @endif
                        </div>

                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="paymentmode">Mode Of Paymet<span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <select class="form-control" name="paymentmode">
                                <option value="">Select</option>
                                @foreach ($paymentmodes as $paymentmode )
                                    <option {{ old('paymentmode') == $paymentmode->id ? "selected":""  }} value="{{ $paymentmode->id }}">{{ $paymentmode->name }}</option>
                                @endforeach
                            </select>
                            <span class="text">Please select Mode Of Paymet</span> <br/>
                            @if ($errors->has('paymentmode'))
                                <span class="text-danger">{{ $errors->first('paymentmode') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="amount_paid">Amount Paid <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="number" name="amount_paid" class="form-control" value="{{ old('amount_paid') }}"/>
                            <span class="text">Please enter the Amount Paid</span> <br/>
                            @if ($errors->has('amount_paid'))
                                <span class="text-danger">{{ $errors->first('amount_paid') }}</span>
                            @endif
                        </div>

                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="risk_detail">Risk Details <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <textarea name="risk_detail" class="form-control" >{{ old('risk_detail') }}</textarea>
                            <span class="text">Please enter Risk Details</span> <br/>
                            @if ($errors->has('risk_detail'))
                                <span class="text-danger">{{ $errors->first('risk_detail') }}</span>
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
