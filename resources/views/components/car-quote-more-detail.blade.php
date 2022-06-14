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
                            {{ $caqQuoteCylinder }}
                        </p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Seat Capacity"><b>Seat Capacity</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">
                            {{ $caqQuoteSeatCapacity }}
                        </p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Trim"><b>Trim</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">
                            {{ $carTrim }}
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
                        <p class="label-align-center">
                            @isset($listQuote->isModified)
                                @if($listQuote->isModified)
                                    Yes
                                @else
                                    No
                                @endif
                            @endisset
                        </p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Bank Financed"><b>Bank Financed</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">
                            @isset($listQuote->isBankFinanced)
                                @if($listQuote->isBankFinanced)
                                    Yes
                                @else
                                    No
                                @endif
                            @endisset
                        </p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="GCC Standard"><b>GCC Standard</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">
                            @isset($listQuote->isGccStandard)
                                @if($listQuote->isGccStandard)
                                    Yes
                                @else
                                    No
                                @endif
                            @endisset
                        </p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Current Insurance"><b>Current Insurance</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">
                            @isset($listQuote->currentInsuranceStatus)
                                @if($listQuote->currentInsuranceStatus == 'ACTIVE_TPL')
                                    Active (Third Party Only)
                                @endif
                                @if($listQuote->currentInsuranceStatus == 'ACTIVE_COMP')
                                    Active (Comprehensive)
                                @endif
                                @if($listQuote->currentInsuranceStatus == 'EXPIRED')
                                    Expired
                                @endif
                            @endisset
                        </p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Year Of First Registration"><b>Year Of First Registration</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">
                            @isset($listQuote->isGccStandard)
                                {{ $listQuote->yearOfFirstRegistration }}
                            @endisset
                        </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
