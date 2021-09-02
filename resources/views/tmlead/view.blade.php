@extends('layouts.app')
@section('title','View TM Leads')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>TM Leads</h2>
                <ul class="nav navbar-right panel_toolbox">
                    @can('tm-insurance-type-create')
                    <li><a href="{{ url('telemarketing/tmleads/create') }}" class="btn btn-warning btn-sm">Create TM Lead</a></li>
                    @endcan
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
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
                                <div class="input-group">
                                    <input type="text" class="form-control" id="searchField" name="searchField" placeholder="Type here...">
                                </div>
                            </div>
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
                @if(session()->has('message'))
                    <div class="alert alert-danger">{{ session()->get('message') }}</div>
                @endif
                <table class="table table-striped jambo_table tmlead-data-table" style="width:100%">
                      <thead>
                        <tr>
                          <th>CDB Id</th>
                          <th>Customer Name</th>
                          <th>Assigned To</th>
                          <th>Lead Status</th>
                          <th>Call Status</th>
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
