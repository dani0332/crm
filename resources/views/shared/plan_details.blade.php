
    <div class="row">
        <div class="col-md-12 col-sm-12 admin-detail">
            <div class="x_panel" style="border: none">
                <div class="x_title" style="text-align: center;">
                    <div class="h5">{{ ucwords($listQuotePlanName) }}</div>
                    {{-- <ul class="nav navbar-right panel_toolbox">
                        <li><a href="{{ url()->previous() }}" class="btn btn-warning btn-sm">Go Back</a></li>
                    </ul> --}}
                    <div class="clearfix"></div>
                </div>

                <div class="x_content">

                    <ul class="nav nav-tabs" id="myTab" role="tablist" style="font-weight: bold;">
                        <li class="nav-item">
                            <a class="nav-link active" id="general-tab" data-toggle="tab" href="#general" role="tab" aria-controls="general" aria-selected="true">General Info</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="addons-tab" data-toggle="tab" href="#addons" role="tab" aria-controls="addons" aria-selected="false">Addons</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="benefits-inclusion-tab" data-toggle="tab" href="#benefits-inclusion" role="tab" aria-controls="benefits-inclusion" aria-selected="false">Inclusions</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="benefits-exclusion-tab" data-toggle="tab" href="#benefits-exclusion" role="tab" aria-controls="benefits-exclusion" aria-selected="false">Exclusions</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="rsa-tab" data-toggle="tab" href="#rsa" role="tab" aria-controls="rsa" aria-selected="false">Road Side Assistance</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="policy-detail-tab" data-toggle="tab" href="#policy-detail" role="tab" aria-controls="policy-detail" aria-selected="false">Policy Detail</a>
                        </li>
                    </ul>

                    <div class="tab-content" style="padding-top: 20px;">
                        <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                            <table cellpadding="3" cellspacing="3">
                                <tr><td style="width: 150px;">Provider Code:</td> <td>{{ $providerCode }}</td></tr>
                                <tr><td>Provider Name:</td> <td>{{ $providerName }}</td></tr>
                                <tr><td>Repair Type:</td> <td>{{ $repairType }}</td></tr>
                                <tr><td>Actual Premium:</td> <td>{{ $actualPremium }}</td></tr>
                                <tr><td>Discount Premium:</td> <td>{{ $discountPremium }}</td></tr>
                            </table>
                            <br /><br />
                            <p>
                                <strong>Features</strong>
                                <table cellpadding="3" cellspacing="3">
                                    <tr>
                                        <td>
                                            <table cellpadding="3" cellspacing="3">
                                                @foreach ($listQuotePlanBenefitsFeatures as $key => $listQuotePlanBenefitsFeature)
                                                    <tr><td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsFeature->text) }}</td>
                                                        <td>{{ ucwords($listQuotePlanBenefitsFeature->value) }}</td></tr>
                                                @endforeach
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </p>
                        </div>
                        <div class="tab-pane fade" id="addons" role="tabpanel" aria-labelledby="addons-tab">
                            <table cellpadding="3" cellspacing="3">
                                <tr>
                                    <td>
                                        <table cellpadding="3" cellspacing="3">
                                            @foreach ($listQuotePlanAddons as $key => $listQuotePlanAddon)
                                                <tr><td style="width: 430px;height: 30px;">{{ ucwords($listQuotePlanAddon->text) }}</td></tr>
                                            @endforeach
                                        </table>
                                    </td>
                                    <td>
                                        <table cellpadding="3" cellspacing="3">
                                            @foreach ($listQuotePlanAddonValues as $key => $listQuotePlanAddonValue)
                                                <tr><td style="width: 430px;height: 30px;">{{ ucwords($listQuotePlanAddonValue) }}</td></tr>
                                            @endforeach
                                        </table>
                                    </td>
                                    <td>
                                        <table cellpadding="3" cellspacing="3">
                                            @foreach ($listQuotePlanAddonPrices as $key => $listQuotePlanAddonPrice)
                                                @if($listQuotePlanAddonPrice == 0)
                                                <tr><td style="width: 430px;height: 30px;">Free</td></tr>
                                                @else
                                                <tr><td style="width: 430px;height: 30px;">AED {{ ucwords($listQuotePlanAddonPrice) }}</td></tr>
                                                @endif
                                            @endforeach
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="tab-pane fade" id="benefits-inclusion" role="tabpanel" aria-labelledby="benefits-inclusion-tab">
                            <table cellpadding="3" cellspacing="3">
                                <tr>
                                    <td>
                                        <table cellpadding="3" cellspacing="3">
                                            @foreach ($listQuotePlanBenefitsInclusions as $key => $listQuotePlanBenefitsInclusion)
                                                <tr><td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsInclusion->text) }}</td>
                                                    <td>{{ ucwords($listQuotePlanBenefitsInclusion->value) }}</td></tr>
                                            @endforeach
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="tab-pane fade" id="benefits-exclusion" role="tabpanel" aria-labelledby="benefits-exclusion-tab">
                            <table cellpadding="3" cellspacing="3">
                                <tr>
                                    <td>
                                        <table cellpadding="3" cellspacing="3">
                                            @foreach ($listQuotePlanBenefitsExclusions as $key => $listQuotePlanBenefitsExclusion)
                                                <tr><td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsExclusion->text) }}</td>
                                                    <td>{{ ucwords($listQuotePlanBenefitsExclusion->value) }}</td></tr>
                                            @endforeach
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="tab-pane fade" id="rsa" role="tabpanel" aria-labelledby="rsa-tab">
                            <table cellpadding="3" cellspacing="3">
                                <tr>
                                    <td>
                                        <table cellpadding="3" cellspacing="3">
                                            @foreach ($listQuotePlanBenefitsRsas as $key => $listQuotePlanBenefitsRsa)
                                                <tr><td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsRsa->text) }}</td>
                                                    <td>{{ ucwords($listQuotePlanBenefitsRsa->value) }}</td></tr>
                                            @endforeach
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="tab-pane fade" id="policy-detail" role="tabpanel" aria-labelledby="policy-detail-tab">
                            <table cellpadding="3" cellspacing="3">
                                <tr>
                                    <td>
                                        <table cellpadding="3" cellspacing="3">
                                            @foreach ($listQuotePlanBenefitsPolicyDetails as $key => $listQuotePlanBenefitsPolicyDetail)
                                                <tr><td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsPolicyDetail->text) }}</td>
                                                    <td>{{ ucwords($listQuotePlanBenefitsPolicyDetail->value) }}</td></tr>
                                            @endforeach
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            {{-- <a id="texta" href="{{ url('quotes/'.strtolower($model->modelType).'/'.$record->id.'/edit') }}" class='btn btn-warning btn-sm'>Edit</a> --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


