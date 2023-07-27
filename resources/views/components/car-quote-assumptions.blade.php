@php
use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
@endphp
<meta name="csrf-token" content="{{ csrf_token() }}" />
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<script>

$(document).ready(function() {
    $("#update-motor-assumptions-btn").hide();
    $("#cancel-motor-assumptions-btn").hide();
    $("#update-assumptions-form :input"). prop("disabled", true);
    $('#edit-motor-assumptions-btn').prop('disabled', false);

    $("#edit-motor-assumptions-btn").click(function(){
        $("#edit-motor-assumptions-btn").hide();
        $("#update-motor-assumptions-btn").show();
        $("#cancel-motor-assumptions-btn").show();
        $("#update-assumptions-form :input"). prop("disabled", false);
    });
    $("#cancel-motor-assumptions-btn").click(function(){
        $("#edit-motor-assumptions-btn").show();
        $("#update-motor-assumptions-btn").hide();
        $("#cancel-motor-assumptions-btn").hide();
        $("#update-assumptions-form :input"). prop("disabled", true);
        $('#edit-motor-assumptions-btn').prop('disabled', false);
    });

    $("#error-motor-assumptions-form").hide();
    $("#update-assumptions-form").submit(function (e) {
        if($("#cylinder").val() == "" || $("#seat_capacity").val() == ""
            || $("#vehicle_type_id").val() == "" || $("#current_insurance_status").val() == ""
            || $("#year_of_first_registration").val() == "") {
            $("#error-motor-assumptions-form").show();
            return false;
        } else {
            $("#error-motor-assumptions-form").hide();
            return true;
        }
    });
});
</script>
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Assumptions</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
            <form id="update-assumptions-form" method='post' action="{{ url('/quotes/car/carAssumptionsUpdate') }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
            {{csrf_field()}}
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="cylinder"><b>Cylinders</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">
                        <input type="number" class="form-control" id="cylinder" name="cylinder" value="{{ $record->cylinder }}">
                        </p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="seat_capacity"><b>Seat Capacity</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">
                        <input type="number" class="form-control" id="seat_capacity" name="seat_capacity" value="{{ $record->seat_capacity }}">
                        </p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Vehicle Body Type"><b>Vehicle Body Type</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">
                                <select class="form-control" id="vehicle_type_id" name="vehicle_type_id">
                                <option value=""></option>
                                @foreach($vehicleTypes as $vehicleType)
                                    <option value="{{$vehicleType->id}}"
                                    {{ $vehicleType->id == old('vehicle_type_id',$record->vehicle_type_id) ? 'selected' : ''}}>{{ $vehicleType->text }}</option>
                                @endforeach
                                </select>
                            </p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="is_modified"><b>Is Vehicle modified?</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">
                            <select class="form-control" id='is_modified' name="is_modified">
                            <option value="0" {{ $record->is_modified == "0" ? 'selected="selected"' : '' }}>No</option>
                            <option value="1" {{ $record->is_modified == "1" ? 'selected="selected"' : '' }}>Yes</option>
                            </select>
                        </p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="is_bank_financed"><b>Is Bank Financed?</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">
                            <select class="form-control" id='is_bank_financed' name="is_bank_financed">
                            <option value="0" {{ $record->is_bank_financed == "0" ? 'selected="selected"' : '' }}>No</option>
                            <option value="1" {{ $record->is_bank_financed == "1" ? 'selected="selected"' : '' }}>Yes</option>
                            </select>
                        </p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="is_gcc_standard"><b>Is GCC Standard?</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">
                            <select class="form-control" id='is_gcc_standard' name="is_gcc_standard">
                            <option value="0" {{ $record->is_gcc_standard == "0" ? 'selected="selected"' : '' }}>No</option>
                            <option value="1" {{ $record->is_gcc_standard == "1" ? 'selected="selected"' : '' }}>Yes</option>
                            </select>
                        </p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="current_insurance_status"><b>Current Insurance</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">
                            <select class="form-control" id='current_insurance_status' name="current_insurance_status">
                            <option value=""></option>
                            <option value="ACTIVE_TPL" {{ $record->current_insurance_status == "ACTIVE_TPL" ? 'selected="selected"' : '' }}>ACTIVE_TPL</option>
                            <option value="ACTIVE_COMP" {{ $record->current_insurance_status == "ACTIVE_COMP" ? 'selected="selected"' : '' }}>ACTIVE_COMP</option>
                            <option value="EXPIRED" {{ $record->current_insurance_status == "EXPIRED" ? 'selected="selected"' : '' }}>EXPIRED</option>
                            </select>
                        </p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="year_of_first_registration"><b>Year Of First Registration</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">
                            <select class="form-control" id="year_of_first_registration" name="year_of_first_registration">
                            <option value=""></option>
                            @foreach($yearsOfManufacture as $yearOfManufacture)
                                <option value="{{$yearOfManufacture->id}}"
                                {{ $yearOfManufacture->id == old('year_of_first_registration',$record->year_of_first_registration) ? 'selected' : ''}}>{{ $yearOfManufacture->text }}</option>
                            @endforeach
                            </select>
                        </p>
                        </div>
                    </div>
                </div>
                @if(! auth()->user()->hasRole(RolesEnum::PA))
                    @can('car-quotes-edit')
                        <div align="right">
                            <span style="color:red;" id="error-motor-assumptions-form">All fields are required.</span>
                            <input type="hidden" id="car_quote_id" name="car_quote_id" value="{{ $record->id }}">
                            <button type="button" class="btn btn-primary btn-sm" id="cancel-motor-assumptions-btn">Cancel</button>
                            @cannot(PermissionsEnum::ApprovePayments)
                                <button type="submit" class="btn btn-success btn-sm" id="update-motor-assumptions-btn">Update</button>
                            @if($access['carManagerCanEdit'] || $access['carAdvisorCanEdit'])
                                    <button type="button" class="btn btn-primary btn-sm" id="edit-motor-assumptions-btn">Edit Assumptions</button>
                                @elseif(!auth()->user()->hasAnyRole([RolesEnum::CarAdvisor, RolesEnum::CarManager]) )
                                    <button type="button" class="btn btn-primary btn-sm" id="edit-motor-assumptions-btn">Edit Assumptions</button>
                                @endif
                            @endcannot
                        </div>
                    @endcan
                @endif
            </form>
            </div>
        </div>
    </div>
</div>
