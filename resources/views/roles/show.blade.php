@extends('layouts.app')
@section('title','Role Detail')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Role Detail</h2>
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
                            {{ $role->id }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Name 
                        </td>
                        <td>
                            {{ $role->name }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Permissions 
                        </td>
                        <td>
                            @foreach($rolePermissions->chunk(4) as $chunk)
                            <div class="row">
                                <div class="col-md-12" style="padding:15px;">
                                    @foreach($chunk as $item)
                                    <span class="badge badge-pill" style="margin:5px;font-size:13px;">{{ $item->name }} <input checked type="checkbox" class="flat" value="{{ $item->id }}" name='permission[]' /></span>
                                    @endforeach 
                                </div>
                            </div>
                                <hr />
                            @endforeach
                        </td>
                    </tr>
                    <tr>
                        @can('role-edit')
                        <td >
                            <a href="{{ route('roles.edit', ['role' => $role->id]) }}" class='no-style-btn'><i class="fa fa-edit"></i> </a>
                        </td>
                        @endcan
                        @can('role-delete')
                        <td >
                            <form action="{{ route('roles.destroy', ['role' => $role->id]) }}" method="POST">  
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