@extends('layouts.app')
@section('title','Add Role')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Create Role</h2>
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
                <form id="demo-form2" method='post' action="{{ route('roles.store') }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                {{csrf_field()}}
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">Name <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input type="text" id="name" name="name" value="{{ old('name') }}" class="form-control">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">Permission <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            @foreach($permission->chunk(4) as $chunk)
                                <div class="row">
                                    <div class="col-md-12">
                                        @foreach($chunk as $item)
                                            <span class="badge badge-pill" style="margin:5px;font-size:13px;">{{ $item->name }} <input type="checkbox" class="flat" value="{{ $item->id }}" name='permission[]' /></span>
                                        @endforeach
                                    </div>
                                </div>
                                <hr />
                            @endforeach
                        </div>
                    </div>
                    <div id='redirect_to_view_div'></div>
                    <div class="ln_solid"></div>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-warning btn-sm">Create & Add New</button> <button type="submit" class="btn btn-warning btn-sm" id="return_to_view" >Create</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
