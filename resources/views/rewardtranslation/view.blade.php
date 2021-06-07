@extends('layouts.app')
@section('title','View Reward')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Rewards</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ url('rewards/reward/create') }}" class="btn btn-success btn-sm">Create reward</a></li>
                </ul>
                <div class="clearfix"></div>

            </div>
            <div class="x_content">
                <br />
                <table id="datatable" class="table table-striped jambo_table" style="width:100%">
                      <thead>
                        <tr>
                          <th>Id</th>
                          <th>Coupon Code</th>
                          <th>Partner</th>
                          <th>Discount</th>
                          <th>Start Date</th>
                          <th>End Date</th>
                          <th>Is Active</th>
                          <th>Is Flat Discount</th>
                        </tr>
                      </thead>


                      <tbody>

                        @foreach($rewards as $key => $reward)
                        <tr>
                          <td>{{ $reward->id }}</td>
                          <td>{{ $reward->coupon_code }}</td>
                          <td>{{ $reward->partner_id }}</td>
                          <td>{{ $reward->discount }}</td>
                          <td>{{ $reward->start_date }}</td>
                          <td>{{ $reward->end_date }}</td>
                          <td>{{ $reward->is_active }}</td>
                          <td>{{ $reward->is_flat_discount }}</td>
                        </tr>
                        @endforeach

                      </tbody>
                    </table>
            </div>
        </div>
    </div>
</div>
@endsection
