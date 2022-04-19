@extends('layouts.app')
@section('title','Vehicle Value')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Vehicle Value</h2>
                <ul class="nav navbar-right panel_toolbox">
                    @can('vehicle-depreciation-create')
                    <li><a href="{{ url('valuation/vehiclevalue/create') }}" class="btn btn-warning btn-sm">Create Vehicle Value</a></li>
                    @endcan
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('message'))
                <div class="alert alert-danger">{{ session()->get('message') }}</div>
                @endif
                <table  class="table table-striped jambo_table vehiclevalue-data-table" style="width:100%">
                  <thead>
                    <tr>
                      <th>Id</th>
                      <th>Car Make Id</th>
                      <th>Car Model Id</th>
                      <th>Car Trim Id</th>
                      <th>Insurance Provider</th>
                      <th>Current Value</th>
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
