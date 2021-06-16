@extends('layouts.app')
@section('title','Role Detail')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Role Detail</h2>
                 <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('roles.index') }}" class="btn btn-warning btn-sm">Roles List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                    <form id="demo-form2" method='post' enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="name"><b>Name</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $role->name }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="name"><b>Permissions</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">
                                @foreach($rolePermissions as $rolePermission)
                                    {{ $rolePermission->name }}
                                @endforeach
                            </p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="email"><b>Created At</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $role->created_at }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="email"><b>Updated At</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $role->updated_at }}</p>
                        </div>
                    </div>
                    <div class="ln_solid"></div>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <a href="{{ route('roles.edit', ['role' => $role->id]) }}" class='btn btn-warning btn-sm'>Edit</a>
                            <a href="#" date-route="{{ route('roles.destroy', ['role' => $role->id]) }}" class='btn btn-warning btn-sm delete'>Delete</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@can('auditable')
    <div id="auditable">
        <button id='auditablebtn' class="btn btn-warning btn-sm auditablebtn" data-id="{{ $role->id }}" data-model="App\Models\Role">
            View Audit Logs
        </button>
    </div>
@endcan
@endsection
