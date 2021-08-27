@extends('layouts.app')
@section('title','Transaction Detail')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Transaction Detail</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('home') }}" class="btn btn-warning btn-sm">Go back</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                <form id="demo-form2" method='post' enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Approval code"><b>Approval code</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $transaction->approval_code }}</p>
                        </div>
                    </div>
                    @if ($transaction->prev_approval_code)
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Previous Approval Code"><b>Previous Approval Code</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $transaction->prev_approval_code }}</p>
                        </div>
                    </div>
                    @endif
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Insurance Company"><b>Insurance Company</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $transaction->insurance }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Paid Amount"><b>Paid Amount</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $transaction->amount_paid }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Mode Of Payment"><b>Payment mode</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $transaction->payment_mode }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Mode Of Payment"><b>Customer Name</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $customer ? $customer->first_name.' '.$customer->last_name : '' }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Risk Details"><b>Risk Details</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $transaction->risk_details }}</p>
                        </div>
                    </div>
                    {{-- <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Risk Details"><b>Type Of Insurance</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $transaction->typeofinsurance ? $transaction->typeofinsurance->text: '' }}</p>
                        </div>
                    </div> --}}
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Handler"><b>Advisor</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $transaction->assignedto ? $transaction->assignedto->name : '' }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Last Transactor"><b>Last Transactor</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $transaction->createdby ? $transaction->createdby->name : '' }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Last transaction date"><b>Last transaction date</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $transaction->created_at }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Comments"><b>Comments</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $transaction->comments }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Comments"><b>Reason</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $reason != null ? $reason->name: '' }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Comments"><b>Status</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $status }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Comments"><b>Cancelled</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $transaction->is_cancelled ? 'True' : 'False' }}</p>
                        </div>
                    </div>
                    <div class="ln_solid"></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
