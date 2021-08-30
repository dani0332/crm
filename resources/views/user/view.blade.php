@extends('layouts.app')
@section('title','View Users')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Users</h2>
                @can('users-create')
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ url('admin/users/create') }}" class="btn btn-warning btn-sm">Create User</a></li>
                </ul>
                @endcan
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('message'))
                    <div class="alert alert-danger">{{ session()->get('message') }}</div>
                @endif
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                <form method="POST" id="search-users" class="form-horizontal form-label-left" role="form" data-parsley-validate="" novalidate="" autocomplete="off">
                    <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-2 col-sm-2" id='email' for="Email">Email</label>
                                <div class="col-md-6 col-sm-6">
                                    <div class="input-group">
                                        <input type="text" name="email" id="users_email" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-2 col-sm-2" id='name' for="Stop Date">Name</label>
                                <div class="col-md-6 col-sm-6">
                                    <div class="input-group">
                                    <input type="text" name="name" id="users_name" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <ul class="nav navbar-right panel_toolbox">
                                <li><input type="submit" class="btn btn-warning btn-sm"></li>
                                <li><input type="reset" class="btn btn-warning btn-sm"></li>
                                </ul>
                            </div>
                        </div>
                    </form>
                <table class="table table-striped jambo_table user-data-table" style="width:100%">
                    <thead>
                    <tr>
                        <th>id</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Roles</th>
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
