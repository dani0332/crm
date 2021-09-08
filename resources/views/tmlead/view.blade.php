@extends('layouts.app')
@section('title','View TM Leads')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>TM Leads</h2>
                <ul class="nav navbar-right panel_toolbox">
                    @can('telemarketing-create') 
                    <li><a href="{{ url('telemarketing/tmleads/create') }}" class="btn btn-warning btn-sm">Create TM Lead</a></li>
                    @endcan
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                @if(session()->has('message'))
                    <div class="alert alert-danger">{{ session()->get('message') }}</div>
                @endif
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                <form method="POST" id="search-tm-leads" class="form-horizontal form-label-left" role="form" data-parsley-validate="" novalidate="" autocomplete="off">
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-2 col-sm-2" for="Search By">Search By</label>
                            <div class="col-md-6 col-sm-6">
                                <select class="form-control" id="searchType" name="searchType">
                                    <option value="cdbID">CDB ID</option>
                                    <option value="emailAddress">Email Address</option>
                                    <option value="phoneNumber">Phone Number</option>
                                </select>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-2 col-sm-2" for="Search Value">Search Value</label>
                            <div class="col-md-6 col-sm-6">
                                <input type="text" class="form-control" id="searchField" name="searchField" placeholder="Type here...">
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-2 col-sm-2" for="tm_lead_types_id">Lead Type</label>
                            <div class="col-md-6 col-sm-6">
                                <select class="form-control" id="tm_lead_types_id" name="tm_lead_types_id">
                                    <option value=""></option>
                                    @foreach ($tmLeadTypes as $tmLeadType)
                                        <option value="{{ $tmLeadType->id }}">{{ $tmLeadType->text }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-2 col-sm-2" for="tm_insurance_types_id">Insurance Type</label>
                            <div class="col-md-6 col-sm-6">
                                <select class="form-control" id="tm_insurance_types_id" name="tm_insurance_types_id">
                                    <option value=""></option>
                                    @foreach ($tmInsuranceTypes as $tmInsuranceType)
                                        <option value="{{ $tmInsuranceType->id }}">{{ $tmInsuranceType->text }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            @if($isCurrentUserIsAdvisor == "0")
                            <label class="col-form-label col-md-2 col-sm-2" for="assigned_to_id">Lead Owner</label>
                            <div class="col-md-6 col-sm-6">
                                <select class="form-control" id="assigned_to_id" name="assigned_to_id">
                                    <option value="">All</option>
                                    <option value="Unassigned">Unassigned Leads</option>
                                    <option value="MyLeads">My Leads</option>
                                    @foreach ($handlers as $handler)
                                        <option value="{{ $handler->id }}">{{ $handler->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                        </div>
                        <div class="col">

                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">

                        </div>
                            <div class="col">
                                <ul class="nav navbar-right panel_toolbox">
                                <li><input type="submit" class="btn btn-warning btn-sm" value="Search"></li>
                                <li><input type="reset" class="btn btn-warning btn-sm"></li>
                                </ul>
                            </div>
                        </div>
                    </form>

                <form method="post" action="tmLeadsAssign" class="form-horizontal form-label-left" role="form" data-parsley-validate=""novalidate="" autocomplete="off">
                {{csrf_field()}}
                @method('GET')
                <div class="row" id="tm-leads-assign-div">
                    <div class="col-md-12 col-sm-12">
                        <div class="x_panel">
                            <div class="x_title">
                                <h2>Assign Leads</h2>
                                <div class="clearfix"></div>
                            </div>
                            <div class="x_content" id="form-to-show">
                                <div class="item form-group">
                                    <label class="col-form-label col-md-2 col-sm-2" for="Assign To">Assign To</label>
                                    <div class="col-md-6 col-sm-6">
                                        <select class="form-control" id="assigned_to_id_new" name="assigned_to_id_new">
                                        <!-- <option value="">Select a user</option> -->
                                            @foreach ($handlers as $handler)
                                                <option value="{{ $handler->id }}">{{ $handler->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="item form-group">
                                    <label class="col-form-label col-md-2 col-sm-2" for="first-name"> </label>
                                    <div class="col-md-6 col-sm-6">
                                        <div class="input-group">
                                            <button type="submit" id="tmLeadsAssignToUser" name="tmLeadsAssignToUser" class="btn btn-warning btn-sm">Assign</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @if(session()->has('message'))
                    <div class="alert alert-danger">{{ session()->get('message') }}</div>
                @endif
                <input type="hidden" id="selectTmLeadId" name="selectTmLeadId" value="">
                <input type="hidden" id="isCurrentUserIsAdvisor" name="isCurrentUserIsAdvisor" value="{{ $isCurrentUserIsAdvisor }}">
                <table class="table table-striped jambo_table tmlead-data-table" style="width:100%">
                      <thead>
                        <tr>
                          <th>@if($isCurrentUserIsAdvisor == "0")<input type="checkbox" id="checkAllTmLeads" name="checkAllTmLeads" value="">@endif</th>
                          <th>Id</th>
                          <th>Customer Name</th>
                          <th>Insurance Type</th>
                          <th>Lead Type</th>
                          <th>Lead Status</th>
                          <th>Next Follow-up Date</th>
                          <th>Assigned To</th>
                          <th>Created At</th>
                          <th>Updated At</th>
                        </tr>
                      </thead>

                      <tbody>

                      </tbody>
                    </table>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
