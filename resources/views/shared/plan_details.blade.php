<meta name="csrf-token" content="{{ csrf_token() }}" />

<?php

    use App\Models\ApplicationStorage;
    use App\Enums\ApplicationStorage as EnumsApplicationStorage;
    use App\Enums\quoteTypeCode;
    use App\Enums\CarPlanType;
    use App\Enums\CarPlanAddons;
    use App\Enums\InsuranceProviders;

    if(!isset($modelName)) {

        $carQuoteEditSwitch = ApplicationStorage::select('value')
        ->where([['key_name', 'IMCRM_CAR_QUOTE_PLANS_EDIT_IS_DISABLED'], ['is_active', 1]])
        ->get()->first();

        $carQuoteEditDisable = $carQuoteEditSwitch->value == EnumsApplicationStorage::ACTIVE ? 'disabled' : '';

        foreach ($listQuotePlans as $listQuotePlan) { // Car quote plans
            if ($listQuotePlan->id == $planId) {
                $listQuotePlanName = $listQuotePlan->name;
                $providerCode = $listQuotePlan->providerCode;
                $providerName = $listQuotePlan->providerName;
                $repairType = $listQuotePlan->repairType;
                $actualPremium = $listQuotePlan->actualPremium;

                $actualPremium = isset($listQuotePlan->actualPremium) ? $listQuotePlan->actualPremium : 0;
                $discountPremium = isset($listQuotePlan->discountPremium) ? $listQuotePlan->discountPremium : 0;
                $carValueLowerLimit = isset($listQuotePlan->carValueLowerLimit) ? $listQuotePlan->carValueLowerLimit : 0;
                $carValueUpperLimit = isset($listQuotePlan->carValueUpperLimit) ? $listQuotePlan->carValueUpperLimit : 0;
                $excess = isset($listQuotePlan->excess) ? $listQuotePlan->excess : 0;
                $carValue = isset($listQuotePlan->carValue) ? $listQuotePlan->carValue : 0;
                $isDisabled = isset($listQuotePlan->isDisabled) ? $listQuotePlan->isDisabled : 0;

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
            }
        }

        $readonlyFieldCss = $repairType == CarPlanType::TPL ? "pointer-events: none;background-color: #f6f6f6;" : "";
        $carPlanTypeComp = CarPlanType::COMP;
?>
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $('#update_car_plan_button').on('click', function(e) {
            var actual_premium = $("#actual_premium").val();
            var discounted_premium = $("#discounted_premium").val();
            var car_value = $("#car_value").val();
            var excess = $("#excess").val();
            var repair_type = $("#repair_type").val();
            var car_value_lower_limit = $("#car_value_lower_limit").val();
            var carPlanTypeComp = JSON.parse('<?php echo json_encode($carPlanTypeComp) ?>');
            if (repair_type == carPlanTypeComp && parseInt(car_value) < parseInt(car_value_lower_limit)) {
                alert("Car Value should be greater than or equal to " + car_value_lower_limit);
                return false;
            }
            if (actual_premium == '' || discounted_premium == '' || car_value == '' || excess == '') {
                alert('Please fill all the fields');
                return false;
            }
            var length = $('.addon_id').length;
            var addons = [];
            for ($a = 0; $a < length; $a++) {
                addons.push({
                    'addonId': parseInt($('.addon_id').eq($a).val()),
                    'addonOptionId': parseInt($('.addon_option_id').eq($a).val()),
                    'price': parseFloat($('.addon_price').eq($a).val()),
                    'isSelected': $('.addon_is_selected').eq($a).val() == "true" ? true : false,
                });
            }
            $(".loader").show();
            e.preventDefault();
            $.ajax({
                url: "{{ url('/CarPlanUpdateManualProcess') }}",
                type: 'post',
                dataType: "json",
                contentType: "application/json; charset=utf-8",
                data: JSON.stringify({
                    car_quote_uuid: $('#car_quote_uuid').val(),
                    car_plan_id: $('#car_plan_id').val(),
                    actual_premium: $('#actual_premium').val(),
                    discounted_premium: $('#discounted_premium').val(),
                    car_value: $('#car_value').val(),
                    excess: $('#excess').val(),
                    is_disabled: $('#is_disabled').val(),
                    is_create: $('#is_create').val(),
                    addons,
                    _token: '{{ csrf_token() }}'
                }),
                success: function(result) {
                    $(".loader").hide();
                    $('#car_plan_manual_process_text').show().text(result).delay(5000).fadeOut(300);
                },
                error: function(jqXhr, textStatus, errorMessage){
                    $(".loader").hide();
                    $('#car_plan_manual_process_text').show().text(jqXhr.responseText).delay(5000).fadeOut(300);
                }
            });
        });
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
                                                <tr><td><h6 style="font-weight: bold;">Features & Benefits</h6></td></tr>
                                                @foreach ($listQuotePlanBenefitsFeatures as $key => $listQuotePlanBenefitsFeature)
                                                    <tr><td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsFeature->text) }}</td>
                                                        <td>{{ ucwords($listQuotePlanBenefitsFeature->value) }}</td></tr>
                                                @endforeach
                                            @endif
                                            @if(isset($listQuotePlanBenefitstravelInconvenienceCover) && !empty($listQuotePlanBenefitstravelInconvenienceCover))
                                                <tr><td><h6 style="font-weight: bold;">Travel Inconvenience Cover</h6></td></tr>
                                                @foreach ($listQuotePlanBenefitstravelInconvenienceCover as $key => $listQuotePlanBenefitstravelInconvenience)
                                                    <tr><td style="width: 300px;">{{ ucwords($listQuotePlanBenefitstravelInconvenience->text) }}</td>
                                                        <td>{{ ucwords($listQuotePlanBenefitstravelInconvenience->value) }}</td></tr>
                                                @endforeach
                                            @endif
                                            @if(isset($listQuotePlanBenefitsemergencyMedicalCover) && !empty($listQuotePlanBenefitsemergencyMedicalCover))
                                            <tr><td><h6 style="font-weight: bold;">Emergency Medical Cover</h6></td></tr>
                                                @foreach ($listQuotePlanBenefitsemergencyMedicalCover as $key => $listQuotePlanBenefitsemergencyMedical)
                                                    <tr><td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsemergencyMedical->text) }}</td>
                                                        <td>{{ ucwords($listQuotePlanBenefitsemergencyMedical->value) }}</td></tr>
                                                @endforeach
                                            @endif
                                            @if(isset($listQuotePlanBenefitsInclusions) && !empty($listQuotePlanBenefitsInclusions))
                                                <tr><td><h6 style="font-weight: bold;">Included in the plan</h6></td></tr>
                                                @foreach ($listQuotePlanBenefitsInclusions as $key => $listQuotePlanBenefitsInclusion)
                                                    <tr><td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsInclusion->text) }}</td>
                                                        <td>{{ ucwords($listQuotePlanBenefitsInclusion->value) }}</td></tr>
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
                            <a class="nav-link" id="benefits-exclusion-tab" data-toggle="tab" href="#benefits-exclusion" role="tab" aria-controls="benefits-exclusion" aria-selected="false">Exclusions</a>
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
                                <tr><td>Actual Premium:</td> <td>{{ $actualPremium }}</td></tr>
                                <tr><td>Discount Premium:</td> <td>{{ $discountPremium }}</td></tr>
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
                                        <td valign="top" style="width: 120px;">Provider Code:</td> <td>{{ $providerCode }}</td>
                                        <td valign="top">Provider Name:</td> <td>{{ $providerName }}</td>
                                    </tr>
                                    <tr><td valign="top">Repair Type:</td> <td>{{ $repairType }}</td><td> </td></tr>
                                    <tr>
                                        <td valign="top">Actual Premium:</td> <td><input type="number" id="actual_premium" name="actual_premium" value="{{ old('actual_premium', $actualPremium) }}" class="form-control" onKeyDown="if(this.value.length==8) return false;" {{ $carQuoteEditDisable }}></td>
                                        <td valign="top">Discounted Premium:</td> <td><input type="number" id="discounted_premium" name="discounted_premium" value="{{ old('discounted_premium', $discountPremium) }}" class="form-control" onKeyDown="if(this.value.length==8) return false;" {{ $carQuoteEditDisable }}></td>
                                    </tr>
                                    <tr>
                                        <td valign="top">Car value:</td> <td><input type="number" id="car_value" name="car_value" value="{{ old('car_value', $carValue) }}" class="form-control" onKeyDown="if(this.value.length==8) return false;" style="{{ $readonlyFieldCss }}" {{ $carQuoteEditDisable }}>
                                            <span style="font-size: 10px;">Min: AED {{ number_format($carValueLowerLimit) }} - Max: AED {{ number_format($carValueUpperLimit) }}</span>
                                        </td>
                                        <td valign="top">Excess:</td> <td><input type="number" id="excess" name="excess" value="{{ old('excess', $excess) }}" class="form-control" onKeyDown="if(this.value.length==8) return false;" style="{{ $readonlyFieldCss }}" {{ $carQuoteEditDisable }}></td>
                                    </tr>
                                    <tr><td valign="top">Disabled?</td>
                                        <td><select class="form-control" id='is_disabled' name="is_disabled" {{ $carQuoteEditDisable }}>
                                                <option value="false" {{ $isDisabled == false ? 'selected="selected"' : '' }}>False</option>
                                                <option value="true" {{ $isDisabled == true ? 'selected="selected"' : '' }}>True</option>
                                            </select>
                                            <div id="car_plan_manual_process_text" style="display: none;font-weight:bold;"></div>
                                        </td>
                                        <td> </td>
                                        <td align="right"><button type="submit" class="btn btn-warning btn-sm" id="update_car_plan_button" {{ $carQuoteEditDisable }}>Update</button></td></tr>
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
                        <div id="addons_text" style="font-weight:bold;display:none;"></div>
                            <table cellpadding="3" cellspacing="3">
                                <tr>
                                    <td>
                                        <table cellpadding="3" cellspacing="3">
                                            <ul>
                                                @foreach ($listQuotePlanAddons as $listQuotePlanAddon)
                                                    @foreach ($listQuotePlanAddon->carAddonOption as $carAddonOption)
                                                    <tr>
                                                        <td style="width: 430px;height: 30px;">{{ ucwords($listQuotePlanAddon->text) }}</td>
                                                        <td style="width: 800px;height: 30px;">{{ ucwords($carAddonOption->value) }}</td>

                                                        @if($carAddonOption->price == 0)
                                                            <?php
                                                                if(isset($carAddonOption->isSelected)) {
                                                                    $carAddonOptionIsSelected = $carAddonOption->isSelected;
                                                                } else {
                                                                    $carAddonOptionIsSelected = false;
                                                                }
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
                                                            <input type="hidden" id="addon_id" name="addon_id" value="{{ $listQuotePlanAddon->id }}" class="addon_id">
                                                            <input type="hidden" id="addon_option_id" name="addon_option_id" value="{{ $carAddonOption->id }}" class="addon_option_id">
                                                        </td>
                                                        <td style="width: 430px;height: 30px;" id="plan_addons">
                                                            <select id="addon_is_selected" name="addon_is_selected" style="height: 30px; width: 140px;" class="form-control addon_is_selected">
                                                                <option value=false {{ $carAddonOption->isSelected == false ? 'selected="selected"' : '' }}>Deselected</option>
                                                                <option value=true {{ $carAddonOption->isSelected == true ? 'selected="selected"' : '' }}>Selected</option>
                                                            </select>
                                                        </td>
                                                        @endif
                                                    </tr>
                                                    @endforeach
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
