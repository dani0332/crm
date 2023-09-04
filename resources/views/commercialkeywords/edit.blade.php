@extends('layouts.app')
@section('title', 'Edit Commercial Keyword')
@section('content')
    <div class="row">
        <div class="col-md-12 col-sm-12 ">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Edit Commercial Keyword</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li><a href="{{ route('admin.commercial.keywords') }}" id="" class="btn btn-warning">Keywords
                                List</a></li>
                    </ul>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <br />
                    @if (session()->has('success'))
                        <div class="alert alert-success">{{ session()->get('success') }}</div>
                    @endif
                    <form id="demo-form2" method='post'
                        action="{{ route('admin.commercial.keywords.update', ['commercialKeyword' => $commercialKeyword->id]) }}"
                        enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left"
                        autocomplete="off">
                        {{ csrf_field() }}
                        @method('PUT')
                        <div class="item form-group">
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Name <span class="required">*</span></span>
                                <input type="text" id="name" name="name"
                                    value="{{ old('name', $commercialKeyword->name) }}" class="form-control">
                                @if ($errors->has('name'))
                                    <span class="text-danger">{{ $errors->first('name') }}</span>
                                @endif
                            </div>
                        </div>
                        <div id='redirect_to_view_div'></div>
                        <div class="ln_solid"></div>
                        <div class="row">
                            <div class="col-auto mr-auto"></div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-warning btn-sm" id="return_to_view">Update</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
