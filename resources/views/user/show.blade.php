@extends('layouts.app')
@section('title','User Detail')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>User Detail</h2>
                 <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('users.index') }}" class="btn btn-warning btn-sm">Users List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                <form id="demo-form2" method='post' action="{{ route('users.update', ['user' => $user->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                {{csrf_field()}}
                @method('PUT')
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Name"><b>Name</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $user->name }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Email"><b>Email</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $user->email }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Roles"><b>Roles</b></label>
                        <div class="col-md-6 col-sm-6">
                            @foreach($user->usersroles as $userrole)
                                <button type="button" class="btn btn-disabled">{{ $userrole->name }}</button>
                            @endforeach
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Updated At"><b>User's Team</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $teamName }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Created At"><b>Created At</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $user->created_at }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Updated At"><b>Updated At</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $user->updated_at }}</p>
                        </div>
                    </div>
                    <div class="ln_solid"></div>
                    <div class="row">
                        <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <a href="{{ route('users.edit', ['user' => $user->id])}}" class='btn btn-warning btn-sm'>Edit</a>
                            <a href="#" date-route="{{ route('users.destroy', ['user' => $user->id])}}" class='btn btn-warning btn-sm delete'>Delete</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div id="auditable">
    <button id='auditablebtn' class="btn btn-warning btn-sm auditablebtn" data-id="{{ $user->id }}" data-model="App\Models\User">
        View Audit Logs
    </button>
</div>
@endsection
