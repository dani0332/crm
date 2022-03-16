@extends('layouts.app')
@section('title','View Transactions')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Transaction List</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('message'))
                    <div class="alert alert-danger">{{ session()->get('message') }}</div>
                @endif
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                <form method="POST" id="search-transactions" class="form-horizontal form-label-left" role="form" data-parsley-validate="" novalidate="" autocomplete="off">
                <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-2 col-sm-2" id='start_date' for="Start Date">Start Date</label>
                            <div class="col-md-6 col-sm-6">
                                <div class="input-group">
                                    <input type="text" name="transapp_start_date" id="transapp_start_date" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-2 col-sm-2" id='stop_date' for="Stop Date">Stop Date</label>
                            <div class="col-md-6 col-sm-6">
                                <div class="input-group">
                                <input type="text" name="transapp_stop_date" id="transapp_stop_date" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                    @if(!Auth::user()->hasRole('TRANSAPP_ADVISOR') && !Auth::user()->hasRole('TRANSAPP_APPROVER'))
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-2 col-sm-2" id='created_by_id' for="Transactor">Transactor</label>
                            <div class="col-md-6 col-sm-6">
                                <div class="input-group">
                                    <select class="form-control" name="transactor" id="transactor_value">
                                        <option value="">Select</option>
                                        @foreach ($transactors as $transactor)
                                            <option value="{{ $transactor->id }}">{{ $transactor->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-2 col-sm-2" id='assigned_to_id' for="Handler">Advisor</label>
                            <div class="col-md-6 col-sm-6">
                                <div class="input-group">
                                    <select class="form-control" name="handler" id="handler_value">
                                        <option value="">Please Select Advisor</option>
                                        @foreach ($handlers as $handler)
                                            <option value="{{ $handler->id }}">{{ $handler->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-2 col-sm-2" id='insurance_company_id' for="Insurance Company">Insurance Company</label>
                            <div class="col-md-6 col-sm-6">
                                <div class="input-group">
                                    <select class="form-control" name="insurance_company" id="insurance_company_value">
                                        <option value="">Select</option>
                                        @foreach ($insurance_companies as $insurance_company)
                                            <option value="{{ $insurance_company->id }}">{{ $insurance_company->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-2 col-sm-2" id='reason_id' for="Reason">Reason</label>
                            <div class="col-md-6 col-sm-6">
                                <div class="input-group">
                                    <select class="form-control" name="reason" id="reason_value">
                                        <option value="">Select</option>
                                        @foreach ($reasons as $reason)
                                            <option value="{{ $reason->id }}">{{ $reason->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        {{-- <div class="col">
                            <label class="col-form-label col-md-2 col-sm-2" id='omer_name' for="Customer Name ">Customer Name</label>
                            <div class="col-md-6 col-sm-6">
                                <div class="input-group">
                                <input type="text" name="transapp_customer_name" id="customer_name" class="form-control">
                                </div>
                            </div>
                        </div> --}}
                        <div class="col">
                            <label class="col-form-label col-md-2 col-sm-2" id='customer_email_label' for="Customer Email ">Customer Email</label>
                            <div class="col-md-6 col-sm-6">
                                <div class="input-group">
                                <input type="text" name="transapp_customer_email" id="customer_email" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-2 col-sm-2" id='transapp_approval' for="Customer Email ">Approval Code</label>
                            <div class="col-md-6 col-sm-6">
                                <div class="input-group">
                                <input type="text" name="transapp_approval_code" id="transapp_approval_code" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col col-md-6">
                            <label class="col-form-label col-md-2 col-sm-2" id="payment_mode_id" for="Payment mode">Payment mode</label>
                            <div class="col-md-6 col-sm-6">
                                <div class="input-group">
                                    <select class="form-control" name="payment_mode" id="payment_mode_value">
                                        <option value="">Select</option>
                                        @foreach ($payment_modes as $payment_mode)
                                            <option value="{{ $payment_mode->id }}">{{ $payment_mode->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <ul class="nav navbar-right panel_toolbox">
                            <li><input type="submit" class="btn btn-warning btn-sm"></li>
                            <li><input type="reset" class="btn btn-warning btn-sm"></li>
                            </ul>
                        </div>
                    </div>
                </form>
                <input type="hidden" id="isTransappAdmin" name="isTransappAdmin" value="{{ $isTransappAdmin }}">
                <table class="table table-striped jambo_table transaction-data-table" style="width:100%">
                      <thead>
                        <tr>
                          <th>Approval<br>Code</th>
                          <th>Transaction<br>Date</th>
                          <th>Insurance<br>Company</th>
                          <th>Premium</th>
                          <th>Name</th>
                          <th>Risk Detail</th>
                          <th>Transactor</th>
                          <th>Advisor</th>
                          <th>Payment mode</th>
                          <th>Prev Approval Code</th>
                        </tr>
                      </thead>

                      <tbody>

                      </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
