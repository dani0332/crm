@extends('layouts.app')
@section('title','View Upload TM Lead')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Upload TM Lead</h2>
                <ul class="nav navbar-right panel_toolbox">
                    @can('tm-upload-leads-create')
                    <li><a href="{{ url('telemarketing/tmuploadlead/create') }}" class="btn btn-warning btn-sm">Create Upload TM Leads File</a></li>
                    @endcan
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('message'))
                    <div class="alert alert-danger">{{ session()->get('message') }}</div>
                @endif
                <table  class="table table-striped jambo_table tmuploadlead-data-table" style="width:100%">
                      <thead>
                        <tr>
                          <th>Id</th>
                          <th>File</th>
                          <th>Total Records</th>
                          <th>Good</th>
                          <th>Cannot Upload</th>
                          <th>Is Submitted</th>
                          <th>Submitted by</th>
                          <th>Created At</th>
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
