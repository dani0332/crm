@extends('layouts.app')
@section('title','View Rent a Car')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Rent a Car</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ url('claim/rentacar/create') }}" class="btn btn-warning btn-sm">Create Rent a Car</a></li>
                </ul>
                <div class="clearfix"></div>
<<<<<<< HEAD

=======
>>>>>>> develop
            </div>
            <div class="x_content">
                <br />
                <table  class="table table-striped jambo_table rentacar-data-table" style="width:100%">
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
