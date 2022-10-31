@extends('layouts.app')
@section('title','View Users')
@section('content')
<style>
        .select2-results__option--selected {
            display: none;
    }
    .select2-results__option[aria-selected=true] {
        display: none;
    }

    div.dataTables_wrapper div.dataTables_processing{
        font-size: 30px !important;
        border: none !important;
        background-color: transparent !important;
        color: #4183BD !important;
        padding: 0px  !important;
        height: 110px !important;
        width: 250px !important;
    }

    .dataTables_paginate .paginate_button.active {
        background: blue !important;
    }
    .pagination{
        margin-top: 12px !important;
    }
    .dataTables_paginate .paginate_button.active a {
        background: #71A1CC !important;
        border-radius: 3px;
        color: white;
    }

</style>
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
                <form method="POST" id="search-users" class="form-horizontal form-label-left" role="form"
                    data-parsley-validate="" novalidate="" autocomplete="off">
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
                            <th style="width: 100px !important">id</th>
                            <th style="width: 100px !important">Name</th>
                            <th style="width: 100px !important">Email</th>
                            <th style="width: 100px !important">Roles</th>
                            <th style="width: 100px !important">Primary Team Name</th>
                            <th style="width: 100px !important">IsActive</th>
                            <th style="width: 100px !important">Created At</th>
                            <th style="width: 100px !important">Updated At</th>
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
