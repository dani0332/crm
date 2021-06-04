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
                          <th>Actions</th>
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
                          <td><a href="{{ route('reward.edit', ['reward' => $reward->id]) }}" class='btn btn-info btn-sm'><i class="fa fa-edit"></i> </a>
                          <form action="{{ route('reward.destroy', ['reward' => $reward->id]) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></button>
                            </form>
                            <a href="{{ route('reward.show', ['reward' => $reward->id]) }}" class='btn btn-info btn-sm'><i class="fa fa-eye"></i> </a>
                          </td>
                        </tr>
                        @endforeach

                      </tbody>
                    </table>
            </div>
        </div>
    </div>
</div>
@endsection
