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
                    @if ($transaction->prev_approval_code)
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Previous Approval Code">Previous Approval Code <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input type="text" id="prev_approval_code" name="prev_approval_code" value="{{ $transaction->prev_approval_code }}" class="form-control" readonly/>
                            @if ($errors->has('prev_approval_code'))
                                <span class="text-danger">{{ $errors->first('prev_approval_code') }}</span>
                            @endif
                        </div>
                    </div>
                    @endif
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Insurance Company">Insurance Company <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            @if ($route == 'cancel')
                            <input type="hidden" id="insurance_company" name="insurance_company" value="{{ $transaction->insurance_company_id }}" />
                            <select class="form-control" disabled>
                            @elseif ($route == 're_issue')
                            <select id="insurance_company" name="insurance_company" class="form-control">
                            @endif
                                <option value="">Select</option>
                                @foreach ($insuranceCompanies as $insuranceCompany )
                                    <option {{ $transaction->insurance_company_id == $insuranceCompany->id ? 'selected':'' }} value="{{ $insuranceCompany->id }}">{{ $insuranceCompany->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Please select Insurance Company Name</small><br/>
                            @if ($errors->has('insurance_company'))
                                <span class="text-danger">{{ $errors->first('insurance_company') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Paid Amount">Paid Amount <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            @if ($route == 'cancel')
                            <input id="amount_paid" name="amount_paid" value="{{ $transaction->amount_paid }}" class="form-control" readonly />
                            @elseif ($route == 're_issue')
                            <input id="amount_paid" name="amount_paid" value="{{ $transaction->amount_paid }}" class="form-control" />
                            @endif
                            <small class="text-muted">Please enter the Paid Amount</small><br/>
                            @if ($errors->has('amount_paid'))
                                <span class="text-danger">{{ $errors->first('amount_paid') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Payment mode">Payment mode <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            @if ($route == 'cancel')
                            <input type="hidden" id="paymentmode" name="paymentmode" value="{{ $transaction->payment_mode_id }}" />
                            <select class="form-control" disabled>
                            @elseif ($route == 're_issue')
                            <select id="paymentmode" name="paymentmode" class="form-control">
                            @endif
                                <option value="">Select</option>
                                @foreach ($paymentModes as $paymentMode)
                                    <option {{ $transaction->payment_mode_id == $paymentMode->id ? 'selected':'' }} value="{{ $paymentMode->id }}">{{ $paymentMode->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Please select Payment mode</small><br/>
                            @if ($errors->has('paymentmode'))
                                <span class="text-danger">{{ $errors->first('paymentmode') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="First Name">First Name <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input id="first_name" name="first_name" value="{{ $customer ? $customer->first_name : '' }}" class="form-control" readonly />
                            <small class="text-muted">Please enter First Name</small><br/>
                            @if ($errors->has('first_name'))
                                <span class="text-danger">{{ $errors->first('first_name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Last Name">Last Name <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input id="last_name" name="last_name" value="{{ $customer ? $customer->last_name : '' }}" class="form-control" readonly />
                            <small class="text-muted">Please enter Last Name</small><br/>
                            @if ($errors->has('last_name'))
                                <span class="text-danger">{{ $errors->first('last_name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Customer Email">Customer Email <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input id="email" name="email" value="{{ $customer ? $customer->email : '' }}" class="form-control" readonly />
                            <small class="text-muted">Please enter Customer Email</small><br/>
                            @if ($errors->has('email'))
                                <span class="text-danger">{{ $errors->first('email') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Risk details">Risk details <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            @if ($route == 'cancel')
                            <textarea id="risk_detail" name="risk_detail" class="form-control" readonly>{{ $transaction->risk_details }}</textarea>
                            @elseif ($route == 're_issue')
                            <textarea id="risk_detail" name="risk_detail" class="form-control">{{ $transaction->risk_details }}</textarea>
                            @endif
                            <small class="text-muted">Please enter Risk details</small><br/>
                            @if ($errors->has('risk_detail'))
                                <span class="text-danger">{{ $errors->first('risk_detail') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Handler">Advisor <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            @if ($route == 'cancel')
                            <input type="hidden" id="assigned_to_id" name="assigned_to_id" value="{{ $transaction->assigned_to_id }}" />
                            <select class="form-control" disabled>
                            @elseif ($route == 're_issue')
                            <select id="assigned_to_id" name="assigned_to_id" class="form-control">
                            @endif
                                <option value="">Please select Advisor</option>
                                @foreach ($handlers as $handler )
                                    <option {{ $transaction->assigned_to_id == $handler->id ? 'selected':'' }} value="{{ $handler->id }}">{{ $handler->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Please select Advisor</small><br/>
                            @if ($errors->has('assigned_to_id'))
                                <span class="text-danger">{{ $errors->first('assigned_to_id') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Reason">Reason <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <select id="reason" name="reason" class="form-control">
                                <option value="">Please select Reason</option>
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
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Last Transactor">Last Transactor <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input id="created_by_id" name="created_by_id" value="{{ $transaction->createdby ? $transaction->createdby->name : '' }}" class="form-control" readonly/>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Last transaction date">Last transaction date <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input id="created_at" name="created_at" value="{{ $transaction->created_at }}" class="form-control" readonly/>
                            @if ($errors->has('created_at'))
                                <span class="text-danger">{{ $errors->first('created_at') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Comments">Comments </label>
                        <div class="col-md-6 col-sm-6">
                            <textarea id="comments" name="comments" class="form-control"></textarea>
                            @if ($errors->has('comment'))
                                <span class="text-danger">{{ $errors->first('comment') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="ln_solid"></div>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-warning btn-sm" onclick="$('button').hide();">{{ $title }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
