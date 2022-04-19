@extends('layouts.app')
@section('title','Vehicle Range Detail')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-detail">
        <div class="x_panel">
            <div class="x_title">
                <h2>Vehicle Range Detail</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('vehiclerange.index') }}" class="btn btn-warning btn-sm">Vehicle Range List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                <form id="demo-form2" method='post' action="{{ route('vehiclerange.update', ['vehiclerange' => $vehiclerange->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                    {{csrf_field()}}
                    @method('PUT')
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="text"><b>Car Make</b></label>
                            <div class="col-md-6 col-sm-6">
                                <p class="label-align-center">{{ isset($carMake) ? $carMake->text : '' }}</p>
                               
                           </div>
                       </div>
                       <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text"><b>Car Model</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ isset($carModel) ? $carModel->text : '' }}</p>
                            
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text"><b>Insurance Provider</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $insuranceProvider ? $insuranceProvider->text : '' }}</p>
                            
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text"><b>Lower Limit</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $vehiclerange->lower_limit }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text"><b>Upper Limit</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $vehiclerange->upper_limit }}</p>
                        </div>
                    </div>
                    <div class="col">
                    </div>
                    
                </div>
                <div class="item form-group">

                </div>
                <div class="ln_solid"></div>
                <div class="row">
                    <div class="col-auto mr-auto"></div>
                    <div class="col-auto">
                        @can('vehicle-depreciation-edit')
                        <a id="texta" href="{{ route('vehiclerange.edit', ['vehiclerange' => $vehiclerange->id]) }}" class='btn btn-warning btn-sm'>Edit </a>
                        @endcan
                        @can('vehicle-depreciation-delete')
                        <a href="#" date-route="{{ route('vehiclerange.destroy', ['vehiclerange' => $vehiclerange->id]) }}" class='btn btn-warning btn-sm delete'>Delete</a>
                        @endcan
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
@endsection
