@extends('layouts.app')
@section('title','Batch Detail')
@section('content')
@php
    use App\Enums\RolesEnum;
@endphp
<div class="row">
    <div class="col-md-12 col-sm-12 admin-detail">
        <div class="x_panel">
            <div class="x_title">
                <h2>Plans Processes</h2>
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
                        @hasanyrole(RolesEnum::RenewalsManager.'|'.RolesEnum::Admin)
                        <li><a class="btn btn-info btn-sm mr-4" onclick="return confirm('Do you want to fetch Plans?');" href="{{url('renewals/batches/' . $batch . '/fetch-plans')}}">Fetch Plans</a></li>
                        @endhasanyrole
                    </ul>
                    <table id="datatable" class="table table-striped jambo_table" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Id</th>
                                    <th>Batch</th>
                                    <th>Total Leads</th>
                                    <th>Completed</th>
                                    <th>Failed</th>
                                    <th>Status</th>
                                    <th>User</th>
                                    <th>Created At</th>
                                    <th>Updated At</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($planProcesses as $key => $planProcess)
                                    <tr>
                                        <td>{{ $planProcess->id }}</td>
                                        <td>{{ $planProcess->batch }}</td>
                                        <td>{{ $planProcess->total_leads }}</td>
                                        <td>{{ $planProcess->total_completed }}</td>
                                        <td>{{ $planProcess->total_failed }}</td>
                                        <td>{{ $planProcess->status }}</td>
                                        <td>{{ $planProcess->createdBy ? $planProcess->createdBy->email : '' }}</td>
                                        <td>{{ $planProcess->created_at }}</td>
                                        <td>{{ $planProcess->updated_at }}</td>
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
