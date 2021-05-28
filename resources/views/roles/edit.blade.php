@extends('layouts.app')
@section('title','Edit Role')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Edit Role</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">
                        {{ session()->get('success') }}
                    </div>
                @endif
                <form id="demo-form2" method='post' action="{{ route('roles.update', ['role' => $role->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                {{csrf_field()}}
                @method('PUT')
                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">Name <span class="required">*</span>
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <input type="text" id="name" name="name" value="{{ $role->name }}"  class="form-control ">
                        @if ($errors->has('name'))
                            <span class="text-danger">{{ $errors->first('name') }}</span>
                        @endif
                    </div>

                </div>
                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">Permission <span class="required">*</span>
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        @foreach($permission->chunk(4) as $chunk)
                            <div class="row">
                                <div class="col-md-12">
                                    @foreach($chunk as $item)
                                        <span class="badge badge-pill" style="margin:5px;font-size:13px;">  {{ $item->name }} <input {{ in_array($item->id, $rolePermissions) ? 'checked' : '' }} type="checkbox" class="flat" value="{{ $item->id }}" name='permission[]' /></span>
                                    @endforeach
                                </div>
                            </div>
                            <hr />
                        @endforeach
                    </div>

                </div>
                <div class="ln_solid"></div>
                <div class="item form-group">
                    <div class="col-md-6 col-sm-6 offset-md-3">
                      <button type="submit" class="btn btn-warning">Update</button>
                    </div>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
