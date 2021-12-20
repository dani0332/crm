<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>E-COM Details</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">

                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="PREMIUM"><b>PREMIUM</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">{{ $carQuotePremium }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="PAID AT"><b>PAID AT</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">{{ $carQuotePaidAt }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="PAYMENT STATUS"><b>PAYMENT STATUS</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">{{ $carQuotePaymentStatus }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="PROVIDER NAME"><b>PROVIDER NAME</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">{{ $carQuotePlanProvider }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="PAYMENT METHOD"><b>PAYMENT METHOD</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">{{ $carQuotePaymentMethod }}</p>
                        </div>
                    </div>
                    <div class="col">

                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="PLAN NAME"><b>PLAN NAME</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">{{ ucwords($carQuotePlanName) }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="ADDONS"><b>ADDONS</b></label>
                        <div class="col-md-6 col-sm-6" style="max-width: 75%;">
                            <p class="label-align-center">
                                <table>
                                    @php
                                        $sno = 1;
                                    @endphp
                                    @foreach($carQuotePlanAddons as $carQuotePlanAddon)
                                        <tr>
                                            <td width="20" height="25" valign="top">{{ $sno }}.</td>
                                            <td width="120" valign="top">{{ ucwords($carQuotePlanAddon->car_addon_text) }}</td>
                                            <td width="200" valign="top">{{ ucwords($carQuotePlanAddon->car_addon_option_value) }}</td>
                                            <td valign="top">
                                                <table>
                                                    <tr>
                                                        <td style="width: 53px;">Free</td>
                                                        <td><input type="checkbox" checked style="height: unset !important;" disabled></td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        @php
                                            $sno++;
                                        @endphp
                                    @endforeach
                                </table>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
