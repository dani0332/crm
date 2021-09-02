@extends('layouts.app')
@section('title','View TM Call Status')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>TM Call Status</h2>
                <ul class="nav navbar-right panel_toolbox">
                    @can('tm-call-status-create')
                    <li><a href="{{ url('telemarketing/tmcallstatus/create') }}" class="btn btn-warning btn-sm">Create TM Call Status</a></li>
                    @endcan
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('message'))
                    <div class="alert alert-danger">{{ session()->get('message') }}</div>
                @endif
                <table  class="table table-striped jambo_table tmcallstatus-data-table" style="width:100%">
                      <thead>
                        <tr>
                          <th>Id</th>
                          <th>Code</th>
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
