@extends('layouts.app')
@section('title','View Claim')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Claims</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ url('claim/claims/create') }}" class="btn btn-warning btn-sm">Create Claim</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                <table class="table table-striped jambo_table claim-data-table" style="width:100%">
                      <thead>
                        <tr>
                          <th>Id</th>
                          <th>Policy Number</th>
                          <th>First Name</th>
                          <th>Last Name</th>
                          <th>Email Address</th>
                          <th>Phone Number</th>
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
