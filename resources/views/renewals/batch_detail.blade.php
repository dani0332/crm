@extends('layouts.app')
@section('title','Batch Detail')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-detail">
        <div class="x_panel">
            <div class="x_title">
                <h2>Batch Detail</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="/renewals/batches" class="btn btn-warning btn-sm">Batches List</a></li>
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
                <form id="demo-form2" method='post' action="" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                    <ul class="nav navbar-right panel_toolbox">
                        <li><a class="btn btn-success btn-sm" onclick="return confirm('Do you want to send emails?');" href="{{ $batch }}/batch-process">Send Emails</a></li>
                    </ul>
                    <table id="datatable" class="table table-striped jambo_table" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Id</th>
                                    <th>Batch</th>
                                    <th>Total Leads</th>
                                    <th>Status</th>
                                    <th>User</th>
                                    <th>Created At</th>
                                    <th>Updated At</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($batchEmails as $key => $batchEmail)
                                    <tr>
                                        <td>{{ $batchEmail->id }}</td>
                                        <td>{{ $batchEmail->batch }}</td>
                                        <td>{{ $batchEmail->total_leads }}</td>
                                        <td>{{ $batchEmail->status }}</td>
                                        <td>{{ $batchEmail->createdby ? $batchEmail->createdby->email : '' }}</td>
                                        <td>{{ $batchEmail->created_at }}</td>
                                        <td>{{ $batchEmail->updated_at }}</td>
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