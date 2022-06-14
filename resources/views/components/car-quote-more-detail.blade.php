<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Assumptions</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="cylinder"><b>Cylinders</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">{{ $record->cylinder }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="seat_capacity"><b>Seat Capacity</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">{{ $record->seat_capacity }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Vehicle Type"><b>Vehicle Type</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">
                                @isset($vehicleTypeText)
                                    {{ ucwords($vehicleTypeText) }}
                                @endisset
                            </p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="is_modified"><b>Is Vehicle modified?</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">
                            @if($record->is_modified)
                                Yes
                            @else
                                No
                            @endif
                        </p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="is_bank_financed"><b>Is Bank Financed?</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">
                            @if($record->is_bank_financed)
                                Yes
                            @else
                                No
                            @endif
                        </p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="is_gcc_standard"><b>Is GCC Standard?</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">
                            @if($record->is_gcc_standard)
                                Yes
                            @else
                                No
                            @endif
                        </p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="current_insurance_status"><b>Current Insurance</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">
                            @if($record->current_insurance_status == 'ACTIVE_TPL')
                                Active (Third Party Only)
                            @elseif($record->current_insurance_status == 'ACTIVE_COMP')
                                Active (Comprehensive)
                            @elseif($record->current_insurance_status == 'EXPIRED')
                                Expired
                            @else
                                N/A
                            @endif
                        </p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="year_of_first_registration"><b>Year Of First Registration</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">
                            {{ $record->year_of_first_registration }}
                        </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
