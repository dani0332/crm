@extends('layouts.app')
@section('title','View User')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Users</h2>
                @can('users-create')
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ url('admin/users/create') }}" class="btn btn-success btn-sm">Create User</a></li>
                </ul>
                @endcan
                <div class="clearfix"></div>
                
            </div>
            <div class="x_content">
                <br />
                <table  class="table table-striped table-bordered user-data-table" style="width:100%">
                      <thead>
                        <tr>
                          <th>id</th>
                          <th>Name</th>
                          <th>Email</th>
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