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
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Cylinders"><b>Cylinders</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">
                            @isset($listQuoteVehicleDetails->cylinder)
                                {{ ucwords($listQuoteVehicleDetails->cylinder) }}
                            @endisset
                        </p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Seat Capacity"><b>Seat Capacity</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">
                            @isset($listQuoteVehicleDetails->seatingCapacity)
                                {{ ucwords($listQuoteVehicleDetails->seatingCapacity) }}
                            @endisset
                        </p>
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
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Is Vehicle modified?"><b>Is Vehicle modified?</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">No</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
