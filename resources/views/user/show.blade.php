@extends('layouts.app')
@section('title','User Detail')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
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
                <form id="demo-form2" method='post' action="{{ route('users.update', ['user' => $user->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="name"><b>Name</b>
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $user->name }}</p>
                    </div>

                </div>
                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="email"><b>Email</b>
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $user->email }}</p>
                    </div>

                </div>

                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="email"><b>Created At</b>
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $user->created_at }}</p>
                    </div>

                </div>
                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="email"><b>Updated At</b>
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $user->updated_at }}</p>
                    </div>

                </div>
                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="roles"><b>Role</b>
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">Admin</p>
                    </div>
                </div>
                <div class="ln_solid"></div>
                <div class="item form-group">
                    <div class="col-md-6 col-sm-6 offset-md-3">
                        <a href="{{ route('users.edit', ['user' => $user->id])}}"  class='btn btn-warning btn-sm'>Edit</a>
                        <a href="#" date-route="{{ route('users.destroy', ['user' => $user->id])}}"   class='btn btn-warning btn-sm delete'>Delete</a>
                        {{-- <form action="" method='POST'>
                            @csrf
                            @method('DELETE')
                            <button type='submit' class='btn btn-warning btn-sm'>Delete</button>
                        </form> --}}
                    </div>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div id="auditable">
    <button id='auditablebtn' class="btn btn-warning auditablebtn" data-id="{{ $user->id }}" data-model="App\Models\User">
        View Audit Logs
    </button>
</div>
@endsection
