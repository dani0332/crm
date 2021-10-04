@extends('layouts.app')
@section('title', 'Add Age Discount')
@section('content')
    <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Create Age Discount</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li><a href="{{ url('discount/age') }}" class="btn btn-warning btn-sm">Age Discount
                                List</a></li>
                    </ul>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <br />
                    @if (session()->has('success'))
                        <div class="alert alert-success">{{ session()->get('success') }}</div>
                    @endif
                    @if (session()->has('message'))
                        <div class="alert alert-danger">{{ session()->get('message') }}</div>
                    @endif
                    <form id="demo-form2" method='post' action="{{ url('discount/age') }}" enctype="multipart/form-data"
                        data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                        {{ csrf_field() }}
                        <div class="item form-group">
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Start Age<span
                                        class="required">*</span></span>
                                <input type="text" id="age_start" name="age_start" value="{{ old('age_start') }}"
                                    class="form-control">
                                @if ($errors->has('age_start'))
                                    <span class="text-danger">{{ $errors->first('age_start') }}</span>
                                @endif
                            </div>
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">End Age <span
                                        class="required">*</span></span>
                                <input type="text" id="age_end" name="age_end" value="{{ old('age_end') }}"
                                    class="form-control">
                                @if ($errors->has('age_end'))
                                    <span class="text-danger">{{ $errors->first('age_end') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="item form-group">
                            <div class="col">
                                <span class="col-form-label col-md-6 col-sm-6">Discount<span
                                        class="required">*</span></span>
                                <input type="text" id="discount" name="discount" value="{{ old('discount') }}"
                                    class="form-control">
                                @if ($errors->has('discount'))
                                    <span class="text-danger">{{ $errors->first('discount') }}</span>
                                @endif
                            </div>
                            <div class="col">

                            </div>
                        </div>

                        <div id='redirect_to_view_div'></div>
                        <div class="ln_solid"></div>
                        <div class="row">
                            <div class="col-auto mr-auto"></div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-warning btn-sm" id="return_to_view">Create</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
