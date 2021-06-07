@extends('layouts.app')
@section('title','View Reward')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Rewards</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ url('rewards/reward/create') }}" class="btn btn-warning btn-sm">Create reward</a></li>
                </ul>
                <div class="clearfix"></div>

            </div>
            <div class="x_content">
                <br />
                <table class="table table-striped jambo_table reward-data-table">
                      <thead>
                        <tr>
                        <th>Id</th>
                          <th>Coupon Code</th>
                          <th>Partner</th>
                          <th>Discount</th>
                          <th>Start Date</th>
                          <th>End Date</th>
                          <th>Is Active</th>
                          {{-- <th>Is Flat Discount</th> --}}
                          <th>Actions</th>
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
