<meta name="csrf-token" content="{{ csrf_token() }}" />

<?php

use App\Enums\ApplicationStorageEnums;
use App\Enums\quoteTypeCode;
use App\Enums\CarPlanType;
use App\Enums\CarPlanAddons;
use App\Enums\InsuranceProviders;

if (!isset($modelName)) {

    $carQuoteEditDisable = $isPlanUpdateActive == ApplicationStorageEnums::INACTIVE ? 'disabled' : '';

    foreach ($listQuotePlans as $listQuotePlan) { // Car quote plans
        if ($listQuotePlan->id == $planId) {
            $listQuotePlanName = $listQuotePlan->name;
            $providerCode = $listQuotePlan->providerCode;
            $providerName = $listQuotePlan->providerName;
            $repairType = $listQuotePlan->repairType;
            $actualPremium = $listQuotePlan->actualPremium;

            $actualPremium = isset($listQuotePlan->actualPremium) ? $listQuotePlan->actualPremium : 0;
            $discountPremium = isset($listQuotePlan->discountPremium) ? $listQuotePlan->discountPremium : 0;
            $vat = isset($listQuotePlan->vat) ? $listQuotePlan->vat : 0;
            $carValueLowerLimit = isset($listQuotePlan->carValueLowerLimit) ? $listQuotePlan->carValueLowerLimit : 0;
            $carValueUpperLimit = isset($listQuotePlan->carValueUpperLimit) ? $listQuotePlan->carValueUpperLimit : 0;
            $excess = isset($listQuotePlan->excess) ? $listQuotePlan->excess : 0;
            $carValue = isset($listQuotePlan->carValue) ? $listQuotePlan->carValue : 0;
            $isDisabled = isset($listQuotePlan->isDisabled) ? $listQuotePlan->isDisabled : 0;
            $insurerQuoteNo = isset($listQuotePlan->insurerQuoteNo) ? $listQuotePlan->insurerQuoteNo : ''; // Insurer Quote No.

            $listQuotePlanAddonss = $listQuotePlan->addons;
            $listQuotePlanBenefitsInclusions = $listQuotePlan->benefits->inclusion;
            $listQuotePlanBenefitsExclusions = $listQuotePlan->benefits->exclusion;
            $listQuotePlanBenefitsFeatures = $listQuotePlan->benefits->feature;
            $listQuotePlanBenefitsRsas = $listQuotePlan->benefits->roadSideAssistance;
            $listQuotePlanBenefitsPolicyDetails = $listQuotePlan->policyWordings;
            foreach ($listQuotePlanAddonss as $listQuotePlanAddon) {
                $listQuotePlanAddons[] = $listQuotePlanAddon; // Get Addons Names
            }
            $listQuotePlanBenefitsPolicyDetailLink = '';
            foreach ($listQuotePlanBenefitsPolicyDetails as $listQuotePlanBenefitsPolicyDetail) {
                $listQuotePlanBenefitsPolicyDetailLink = $listQuotePlanBenefitsPolicyDetail->link;
            }

            $totalSelectedAddonsPriceWithVat = 0;
            foreach ($listQuotePlanAddons as $listQuotePlanAddon) {
                foreach ($listQuotePlanAddon->carAddonOption as $carAddonOption) {
                    if (isset($carAddonOption->isSelected)) {
                        if ($carAddonOption->isSelected == 1) {
                            $totalSelectedAddonsPriceWithVat += $carAddonOption->price + $carAddonOption->vat;
                        }
                    }
                }
            }

            $totalPremium = $discountPremium + $vat + $totalSelectedAddonsPriceWithVat;
        }
    }

    $readonlyFieldCss = $repairType == CarPlanType::TPL ? "pointer-events: none;background-color: #f6f6f6;" : "";
?>
    <script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $(document).ready(function() {

            var discountedPremium = $('#discounted_premium').val();
            var carPlanType = JSON.parse('<?php echo json_encode($repairType) ?>');
            var carPlanTypeTpl = JSON.parse('<?php echo json_encode(CarPlanType::TPL) ?>');

            $('.update-car-quote-plan-button').on('click', function(e) {
                var actual_premium = $("#actual_premium").val();
                var discounted_premium = $("#discounted_premium").val();
                var premium_vat = $("#premium_vat").val();
                var car_value = $("#car_value").val();
                var excess = $("#excess").val();
                var repair_type = $("#repair_type").val();
                var car_value_lower_limit = $("#car_value_lower_limit").val();
                var carPlanTypeComp = JSON.parse('<?php echo json_encode(CarPlanType::COMP) ?>');
                var carPlanTypeTpl = JSON.parse('<?php echo json_encode(CarPlanType::TPL) ?>');

                // For COMP, car_value should greater than or equal to lower_limit
                if (repair_type == carPlanTypeComp && parseInt(car_value) < parseInt(car_value_lower_limit)) {
                    validationDivText('.car-quote-plan-validation-div', 'Car Value should be greater than or equal to ' + car_value_lower_limit);
                    return false;
                }

                // discounted_premium always must filled
                if (discounted_premium == '') {
                    validationDivText('.car-quote-plan-validation-div', 'Discounted Premium must be filled');
                    return false;
                } else {
                    if(discounted_premium > actual_premium) {
                        validationDivText('.car-quote-plan-validation-div', 'Discounted Premium must be lower than Actual Premium');
                        return false;
                    }
                    validationDivText('.car-quote-plan-validation-div', '');
                    // Validations as per plan_type
                    if (repair_type == carPlanTypeTpl) { // TPL Plan
                        if (discounted_premium > 0) {
                            if (actual_premium > 0) { // actual_premium should not empty or 0
                                validationDivText('.car-quote-plan-validation-div', '');
                            } else {
                                validationDivText('.car-quote-plan-validation-div', 'Actual Premium must be filled');
                                return false;
                            }
                        }
                    } else { // COMP Plan
                        if (discounted_premium > 0) {
                            // actual_premium, excess, car_value should not empty or 0
                            if (actual_premium > 0 && excess > 0 && car_value > 0) { // actual_premium should not empty or 0
                                validationDivText('.car-quote-plan-validation-div', '');
                            } else {
                                validationDivText('.car-quote-plan-validation-div', 'Actual Premium, Excess, Car Value must be filled');
                                return false;
                            }
                        }
                    }
                }

                var length = $('.addon_id').length;
                var addons = [];
                for ($a = 0; $a < length; $a++) {
                    addons.push({
                        'addonId': parseInt($('.addon_id').eq($a).val()),
                        'addonOptionId': parseInt($('.addon_option_id').eq($a).val()),
                        'price': parseFloat($('.addon_price').eq($a).val()),
                        'vat': parseFloat($('.addon_vat').eq($a).val()),
                        'isSelected': $('.addon_is_selected').eq($a).val() == "true" ? true : false,
                    });
                }
                $(".loader").show();
                e.preventDefault();
                $.ajax({
                    url: "{{ url('/CarPlanUpdateManualProcess') }}",
                    type: 'post',
                    contentType: "application/json; charset=utf-8",
                    data: JSON.stringify({
                        car_quote_uuid: $('#car_quote_uuid').val(),
                        car_plan_id: $('#car_plan_id').val(),
                        actual_premium: $('#actual_premium').val(),
                        discounted_premium: $('#discounted_premium').val(),
                        premium_vat: $('#premium_vat').val(),
                        car_value: $('#car_value').val(),
                        excess: $('#excess').val(),
                        is_disabled: $('#is_disabled').val(),
                        is_create: $('#is_create').val(),
                        addons,
                        _token: '{{ csrf_token() }}'
                    }),
                    success: function(result) {
                        $(".loader").hide();
                        validationDivText('.car-quote-plan-validation-div', result);
                        // Conditionally lock fields
                        conditionallyLockFields(discounted_premium, carPlanType, carPlanTypeTpl);

                        var totalSelectedAddonsPriceWithVat = 0;
                        $.each(addons, function(i, jsondata) {
                            if (jsondata.isSelected == true) {
                                totalSelectedAddonsPriceWithVat += jsondata.price + jsondata.vat;
                            }
                        });
                        var totalPremium = Number(discounted_premium) + Number(premium_vat) + Number(totalSelectedAddonsPriceWithVat);
                        showTotals('.car-quote-plan-total-premium', totalPremium.toFixed(2));
                    },
                    error: function(jqXhr, textStatus, errorMessage) {
                        $(".loader").hide();
                        showTotals('.car-quote-plan-total-premium', '');
                        validationDivText('.car-quote-plan-validation-div', jqXhr.responseText);
                    }
                });
            });
        });

        // Show total on load
        var totalPremium = JSON.parse('<?php echo json_encode($totalPremium) ?>');
        showTotals('.car-quote-plan-total-premium', totalPremium.toFixed(2));

        // Conditionally lock fields
        conditionallyLockFields(discountedPremium, carPlanType, carPlanTypeTpl);

        function lockField(id) {
            $(id).css({
                "pointer-events": "none",
                "background-color": "#f6f6f6"
            });
        }

        function unlockField(id) {
            $(id).css({
                "pointer-events": "",
                "background-color": ""
            });
        }

        function validationDivText(className, text) {
            $(className).text(text).css("width","700px");
        }

        function showTotals(id, totalPremium) {
            var totalPremiumHtml = '<div><span style="padding-top: 7px;border-top: 2px solid #E6E9ED;"><b>Total Premium with VAT:</b> AED ' + totalPremium + '</span></div>';
            $(id).show().html(totalPremiumHtml).delay(5000);
        }

        function conditionallyLockFields(discountedPremium, carPlanType, carPlanTypeTpl) {
            if (carPlanType == carPlanTypeTpl) { // TPL
                if (discountedPremium > 0) {
                    lockField("#actual_premium");
                } else {
                    unlockField("#actual_premium");
                }
            } else { // COMP
                if (discountedPremium > 0) {
                    lockField("#actual_premium");
                    lockField("#car_value");
                    lockField("#excess");
                } else {
                    unlockField("#actual_premium");
                    unlockField("#car_value");
                    unlockField("#excess");
                }
            }
        }
    </script>
<?php
}
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
                            <tr>
                                <td style="width: 150px;">Provider Code:</td>
                                <td>{{ $providerCode }}</td>
                            </tr>
                            <tr>
                                <td>Provider Name:</td>
                                <td>{{ $providerName }}</td>
                            </tr>
                            <tr>
                                <td>Travel Type:</td>
                                <td>{{ $travelType }}</td>
                            </tr>
                            <tr>
                                <td>Actual Premium:</td>
                                <td>{{ $actualPremium }}</td>
                            </tr>
                            <tr>
                                <td>Discount Premium:</td>
                                <td>{{ $discountPremium }}</td>
                            </tr>
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
                                            <td style="width: 50px;">Premium</td>
                                            <td style="width: 50px;">{{ $listQuotePlansMember->premium }}</td>
                                            <td style="width: 50px;">DOB</td>
                                            <td style="width: 200px;">{{ \Carbon\Carbon::createFromTimestamp(strtotime($listQuotePlansMember->dob))->format('d-m-Y')}}</td>
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
                                        @if(isset($listQuotePlanBenefitsFeatures) && !empty($listQuotePlanBenefitsFeatures))
                                        <tr>
                                            <td>
                                                <h6 style="font-weight: bold;">Features & Benefits</h6>
                                            </td>
                                        </tr>
                                        @foreach ($listQuotePlanBenefitsFeatures as $key => $listQuotePlanBenefitsFeature)
                                        <tr>
                                            <td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsFeature->text) }}</td>
                                            <td>{{ ucwords($listQuotePlanBenefitsFeature->value) }}</td>
                                        </tr>
                                        @endforeach
                                        @endif
                                        @if(isset($listQuotePlanBenefitstravelInconvenienceCover) && !empty($listQuotePlanBenefitstravelInconvenienceCover))
                                        <tr>
                                            <td>
                                                <h6 style="font-weight: bold;">Travel Inconvenience Cover</h6>
                                            </td>
                                        </tr>
                                        @foreach ($listQuotePlanBenefitstravelInconvenienceCover as $key => $listQuotePlanBenefitstravelInconvenience)
                                        <tr>
                                            <td style="width: 300px;">{{ ucwords($listQuotePlanBenefitstravelInconvenience->text) }}</td>
                                            <td>{{ ucwords($listQuotePlanBenefitstravelInconvenience->value) }}</td>
                                        </tr>
                                        @endforeach
                                        @endif
                                        @if(isset($listQuotePlanBenefitsemergencyMedicalCover) && !empty($listQuotePlanBenefitsemergencyMedicalCover))
                                        <tr>
                                            <td>
                                                <h6 style="font-weight: bold;">Emergency Medical Cover</h6>
                                            </td>
                                        </tr>
                                        @foreach ($listQuotePlanBenefitsemergencyMedicalCover as $key => $listQuotePlanBenefitsemergencyMedical)
                                        <tr>
                                            <td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsemergencyMedical->text) }}</td>
                                            <td>{{ ucwords($listQuotePlanBenefitsemergencyMedical->value) }}</td>
                                        </tr>
                                        @endforeach
                                        @endif
                                        @if(isset($listQuotePlanBenefitsInclusions) && !empty($listQuotePlanBenefitsInclusions))
                                        <tr>
                                            <td>
                                                <h6 style="font-weight: bold;">Included in the plan</h6>
                                            </td>
                                        </tr>
                                        @foreach ($listQuotePlanBenefitsInclusions as $key => $listQuotePlanBenefitsInclusion)
                                        <tr>
                                            <td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsInclusion->text) }}</td>
                                            <td>{{ ucwords($listQuotePlanBenefitsInclusion->value) }}</td>
                                        </tr>
                                        @endforeach
                                        @endif
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
                                        <tr>
                                            <td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsExclusion->text) }}</td>
                                            <td>{{ ucwords($listQuotePlanBenefitsExclusion->value) }}</td>
                                        </tr>
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
                                        <tr>
                                            <td style="width: 500px;">{{ ucwords($listQuotePlanBenefitsCovid->text) }}</td>
                                            <td>{{ ucwords($listQuotePlanBenefitsCovid->value) }}</td>
                                        </tr>
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
@elseif(isset($modelName) && $modelName == quoteTypeCode::Health)
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
                        <a class="nav-link" id="benefits-inclusion-tab" data-toggle="tab" href="#benefits-inclusion" role="tab" aria-controls="benefits-inclusion" aria-selected="false">Inclusions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="benefits-coInsurance-tab" data-toggle="tab" href="#benefits-coInsurance" role="tab" aria-controls="benefits-coInsurance" aria-selected="false">Co-pay/Co-insurance</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="benefits-regionCover-tab" data-toggle="tab" href="#benefits-regionCover" role="tab" aria-controls="benefits-regionCover" aria-selected="false">Region coverage & Network list</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="benefits-maternityCover-tab" data-toggle="tab" href="#benefits-maternityCover" role="tab" aria-controls="benefits-maternityCover" aria-selected="false">Maternity cover</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="benefits-exclusion-tab" data-toggle="tab" href="#benefits-exclusion" role="tab" aria-controls="benefits-exclusion" aria-selected="false">Exclusions</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" id="policy-detail-tab" data-toggle="tab" href="#policy-detail" role="tab" aria-controls="policy-detail" aria-selected="false">Policy Detail</a>
                    </li>
                </ul>

                <div class="tab-content" style="padding-top: 20px;">
                    <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                        <table cellpadding="3" cellspacing="3">
                            <tr>
                                <td style="width: 150px;">Provider Code:</td>
                                <td>{{ $providerCode }}</td>
                            </tr>
                            <tr>
                                <td>Provider Name:</td>
                                <td>{{ $providerName }}</td>
                            </tr>
                            <tr>
                                <td>Actual Premium:</td>
                                <td>{{ $actualPremium }}</td>
                            </tr>
                            <tr>
                                <td>Discount Premium:</td>
                                <td>{{ $discountPremium }}</td>
                            </tr>
                        </table>
                    </div>



                    <div class="tab-pane fade" id="benefits-inclusion" role="tabpanel" aria-labelledby="benefits-inclusion-tab">
                        <table cellpadding="3" cellspacing="3">
                            <tr>
                                <td>

                                    <table cellpadding="3" cellspacing="3">
                                        <tr>
                                            <td>
                                                <h6 style="font-weight: bold;">Features & Benefits</h6>
                                            </td>
                                        </tr>
                                        @foreach ($listQuotePlanBenefitsFeatures as $key => $listQuotePlanBenefitsFeature)
                                        <tr>
                                            <td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsFeature->text) }}</td>
                                            <td>{{ ucwords($listQuotePlanBenefitsFeature->value) }}</td>
                                        </tr>
                                        @endforeach
                                        @foreach ($listQuotePlanBenefitsInclusions as $key => $listQuotePlanBenefitsInclusion)
                                        <tr>
                                            <td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsInclusion->text) }}</td>
                                            <td>{{ ucwords($listQuotePlanBenefitsInclusion->value) }}</td>
                                        </tr>
                                        @endforeach
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="tab-pane fade" id="benefits-coInsurance" role="tabpanel" aria-labelledby="benefits-coInsurance-tab">
                        <table cellpadding="3" cellspacing="3">
                            <tr>
                                <td>
                                    <table cellpadding="3" cellspacing="3">
                                        @foreach ($listQuotePlanBenefitsCoInsurance as $key => $listQuotePlanCoInsurance)
                                        <tr>
                                            <td style="width: 300px;">{{ ucwords($listQuotePlanCoInsurance->text) }}</td>
                                            <td>{{ ucwords($listQuotePlanCoInsurance->value) }}</td>
                                        </tr>
                                        @endforeach
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="tab-pane fade" id="benefits-regionCover" role="tabpanel" aria-labelledby="benefits-regionCover-tab">
                        <table cellpadding="3" cellspacing="3">
                            <tr>
                                <td>
                                    <table cellpadding="3" cellspacing="3">
                                        @foreach ($listQuotePlanBenefitsRegionCover as $key => $listQuotePlanRegionCover)
                                        <tr>
                                            <td style="width: 300px;">{{ ucwords($listQuotePlanRegionCover->text) }}</td>
                                            <td>{{ ucwords($listQuotePlanRegionCover->value) }}</td>
                                        </tr>
                                        @endforeach
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="tab-pane fade" id="benefits-maternityCover" role="tabpanel" aria-labelledby="benefits-maternityCover-tab">
                        <table cellpadding="3" cellspacing="3">
                            <tr>
                                <td>
                                    <table cellpadding="3" cellspacing="3">
                                        @foreach ($listQuotePlanBenefitsMaternityCover as $key => $listQuotePlanMaternityCover)
                                        <tr>
                                            <td style="width: 300px;">{{ ucwords($listQuotePlanMaternityCover->text) }}</td>
                                            <td>{{ ucwords($listQuotePlanMaternityCover->value) }}</td>
                                        </tr>
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
                                        <tr>
                                            <td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsExclusion->text) }}</td>
                                            <td>{{ ucwords($listQuotePlanBenefitsExclusion->value) }}</td>
                                        </tr>
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
                                        <tr>
                                            <td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsExclusion->text) }}</td>
                                            <td>{{ ucwords($listQuotePlanBenefitsExclusion->value) }}</td>
                                        </tr>
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
                        <form id="update_car_plan" method='post' enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                            {{csrf_field()}}
                            <input type="hidden" id="car_plan_id" name="car_plan_id" value="{{ $planId }}">
                            <input type="hidden" id="car_quote_uuid" name="car_quote_uuid" value="{{ $quoteId }}">
                            <input type="hidden" id="car_value_lower_limit" name="car_value_lower_limit" value="{{ $carValueLowerLimit }}">
                            <input type="hidden" id="car_value_upper_limit" name="car_value_upper_limit" value="{{ $carValueUpperLimit }}">
                            <input type="hidden" id="repair_type" name="repair_type" value="{{ $repairType }}">
                            <input type="hidden" id="is_create" name="is_create" value="0">
                            <table cellpadding="8" cellspacing="8">
                                <tr>
                                    <td valign="top" style="width: 120px;">Provider Code:</td>
                                    <td>{{ $providerCode }}</td>
                                    <td valign="top">Provider Name:</td>
                                    <td>{{ $providerName }}</td>
                                </tr>
                                <tr>
                                    <td valign="top">Repair Type:</td>
                                    <td>{{ $repairType }}</td>
                                    <td>Insurer Quote No.:</td>
                                    <td>{{ $insurerQuoteNo }}</td>
                                </tr>
                                <tr>
                                    <td valign="top">Actual Premium:</td>
                                    <td><input type="number" id="actual_premium" name="actual_premium" value="{{ old('actual_premium', $actualPremium) }}" class="form-control" onKeyDown="if(this.value.length==8) return false;" onkeypress="return isNumberKey(event,this)"></td>
                                    <td valign="top">Discounted Premium:</td>
                                    <td>
                                        <input type="number" id="discounted_premium" name="discounted_premium" value="{{ old('discounted_premium', $discountPremium) }}" class="form-control" onKeyDown="if(this.value.length==8) return false;" onkeypress="return isNumberKey(event,this)">
                                        <input type="hidden" id="premium_vat" name="premium_vat" value="{{ $vat }}">
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="top">Car value:</td>
                                    <td><input type="number" id="car_value" name="car_value" value="{{ old('car_value', $carValue) }}" class="form-control" onKeyDown="if(this.value.length==8) return false;" style="{{ $readonlyFieldCss }}" onkeypress="return isNumberKey(event,this)">
                                        <span style="font-size: 10px;">Min: AED {{ number_format($carValueLowerLimit) }} - Max: AED {{ number_format($carValueUpperLimit) }}</span>
                                    </td>
                                    <td valign="top">Excess:</td>
                                    <td><input type="number" id="excess" name="excess" value="{{ old('excess', $excess) }}" class="form-control" onKeyDown="if(this.value.length==8) return false;" style="{{ $readonlyFieldCss }}" onkeypress="return isNumberKey(event,this)"></td>
                                </tr>
                                <tr>
                                    <td valign="top">Hide Plan?</td>
                                    <td><select class="form-control" id='is_disabled' name="is_disabled">
                                            <option value="false" {{ $isDisabled == "false" ? 'selected="selected"' : '' }}>No</option>
                                            <option value="true" {{ $isDisabled == "true" ? 'selected="selected"' : '' }}>Yes</option>
                                        </select>
                                    </td>
                                    <td> </td>
                                    <td align="right">
                                        <button type="submit" class="btn btn-warning btn-sm update-car-quote-plan-button" {{ $carQuoteEditDisable }}>Update</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="4">
                                        <div class="car-quote-plan-validation-div" style="color:red;font-weight:bold;text-align:right;"></div>
                                    </td>
                                </tr>
                            </table>
                        </form>
                        <br />
                        <p>
                            <strong>Features</strong>
                        <table cellpadding="3" cellspacing="3">
                            @foreach ($listQuotePlanBenefitsFeatures as $key => $listQuotePlanBenefitsFeature)
                            <tr>
                                <td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsFeature->text) }}</td>
                                <td>{{ ucwords($listQuotePlanBenefitsFeature->value) }}</td>
                            </tr>
                            @endforeach
                            <tr>
                                <td colspan="4"></td>
                            </tr>
                            <tr>
                                <td colspan="4"></td>
                            </tr>
                            <tr>
                                <td colspan="4"><span class="car-quote-plan-total-premium"></span></td>
                            </tr>
                        </table>
                        </p>
                    </div>
                    <div class="tab-pane fade" id="addons" role="tabpanel" aria-labelledby="addons-tab">
                        <table cellpadding="3" cellspacing="3">
                            <tr>
                                <td>
                                    <table cellpadding="3" cellspacing="3">
                                        @foreach ($listQuotePlanAddons as $listQuotePlanAddon)
                                        @foreach ($listQuotePlanAddon->carAddonOption as $carAddonOption)
                                        <tr>
                                            <td style="width: 430px;height: 30px;">{{ ucwords($listQuotePlanAddon->text) }}</td>
                                            <td style="width: 800px;height: 30px;">{{ ucwords($carAddonOption->value) }}</td>

                                            @if($carAddonOption->price == 0)
                                            <?php
                                            $carAddonOptionIsSelected = isset($carAddonOption->isSelected) ? $carAddonOption->isSelected : false;
                                            ?>
                                            <td style="width: 430px;height: 30px;">Free</td>
                                            <td style="width: 430px;height: 30px;">
                                                <select class="form-control" style="height: 30px; width: 140px;pointer-events: none; background-color: #f6f6f6">
                                                    <option value="false" {{ $carAddonOptionIsSelected == false ? 'selected="selected"' : '' }}>Deselected</option>
                                                    <option value="true" {{ $carAddonOptionIsSelected == true ? 'selected="selected"' : '' }}>Selected</option>
                                                </select>
                                            </td>
                                            @else
                                            <td style="width: 430px;height: 30px;">
                                                @if($listQuotePlanAddon->code == CarPlanAddons::CAR_HIRE && ($providerCode == InsuranceProviders::AXA || $providerCode == InsuranceProviders::RSA))
                                                <input type="number" id="addon_price" name="addon_price" value="{{ $carAddonOption->price }}" style="width: 100px;" onKeyDown="if(this.value.length==5) return false;" class="form-control addon_price">
                                                @else
                                                AED {{ $carAddonOption->price }}
                                                <input type="hidden" id="addon_price" name="addon_price" value="{{ $carAddonOption->price }}" class="addon_price">
                                                @endif
                                                <input type="hidden" id="addon_vat" name="addon_vat" value="{{ $carAddonOption->vat }}" class="addon_vat">
                                                <input type="hidden" id="addon_id" name="addon_id" value="{{ $listQuotePlanAddon->id }}" class="addon_id">
                                                <input type="hidden" id="addon_option_id" name="addon_option_id" value="{{ $carAddonOption->id }}" class="addon_option_id">
                                            </td>
                                            <td style="width: 430px;height: 30px;" id="plan_addons">
                                                <select id="addon_is_selected" name="addon_is_selected" style="height: 30px; width: 140px;" class="form-control addon_is_selected">
                                                    <option value=false {{ isset($carAddonOption->isSelected) && $carAddonOption->isSelected == false ? 'selected="selected"' : '' }}>Deselected</option>
                                                    <option value=true {{ isset($carAddonOption->isSelected) && $carAddonOption->isSelected == true ? 'selected="selected"' : '' }}>Selected</option>
                                                </select>
                                            </td>
                                            @endif
                                        </tr>
                                        @endforeach
                                        @endforeach
                                        <tr>
                                            <td colspan="5"> </td>
                                            <td align="center"> </td>
                                        </tr>
                                        <tr>
                                            <td colspan="5"> </td>
                                            <td align="center"> </td>
                                        </tr>
                                        <tr>
                                            <td colspan="5" align="right"><button type="submit" class="btn btn-warning btn-sm update-car-quote-plan-button" {{ $carQuoteEditDisable }}>Update</button></td>
                                        </tr>
                                        <tr>
                                            <td colspan="5">
                                                <div class="car-quote-plan-validation-div" style="color:red;font-weight:bold;text-align:right;"></div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="5"><span class="car-quote-plan-total-premium"></span></td>
                                        </tr>
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
                                        <tr>
                                            <td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsInclusion->text) }}</td>
                                            <td>{{ ucwords($listQuotePlanBenefitsInclusion->value) }}</td>
                                        </tr>
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
                                        <tr>
                                            <td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsExclusion->text) }}</td>
                                            <td>{{ ucwords($listQuotePlanBenefitsExclusion->value) }}</td>
                                        </tr>
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
                                        <tr>
                                            <td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsRsa->text) }}</td>
                                            <td>{{ ucwords($listQuotePlanBenefitsRsa->value) }}</td>
                                        </tr>
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