@extends('layouts.app')
@section('title','Vehicle Depreciation')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Vehicle Depreciation</h2>
                <ul class="nav navbar-right panel_toolbox">
                    @can('vehicle-depreciation-create')
                    <li><a href="{{ url('valuation/vehicledepreciation/ceate') }}" class="btn btn-warning btn-sm">Create Vehicle Depreciation</a></li>
                    @endcan
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('message'))
                    <div class="alert alert-danger">{{ session()->get('message') }}</div>
                @endif
                <table  class="table table-striped jambo_table vehicledepreciation-data-table" style="width:100%">
                      <thead>
                        <tr>
                          <th>Id</th>
                          <th>Car Model Id</th>
                          <th>Car Make Id</th>
                          <th>First Year</th>
                          <th>Second Year</th>
                          <th>Third Year</th>
                          <th>Fourth Year</th>
                          <th>Fifth Year</th>
                          <th>Sixth Year</th>
                          <th>Seventh Year</th>
                          <th>Eighth Year</th>
                          <th>Ninth Year</th>
                          <th>Tenth Year</th>
                          <th>Upper Limit</th>
                          <th>Lower Limit</th>
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
