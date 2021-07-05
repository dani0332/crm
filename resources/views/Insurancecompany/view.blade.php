@extends('layouts.app')
@section('title','View Insurance Companies')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Insurance Companies</h2>
                @can('insurance-company-create')
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('insurancecompany.create') }}" class="btn btn-warning btn-sm">Create Insurance Company</a></li>
                </ul>
                @endcan
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('message'))
                    <div class="alert alert-danger">{{ session()->get('message') }}</div>
                @endif
                <table class="table table-striped jambo_table insurancecompany-data-table" style="width:100%">
                      <thead>
                        <tr>
                          <th>ID</th>
                          <th>Name</th>
                          <th>Is Active</th>
                          <th>Created By</th>
                          <th>Updated By</th>
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
