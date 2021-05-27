@extends('layouts.app')
@section('title','User Detail')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>User Detail</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                <form id="demo-form2" method='post' action="{{ route('users.update', ['user' => $user->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">Name <span class="required">*</span>
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $user->name }}</p>
                    </div>
                    
                </div>
                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="email">Email <span class="required">*</span>
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $user->email }}</p>
                    </div>
                    
                </div>
                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="roles">Role <span class="required">*</span>
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">Admin</p>
                    </div>
                </div>
                <div class="ln_solid"></div>
                <div class="item form-group">
                    <div class="col-md-6 col-sm-6 offset-md-3">
                        <a href="{{ route('users.edit', ['user' => $user->id])}}"  class='btn btn-warning btn-sm'>Edit</a>
                        <form action="{{ route('users.destroy', ['user' => $user->id])}}" method='POST'>  
                            @csrf 
                            @method('DELETE')
                            <button type='submit' class='btn btn-warning btn-sm'>Delete</button>
                        </form>
                    </div>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>

<x-auditable  :auditableId="$user->id" auditableType="App\Models\User"/>
@endsection