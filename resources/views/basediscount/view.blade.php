@extends('layouts.app')
@section('title', 'View Base Discount')
@section('content')
    <div class="row">
        <div class="x_panel">
            <div class="x_title">
                <h2>Search Base Discount</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ url('discount/base/create') }}" class="btn btn-warning btn-sm">Create Base Discount</a>
                    </li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <form method="POST" id="search-vehicleTypes" class="form-horizontal form-label-left" role="form"
                    data-parsley-validate="" novalidate="">
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-2 col-sm-2" id="vehicle_type_label"
                                for="Payment mode">Vehicle Type</label>
                            <div class="col-md-6 col-sm-6">
                                <div class="input-group">
                                    <select class="form-control" name="vehicle_type" id="vehicle_type">
                                        <option value="">Select Vehicle Type</option>
                                        @foreach ($vehicleTypes as $vehicleType)
                                            <option value="{{ $vehicleType->id }}">{{ $vehicleType->text }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <ul class="nav navbar-right panel_toolbox">
                                <li><input type="button" id="search-vehicleTypes-reset" value="Reset"
                                        class="btn btn-success btn-sm"></li>
                                <li><input type="submit" class="btn btn-warning btn-sm"></li>

                            </ul>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="x_panel">
            <div class="x_title">
                <h2>Base Discounts</h2>

                {{-- <ul class="nav navbar-right panel_toolbox">
                    <li><button id="resubmit_api_carquote" class="btn btn-success btn-sm">ReSubmit Api</button></li>
                </ul> --}}
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                {{-- <div id="success_message" class="alert alert-success" style="display:none"></div>
                <div id="error_message" class="alert alert-danger" style="display:none"></div> --}}
                <br />


                <br />
                <table class="table table-striped jambo_table base-discount-data-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>id</th>
                            <th>Value Start</th>
                            <th>Value End</th>
                            <th>Vehicle Type</th>
                            <th>Comprehensive (Non Agency) Discount</th>
                            <th>Agency Discount</th>
                            <th>Is Active</th>
                            <th>Created At</th>
                            <th>Updated At</th>
                        </tr>
                    </thead>


                    <tbody>



                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>
@endsection
