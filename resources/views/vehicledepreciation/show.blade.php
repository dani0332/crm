@extends('layouts.app')
@section('title','Vehicle Depreciation Detail')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-detail">
        <div class="x_panel">
            <div class="x_title">
                <h2>Vehicle Depreciation Detail</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('vehicledepreciation.index') }}" class="btn btn-warning btn-sm">Vehicle Depreciation List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                <form id="demo-form2" method='post' action="{{ route('vehicledepreciation.update', ['vehicledepreciation' => $vehicledepreciation->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                    {{csrf_field()}}
                    @method('PUT')
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="text"><b>Car Make</b></label>
                            <div class="col-md-6 col-sm-6">
                                <p class="label-align-center">{{ $carMake }}</p>
                               
                           </div>
                       </div>
                       <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text"><b>Car Model</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $carModel }}</p>
                            
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
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text"><b>First Year</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $vehicledepreciation->first_year }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text"><b>Second Year</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $vehicledepreciation->second_year }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text"><b>Third Year</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $vehicledepreciation->third_year }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text"><b>Fourth Year</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $vehicledepreciation->fourth_year }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text"><b>Fifth Year</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $vehicledepreciation->fifth_year }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text"><b>Sixth Year</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $vehicledepreciation->sixth_year }}</p>
                        </div>
                    </div>
                    
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text"><b>Seventh Year</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $vehicledepreciation->seventh_year }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text"><b>Eighth Year</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $vehicledepreciation->eighth_year }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text"><b>Ninth Year</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $vehicledepreciation->ninth_year }}</p>
                        </div>
                    </div>
                    <div class="col">

                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text"><b>Tenth Year</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $vehicledepreciation->tenth_year }}</p>
                        </div>
                    </div>
                </div>

                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text"><b>Upper Limit</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $vehicledepreciation->upper_limit }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text"><b>Lower Limit</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $vehicledepreciation->lower_limit }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">

                </div>
                <div class="ln_solid"></div>
                <div class="row">
                    <div class="col-auto mr-auto"></div>
                    <div class="col-auto">
                        @can('vehicle-depreciation-edit')
                        <a id="texta" href="{{ route('vehicledepreciation.edit', ['vehicledepreciation' => $vehicledepreciation->id]) }}" class='btn btn-warning btn-sm'>Edit </a>
                        @endcan
                        @can('vehicle-depreciation-delete')
                        <a href="#" date-route="{{ route('vehicledepreciation.destroy', ['vehicledepreciation' => $vehicledepreciation->id]) }}" class='btn btn-warning btn-sm delete'>Delete</a>
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
    <button id='auditablebtn' class="btn btn-warning btn-sm auditablebtn" data-id="{{ $vehicledepreciation->id }}" data-model="App\Models\VehicleDepreciation">
        View Audit Logs
    </button>
</div>
@endcan
@endsection
