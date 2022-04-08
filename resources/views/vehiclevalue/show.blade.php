@extends('layouts.app')
@section('title','Vehicle Value Detail')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-detail">
        <div class="x_panel">
            <div class="x_title">
                <h2>Vehicle Value Detail</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('vehiclevalue.index') }}" class="btn btn-warning btn-sm">Vehicle Value List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                <form id="demo-form2" method='post' action="{{ route('vehiclevalue.update', ['vehiclevalue' => $vehiclevalue->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                    {{csrf_field()}}
                    @method('PUT')
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="text"><b>Car Make</b></label>
                            <div class="col-md-6 col-sm-6">
                                <p class="label-align-center">{{ $carMake ? $carMake->text : '' }}</p>
                               
                           </div>
                       </div>
                       <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text"><b>Car Model</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $carModel ? $carModel->text : ''}}</p>
                            
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text"><b>Car Trim</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $carModelDetail ? $carModelDetail->text : ''}}</p>
                            
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text"><b>Insurance Provider</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $insuranceProvider ? $insuranceProvider->text : '' }}</p>
                            
                        </div>
                    </div>
                    
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text"><b>Current Value</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $vehiclevalue->current_value }}</p>
                        </div>
                    </div>
                    <div class="col">
                    </div>
                </div>
                <div class="ln_solid"></div>
                <div class="row">
                    <div class="col-auto mr-auto"></div>
                    <div class="col-auto">
                        @can('vehicle-depreciation-edit')
                        <a id="texta" href="{{ route('vehiclevalue.edit', ['vehiclevalue' => $vehiclevalue->id]) }}" class='btn btn-warning btn-sm'>Edit </a>
                        @endcan
                        @can('vehicle-depreciation-delete')
                        <a href="#" date-route="{{ route('vehiclevalue.destroy', ['vehiclevalue' => $vehiclevalue->id]) }}" class='btn btn-warning btn-sm delete'>Delete</a>
                        @endcan
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
@can('auditable')
<div id="auditable">
    <button id='auditablebtn' class="btn btn-warning btn-sm auditablebtn" data-id="{{ $vehiclevalue->id }}" data-model="App\Models\vehiclevalue">
        View Audit Logs
    </button>
</div>
@endcan
@endsection
