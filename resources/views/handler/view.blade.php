@extends('layouts.app')
@section('title','View Handler')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Handler</h2>
                @can('role-create')
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('handler.create') }}" class="btn btn-warning btn-sm">Create Handler</a></li>
                </ul>
                @endcan

                <div class="clearfix"></div>

            </div>
            <div class="x_content">
                <br />
                <table  class="table table-striped jambo_table handler-data-table" style="width:100%">
                      <thead>
                        <tr>
                          <th>id</th>
                          <th>Name</th>
                          <th>Is Active</th>
                          <th>Created By</th>
                          <th>Updated By</th>
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
