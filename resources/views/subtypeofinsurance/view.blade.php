@extends('layouts.app')
@section('title','View Sub Type of Insurance')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Sub Type of Insurance</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ url('claim/subtypeofinsurance/create') }}" class="btn btn-warning btn-sm">Create Sub Type of Insurance</a></li>
                </ul>
                <div class="clearfix"></div>
                
            </div>
            <div class="x_content">
                <br />
                <table  class="table table-striped jambo_table subtypeofinsurance-data-table" style="width:100%">
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