@extends('layouts.app')
@section('title','Transaction Detail')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Transaction Detail</h2>
                 {{-- <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('transaction.index') }}" class="btn btn-warning btn-sm">Transaction List</a></li>
                </ul> --}}
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
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Transaction Date"><b>Transaction Date</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $transaction->created_at }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Insurance Company"><b>Insurance Company</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $transaction->insurance }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Premium"><b>Premium</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $transaction->amount_paid }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Risk"><b>Risk</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $transaction->risk_details }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Transector"><b>Transector</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $transaction->createdby ? $transaction->createdby->name : '' }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Handler"><b>Handler</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $transaction->assignedto ? $transaction->assignedto->name : '' }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Mode Of Payment"><b>Mode Of Payment</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $transaction->payment_mode }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Last Modified"><b>Last Modified</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $transaction->updated_at }}</p>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@can('auditable')
    <div id="auditable">
        <button id='auditablebtn' class="btn btn-warning btn-sm auditablebtn" data-id="{{ $transaction->id }}" data-model="App\Models\Transaction">
            View Audit Logs
        </button>
    </div>
@endcan
@endsection
