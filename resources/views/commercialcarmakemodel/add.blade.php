@extends('layouts.app')
@section('title', 'Configure Commercial car make & model')
@section('content')
    <div class="row">
        <div class="col-md-12 col-sm-12 ">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Select Car Make & Model to assign commercial status</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li><a href="{{ route('admin.configure.commerical.vehicles') }}"
                                class="btn btn-warning btn-sm">Commercial Vehicles List</a></li>
                    </ul>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <br />
                    @if (session()->has('success'))
                        <div class="alert alert-success">{{ session()->get('success') }}</div>
                    @endif
                    <form id="demo-form2" method='post' action="{{ route('admin.configure.commerical.vehicles.store') }}"
                        data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                        {{ csrf_field() }}
                        <div class="item form-group">
                            <div class="col-4">
                                <span class="col-form-label col-md-6 col-sm-6" for="car_make_id">
                                    Car Make
                                    <span class='required'>*</span>
                                </span>
                                <select class="form-control" name="car_make_id" id="car_make_id">
                                    <option selected disabled>Please select Car Make</option>
                                    @if ($carsMake)
                                        @foreach ($carsMake as $carMake)
                                            <option value="{{ $carMake->id }}"
                                                @if (old('car_make_id') == $carMake->id) selected @endif
                                                @if (4000 == $carMake->id) selected @endif
                                                data-id="{{ $carMake->code }}">{{ $carMake->text }}</option>
                                        @endforeach
                                    @endif
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
                                    <option disabled>Please select Car Make First</option>
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
                                <button type="submit" class="btn btn-warning btn-sm">Assign</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
