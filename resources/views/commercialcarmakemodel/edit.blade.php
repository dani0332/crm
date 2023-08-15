@extends('layouts.app')
@section('title', 'Edit Commercial Keyword')
@section('content')
    <div class="row">
        <div class="col-md-12 col-sm-12 ">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Edit Commercial Vehicle</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li><a href="{{ route('admin.configure.commerical.vehicles') }}" id=""
                                class="btn btn-warning">Commercial Vehicle List</a></li>
                    </ul>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <br />
                    @if (session()->has('success'))
                        <div class="alert alert-success">{{ session()->get('success') }}</div>
                    @endif
                    <form id="demo-form2" method='post' action="{{ route('admin.configure.commerical.vehicles.store') }}"
                        enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left"
                        autocomplete="off">
                        {{ csrf_field() }}
                        <div class="item form-group">
                            <div class="col-4">
                                <span class="col-form-label col-md-6 col-sm-6" for="car_make_id">
                                    Car Make
                                    <span class='required'>*</span>
                                </span>
                                <select class="form-control" name="car_make_id" id="car_make_id" readonly>
                                    <option value="{{ $carMake->id }}" selected readonly data-id="{{ $carMake->code }}">
                                        {{ $carMake->text }}</option>
                                </select>
                                @if ($errors->has('car_make_id'))
                                    <span class="text-danger">{{ $errors->first('car_make_id') }}</span>
                                @endif
                            </div>

                            <div class="col-8">
                                <span class="col-form-label col-md-6 col-sm-6" for="car_make_ids">
                                    Car Model
                                    <span class='required'>*</span>
                                </span>
                                <select name="car_model_id[]" multiple="multiple" class="form-control select2 select-roles"
                                    id="car_model_id">
                                    @if (count($carMake->carModels) > 0)
                                        @foreach ($carMake->carModels as $carModel)
                                            <option value="{{ $carModel->id }}"
                                                @if (in_array($carModel->id, $commercialModels)) selected="selected" @endif>
                                                {{ $carModel->text }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                @if ($errors->has('car_make_id'))
                                    <span class="text-danger">{{ $errors->first('car_make_id') }}</span>
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
