@extends('layouts.app')
@section('title','Edit Role')
@section('content')

<style>
    .js-example-basic-multiple{
        width: 100% !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove{
        color: white !important;
        top: 8px !important;
        border: none !important;
        left: 2px !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover{
        color: white !important;
        background-color: #3498db !important
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice{
        background-color: #3498db !important;
        color: white !important;
        padding: 8px 20px 8px 22px !important;
        font-family: calibri !important;
        font-size: 13px !important;
        font-weight: 600 !important;

    }
    .select2-container--default .select2-search--inline .select2-search__field{
        width: 100% !important;
    }
</style>
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Edit Role</h2>
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
                <form id="demo-form2" method='post' action="{{ route('roles.update', ['role' => $role->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                {{csrf_field()}}
                @method('PUT')
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">Name <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input type="text" id="name" name="name" value="{{ $role->name }}" class="form-control">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">Permission <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">

                        <select name="permission[]" multiple="multiple" style="margin-bottom:15px;"
                                            class="form-control js-example-basic-multiple select_multiple">
                            @foreach($permission->chunk(6) as $chunk)
                                @foreach($chunk as $skey=>$item)
                                <option value="{{$item->id }}"}} @if(in_array($item->id, $rolePermissions)) selected="selected" @endif>
                                {{ $item->name }}
                                </option>
                                @endforeach
                            @endforeach
                        </select>
                        </div>


                    </div>
                    <div id='redirect_to_view_div'></div>
                    <div class="ln_solid"></div>
                    <div class="row">
                        <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-warning btn-sm">Update & Continue Updating</button> <button type="submit" class="btn btn-warning btn-sm" id="return_to_view">Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
