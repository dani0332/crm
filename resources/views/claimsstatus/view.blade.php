@extends('layouts.app')
@section('title','View Claim Status')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Claim Status</h2>
                <ul class="nav navbar-right panel_toolbox">
                    @can('claims-status-create')
                    <li><a href="{{ url('claim/claimsstatus/create') }}" class="btn btn-warning btn-sm">Create Claim Status</a></li>
                    @endcan
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('message'))
                    <div class="alert alert-danger">{{ session()->get('message') }}</div>
                @endif
                <table  class="table table-striped jambo_table claimsstatus-data-table" style="width:100%">
                      <thead>
                        <tr>
                          <th>Id</th>
                          <th>Text</th>
                          <th>Text Ar</th>
                          <th>Sort Order</th>
                          <th>Is Active</th>
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
