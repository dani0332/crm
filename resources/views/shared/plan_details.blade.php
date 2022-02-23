<?php
    use App\Enums\quoteTypeCode;
?>
@if(isset($modelName) && $modelName == quoteTypeCode::Travel)
<div class="row">
        <div class="col-md-12 col-sm-12 admin-detail">
            <div class="x_panel" style="border: none">
                <div class="x_title" style="text-align: center;">
                    <div class="h5">{{ ucwords($listQuotePlanName) }}</div>
                    <div class="clearfix"></div>
                </div>

                <div class="x_content">

                    <ul class="nav nav-tabs" id="myTab" role="tablist" style="font-weight: bold;">
                        <li class="nav-item">
                            <a class="nav-link active" id="general-tab" data-toggle="tab" href="#general" role="tab" aria-controls="general" aria-selected="true">General Info</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="members-tab" data-toggle="tab" href="#members" role="tab" aria-controls="members" aria-selected="false">Members</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="benefits-inclusion-tab" data-toggle="tab" href="#benefits-inclusion" role="tab" aria-controls="benefits-inclusion" aria-selected="false">Inclusions</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="benefits-exclusion-tab" data-toggle="tab" href="#benefits-exclusion" role="tab" aria-controls="benefits-exclusion" aria-selected="false">Exclusions</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="covid-tab" data-toggle="tab" href="#covid" role="tab" aria-controls="covid" aria-selected="false">COVID-19 Cover</a>
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
                                <tr><td>Travel Type:</td> <td>{{ $travelType }}</td></tr>
                                <tr><td>Actual Premium:</td> <td>{{ $actualPremium }}</td></tr>
                                <tr><td>Discount Premium:</td> <td>{{ $discountPremium }}</td></tr>
                            </table>
                        </div>

                        <div class="tab-pane fade" id="members" role="tabpanel" aria-labelledby="members-tab">
                            <table cellpadding="3" cellspacing="3">
                                <tr>
                                    <td>
                                        <table cellpadding="3" cellspacing="3">
                                            @foreach ($listQuotePlansMembers as $key => $listQuotePlansMember)
                                                <tr>
                                                    <td style="width: 100px;font-weight: bold;">Member {{ $key+1 }}</td>
                                                </tr>
                                                <tr>
                                                    <td style="width: 50px;">Age</td>
                                                    <td style="width: 50px;">{{ ucwords($listQuotePlansMember->ageValue) }}</td>
                                                    <td style="width: 50px;">DOB</td>
                                                    <td style="width: 200px;">{{ ucwords($listQuotePlansMember->dob) }}</td>
                                                </tr>
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
                        <div class="tab-pane fade" id="covid" role="tabpanel" aria-labelledby="covid-tab">
                            <table cellpadding="3" cellspacing="3">
                                <tr>
                                    <td>
                                        <table cellpadding="3" cellspacing="3">
                                            @foreach ($listQuotePlanBenefitsCovid19 as $key => $listQuotePlanBenefitsCovid)
                                                <tr><td style="width: 500px;">{{ ucwords($listQuotePlanBenefitsCovid->text) }}</td>
                                                    <td>{{ ucwords($listQuotePlanBenefitsCovid->value) }}</td></tr>
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
                                        <a href="{{ $listQuotePlanBenefitsPolicyDetailLink }}" target="_blank">Click here</a>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="row">
        <div class="col-md-12 col-sm-12 admin-detail">
            <div class="x_panel" style="border: none">
                <div class="x_title" style="text-align: center;">
                    <div class="h5">{{ ucwords($listQuotePlanName) }}</div>
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
                            <form id="update_car_plan" method='post' action="{{ route('SaveCarPlan') }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                                {{csrf_field()}}
                                <table cellpadding="3" cellspacing="3">
                                    <tr><td style="width: 150px;">Provider Code:</td> <td>{{ $providerCode }}</td></tr>
                                    <tr><td>Provider Name:</td> <td>{{ $providerName }}</td></tr>
                                    <tr><td>Repair Type:</td> <td>{{ $repairType }}</td></tr>
                                    <tr><td>Actual Premium:</td> <td><input type="number" id="actual_premium" name="actual_premium" value="{{ old('actual_premium', $actualPremium) }}" class="form-control"></td><td>Discount Premium:</td> <td><input type="number" id="discount_premium" name="discount_premium" value="{{ old('discount_premium', $discountPremium) }}" class="form-control"></td></tr>
                                    <tr><td>Car value:</td> <td><input type="number" id="car_value" name="car_value" value="{{ old('car_value', $carValue) }}" class="form-control"></td><td>Excess:</td> <td><input type="number" id="excess" name="excess" value="{{ old('excess', $excess) }}" class="form-control"></td></tr>
                                    <tr><td> </td> <td> </td><td> </td> <td align="right"><button type="submit" class="btn btn-warning btn-sm">Update</button></td></tr>
                                </table>
                            </form>
                            <br />
                            <p>
                                <strong>Features</strong>
                                <table cellpadding="3" cellspacing="3">
                                    @foreach ($listQuotePlanBenefitsFeatures as $key => $listQuotePlanBenefitsFeature)
                                        <tr><td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsFeature->text) }}</td>
                                            <td>{{ ucwords($listQuotePlanBenefitsFeature->value) }}</td></tr>
                                    @endforeach
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
                                                <tr><td style="width: 230px;height: 30px;">Free</td>
                                                    <td><input type="checkbox" checked style="height: unset !important;" disabled></td></td></tr>
                                                @else
                                                <tr><td style="width: 230px;height: 30px;">AED {{ ucwords($listQuotePlanAddonPrice) }}</td>
                                                    <td><input type="checkbox" style="height: unset !important;" disabled></td></tr>
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
                                        <a href="{{ $listQuotePlanBenefitsPolicyDetailLink }}" target="_blank">Click here</a>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif


