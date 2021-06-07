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
                <form id="demo-form2" method='post' action="{{ route($route) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                {{csrf_field()}}
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="approval_code">Approval Code <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input name="approval_code" class="form-control" value="{{ $transaction->approval_code }}" readonly/>
                            @if ($errors->has('approval_code'))
                                <span class="text-danger">{{ $errors->first('approval_code') }}</span>
                            @endif
                        </div>

                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="insurance_company">Insurance Company<span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <select class="form-control" name="insurance_company">
                                <option value="">Select</option>
                                @foreach ($insurancecompanies as $insurancecompany )
                                    <option {{ $transaction->insurance_company_id == $insurancecompany->id ? 'selected':''  }} value="{{ $insurancecompany->id }}">{{ $insurancecompany->name }}</option>
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
                            <input name="customer_name" class="form-control" value="{{ $transaction->customer_name }}"/>
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
                                    <option {{ $transaction->handler_id == $handler->id ? 'selected':''  }} value="{{ $handler->id }}">{{ $handler->name }}</option>
                                @endforeach
                            </select>
                            <span class="text">Please select Handler Name</span> <br/>
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
                                    <option {{ $transaction->payment_mode_id == $paymentmode->id ? 'selected':''  }} value="{{ $paymentmode->id }}">{{ $paymentmode->name }}</option>
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
                            <input name="amount_paid" class="form-control" value="{{ $transaction->amount_paid }}"/>
                            <span class="text">Please enter the Amount Paid</span> <br/>
                            @if ($errors->has('amount_paid'))
                                <span class="text-danger">{{ $errors->first('amount_paid') }}</span>
                            @endif
                        </div>

                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="reason">Reason<span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <select class="form-control" name="reason">
                                <option value="">Select</option>
                                @foreach ($reasons as $reason )
                                    <option {{ $transaction->reason_id == $reason->id ? 'selected':''  }} value="{{ $reason->id }}">{{ $reason->name }}</option>
                                @endforeach
                            </select>
                            <span class="text">Please select Reason</span> <br/>
                            @if ($errors->has('reason'))
                                <span class="text-danger">{{ $errors->first('reason') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="risk_detail">Risk Details <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <textarea name="risk_detail" class="form-control">{{ $transaction->risk_details }}</textarea>
                            <span class="text">Please enter Risk Details</span> <br/>
                            @if ($errors->has('risk_detail'))
                                <span class="text-danger">{{ $errors->first('risk_detail') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="created_by">Last Transactor <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input name="created_by" class="form-control" value="{{ $transaction->created_by }}" readonly/>
                            @if ($errors->has('created_by'))
                                <span class="text-danger">{{ $errors->first('created_by') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="created_at">Last Transaction Date <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input name="created_at" class="form-control" value="{{ $transaction->created_at }}" readonly/>
                            @if ($errors->has('created_at'))
                                <span class="text-danger">{{ $errors->first('created_at') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="status">Status<span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <select class="form-control" name="status">
                                <option value="">Select</option>
                                @foreach ($statuses as $status )
                                    <option {{ $transaction->status_id == $status->id ? 'selected':''  }} value="{{ $status->id }}">{{ $status->name }}</option>
                                @endforeach
                            </select>
                            <span class="text">Please Select Status</span> <br/>
                            @if ($errors->has('status'))
                                <span class="text-danger">{{ $errors->first('status') }}</span>
                            @endif
                        </div>
                    </div>


                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="comment">Comments<span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <textarea class="form-control" readonly  name="comments"></textarea>
                            @if ($errors->has('comment'))
                                <span class="text-danger">{{ $errors->first('comment') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="status_modified_at">Status Modified Date<span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input class="form-control" readonly name="status_modified_at" />
                            @if ($errors->has('status_modified_at'))
                                <span class="text-danger">{{ $errors->first('status_modified_at') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="status_modified_by">Status Modified By<span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input class="form-control" name="status_modified_by" readonly/>
                            @if ($errors->has('status_modified_by'))
                                <span class="text-danger">{{ $errors->first('status_modified_by') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="ln_solid"></div>
                    <div class="item form-group">
                        <div class="col-md-6 col-sm-6 offset-md-3">
                          <button type="submit" class="btn btn-warning">{{ $title }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
