@extends('layouts.app')
@section('title','View Payment Mode')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Payment Mode</h2>
                @can('role-create')
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('paymentmode.create') }}" class="btn btn-warning btn-sm">Create Payment Mode</a></li>
                </ul>
                @endcan

                <div class="clearfix"></div>

            </div>
            <div class="x_content">
                <br />
                <table  class="table table-striped jambo_table paymentmode-data-table" style="width:100%">
                      <thead>
                        <tr>
                          <th>id</th>
                          <th>Name</th>
                          <th>Is Active</th>
                          <th>Created By</th>
                          <th>Modified By</th>
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
