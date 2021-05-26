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
                <table class="table table-striped">
                    <tr>
                        <td>
                            Id 
                        </td>
                        <td>
                            {{ $user->id }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Name 
                        </td>
                        <td>
                            {{ $user->name }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Email 
                        </td>
                        <td>
                            {{ $user->email }}
                        </td>
                    </tr>
                    <tr>
                    @can('users-edit')
                        <td >
                            <a href="{{ route('users.edit', ['user' => $user->id]) }}" class='no-style-btn'><i class="fa fa-edit"></i> </a>
                        </td>
                    @endcan    
                    @can('users-delete')
                    <td >
                        <form action="{{ route('users.destroy', ['user' => $user->id]) }}" method="POST">  
                            @csrf 
                            @method('DELETE')
                            <button type="submit" class="no-style-btn"><i class="fa fa-trash"></i></button>
                        </form>
                    </td>
                    @endcan
                    </tr>
                </table >
                    
                </div>
            </div>
        </div>
    </div>
</div>
@endsection