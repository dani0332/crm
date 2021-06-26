@extends('layouts.app')
@section('title','View Claim')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Claims</h2>
                <ul class="nav navbar-right panel_toolbox">
                    @can('claim-create')
                    <li><a href="{{ url('claim/claims/create') }}" class="btn btn-warning btn-sm">Create Claim</a></li>
                    @endcan
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <form method="POST" id="search-claims" class="form-horizontal form-label-left" role="form" data-parsley-validate="" novalidate="" autocomplete="off">
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-2 col-sm-2" for="searchtype">Search By</label>
                            <div class="col-md-6 col-sm-6">
                                <select class="form-control" name="searchtype" id="search_type">
                                    <option value="id">ID</option>
                                    <option value="email_address">Email Address</option>
                                    <option value="phone_number">Phone Number</option>
                                    <option value="policy_number">Policy Number</option>
                                    <option value="ticket_number">Ticket Number</option>
                                </select>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-2 col-sm-2" for="searchfield">Search Value</label>
                            <div class="col-md-6 col-sm-6">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="searchfield" id="searchfield" placeholder="Search here...">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-2 col-sm-2" for="claim-status" id='claimstatus'>Claim Status</label>
                            <div class="col-md-6 col-sm-6">
                                <div class="input-group">
                                    <select class="form-control" name="claimstatus" id="claim_status_value">
                                        <option value="">Select</option>
                                        @foreach ($claimsstatuses as $claimstatus)
                                            <option value="{{ $claimstatus->id }}">{{ $claimstatus->text }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-2 col-sm-2" for="assigned-to" id='assignedto'>Assigned To</label>
                            <div class="col-md-6 col-sm-6">
                                <div class="input-group">
                                    <select class="form-control" name="assignedto" id="assigned_to_value">
                                        <option value="">Select</option>
                                        @foreach ($advisors as $advisor)
                                            <option value="{{ $advisor->id }}">{{ $advisor->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-2 col-sm-2" for="type-of-insurance" id='type_of_insurance'>Type of Insurance</label>
                            <div class="col-md-6 col-sm-6">
                                <div class="input-group">
                                    <select class="form-control" name="type_of_insurance" id="type_of_insurance_value">
                                        <option value="">Select</option>
                                        @foreach ($typeofinsurances as $typeofinsurance)
                                            <option value="{{ $typeofinsurance->id }}">{{ $typeofinsurance->text }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <ul class="nav navbar-right panel_toolbox">
                            <li><input type="submit" class="btn btn-warning btn-sm"></li>
                            <li><input type="reset" class="btn btn-warning btn-sm"></li>
                            </ul>
                        </div>
                    </div>
                </form>
                @if(session()->has('message'))
                    <div class="alert alert-danger">{{ session()->get('message') }}</div>
                @endif
                <table class="table table-striped jambo_table claim-data-table" style="width:100%">
                      <thead>
                        <tr>
                          <th>Id</th>
                          <th>Ticket Number</th>
                          <th>Policy Number</th>
                          <th>First Name</th>
                          <th>Last Name</th>
                          <th>Email Address</th>
                          <th>Phone Number</th>
                          <th>Type of Insurance</th>
                          <th>Status</th>
                          <th>Created At</th>
                          <th>Updated At</th>
                        </tr>
                      </thead>

                      <tbody>

                      </tbody>
                    </table>
            </div>
        </div>
    </div>
</div>
@endsection
