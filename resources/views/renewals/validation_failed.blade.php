@extends('layouts.app')
@section('title','Batch Detail')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-detail">
        <div class="x_panel">
            <div class="x_title">
                <h2>Validation Failed Detail</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ request()->url() }}/download" class="btn btn-success btn-sm">Download Excel File</a></li>
                    <li><a href="{{ url('renewals/uploaded-leads') }}" class="btn btn-warning btn-sm">Batches List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                @if(session()->has('message'))
                    <div class="alert alert-danger">{{ session()->get('message') }}</div>
                @endif
                <form method='post' action="" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                    <table id="datatable" class="table table-striped jambo_table" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Batch</th>
                                    <th>File Name</th>
                                    <th>Quote Type</th>
                                    <th>Policy Number</th>
                                    <th>Status</th>
                                    <th>Validation Error</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($renewalLeads as $key => $lead)
                                    <tr>
                                        <td>{{ $lead->batch }}</td>
                                        <td>{{ $lead->renewalUploadLead ? $lead->renewalUploadLead->file_name : '' }}</td>
                                        <td>{{ $lead->quote_type }}</td>
                                        <td>{{ $lead->policy_number }}</td>
                                        <td>{{ $lead->status }}</td>
                                        <td>@foreach($lead->validation_errors as $error)
                                                <li>@if(!is_array($error)){{ $error }}@endif</li>
                                            @endforeach
                                        </td>
                                        <td>{{ $lead->created_at }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                    <div class="ln_solid"></div>
                    <div class="item form-group">
                        <div class="col-md-6 col-sm-6 offset-md-3">
                            
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


@endsection