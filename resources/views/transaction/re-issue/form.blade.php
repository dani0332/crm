@extends('layouts.app')
@section('title',$title.' Transaction')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>{{ $title }} Transection</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content" id="form-to-show">
            <br />
                <form id="demo-form2" method='post' action="{{ route($route) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                {{csrf_field()}}
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Approval Code">Approval Code <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input type="text" id="approval_code" name="approval_code" value="{{ $transaction->approval_code }}" class="form-control" readonly/>
                            @if ($errors->has('approval_code'))
                                <span class="text-danger">{{ $errors->first('approval_code') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Insurance Company">Insurance Company<span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <select id="insurance_company" name="insurance_company" class="form-control">
                                <option value="">Select</option>
                                @foreach ($insurancecompanies as $insurancecompany )
                                    <option {{ $transaction->insurance_company_id == $insurancecompany->id ? 'selected':'' }} value="{{ $insurancecompany->id }}">{{ $insurancecompany->name }}</option>
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
                            <input id="customer_name" name="customer_name" value="{{ $transaction->customer_name }}" class="form-control" />
                            <small class="text-muted">Please enter Customer Name</small><br/>
                            @if ($errors->has('customer_name'))
                                <span class="text-danger">{{ $errors->first('customer_name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Handler">Handler<span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <select id="assigned_to_id" name="assigned_to_id" class="form-control">
                                <option value="">Select</option>
                                @foreach ($handlers as $handler )
                                    <option {{ $transaction->assigned_to_id == $handler->id ? 'selected':'' }} value="{{ $handler->id }}">{{ $handler->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Please select Handler Name</small><br/>
                            @if ($errors->has('assigned_to_id'))
                                <span class="text-danger">{{ $errors->first('assigned_to_id') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Mode Of Payment">Mode Of Payment<span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <select id="paymentmode" name="paymentmode" class="form-control">
                                <option value="">Select</option>
                                @foreach ($paymentmodes as $paymentmode )
                                    <option {{ $transaction->payment_mode_id == $paymentmode->id ? 'selected':'' }} value="{{ $paymentmode->id }}">{{ $paymentmode->name }}</option>
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
                            <input id="amount_paid" name="amount_paid" value="{{ $transaction->amount_paid }}" class="form-control" />
                            <small class="text-muted">Please enter the Amount Paid</small><br/>
                            @if ($errors->has('amount_paid'))
                                <span class="text-danger">{{ $errors->first('amount_paid') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Reason">Reason <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <select id="reason" name="reason" class="form-control">
                                <option value="">Select</option>
                                @foreach ($reasons as $reason )
                                    <option {{ $transaction->reason_id == $reason->id ? 'selected':'' }} value="{{ $reason->id }}">{{ $reason->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Please select Reason</small><br/>
                            @if ($errors->has('reason'))
                                <span class="text-danger">{{ $errors->first('reason') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Risk Details">Risk Details <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <textarea id="risk_detail" name="risk_detail" class="form-control">{{ $transaction->risk_details }}</textarea>
                            <small class="text-muted">Please enter Risk Details</small><br/>
                            @if ($errors->has('risk_detail'))
                                <span class="text-danger">{{ $errors->first('risk_detail') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Last Transactor">Last Transactor <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input id="created_by" name="created_by" value="{{ $transaction->created_by }}" class="form-control" readonly/>
                            @if ($errors->has('created_by'))
                                <span class="text-danger">{{ $errors->first('created_by') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Last Transaction Date">Last Transaction Date <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input id="created_at" name="created_at" value="{{ $transaction->created_at }}" class="form-control" readonly/>
                            @if ($errors->has('created_at'))
                                <span class="text-danger">{{ $errors->first('created_at') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Status">Status<span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <select id="status" name="status" class="form-control">
                                <option value="">Select</option>
                                @foreach ($statuses as $status )
                                    <option {{ $transaction->status_id == $status->id ? 'selected':'' }} value="{{ $status->id }}">{{ $status->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Please Select Status</small><br/>
                            @if ($errors->has('status'))
                                <span class="text-danger">{{ $errors->first('status') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Comments">Comments<span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <textarea id="comments" name="comments" class="form-control" readonly></textarea>
                            @if ($errors->has('comment'))
                                <span class="text-danger">{{ $errors->first('comment') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Status Modified Date">Status Modified Date<span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input type="text" id="status_modified_at" name="status_modified_at" class="form-control" readonly />
                            @if ($errors->has('status_modified_at'))
                                <span class="text-danger">{{ $errors->first('status_modified_at') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Status Updated By">Status Updated By<span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input type="text" id="status_modified_by" name="status_modified_by" class="form-control" readonly/>
                            @if ($errors->has('status_modified_by'))
                                <span class="text-danger">{{ $errors->first('status_modified_by') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="ln_solid"></div>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-warning btn-sm">{{ $title }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
