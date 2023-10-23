@php
use App\Enums\ApplicationStorageEnums;
use App\Enums\quoteTypeCode;
use App\Enums\CarPlanType;
use App\Enums\CarPlanAddons;
use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Enums\GenericRequestEnum;
if (!isset($modelName)) {
	$carQuoteEditDisable = $isPlanUpdateActive == ApplicationStorageEnums::INACTIVE ? 'disabled' : '';
	foreach ($listQuotePlans as $listQuotePlan) { // Car quote plans
		if(!isset($listQuotePlan->id)) {
			continue;
		}
	if ($listQuotePlan->id == $planId) {
		$listQuotePlanName = $listQuotePlan->name;
		$providerCode = $listQuotePlan->providerCode;
		$providerName = $listQuotePlan->providerName;
		$repairType = isset($listQuotePlan->repairType) ? $listQuotePlan->repairType : '';
		$actualPremium = isset($listQuotePlan->actualPremium) ? $listQuotePlan->actualPremium : 0;
		$discountPremium = isset($listQuotePlan->discountPremium) ? $listQuotePlan->discountPremium : 0;
		$vat = isset($listQuotePlan->vat) ? $listQuotePlan->vat : 0;
		$carValueLowerLimit = isset($listQuotePlan->carValueLowerLimit) ? $listQuotePlan->carValueLowerLimit : 0;
		$carValueUpperLimit = isset($listQuotePlan->carValueUpperLimit) ? $listQuotePlan->carValueUpperLimit : 0;
		$excess = isset($listQuotePlan->excess) ? $listQuotePlan->excess : 0;
		$carValue = isset($listQuotePlan->carValue) ? $listQuotePlan->carValue : 0;
		$isDisabled = isset($listQuotePlan->isDisabled) ? $listQuotePlan->isDisabled : 0;
		$insurerQuoteNo = isset($listQuotePlan->insurerQuoteNo) ? $listQuotePlan->insurerQuoteNo : ''; // Insurer Quote No.
		$isManualUpdate = isset($listQuotePlan->isManualUpdate) ? $listQuotePlan->isManualUpdate : 0;
		$shouldReviseQuote  = isset($listQuotePlan->shouldReviseQuote ) ? $listQuotePlan->shouldReviseQuote  : 0;
		$ancillaryExcess  = isset($listQuotePlan->ancillaryExcess ) ? $listQuotePlan->ancillaryExcess  : 0;
		$listQuotePlanAddonss = $listQuotePlan->addons;
		$listQuotePlanBenefitsInclusions = $listQuotePlan->benefits->inclusion;
		$listQuotePlanBenefitsExclusions = $listQuotePlan->benefits->exclusion;
		$listQuotePlanBenefitsFeatures = $listQuotePlan->benefits->feature;
		$listQuotePlanBenefitsRsas = $listQuotePlan->benefits->roadSideAssistance;
		$listQuotePlanBenefitsPolicyDetails = $listQuotePlan->policyWordings;
		$listQuotePlanAddons = [];
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
		$insurerAvailableTrims = isset($listQuotePlan->insurerAvailableTrims) ? $listQuotePlan->insurerAvailableTrims : [];
		$insurerSelectedTrim = isset($listQuotePlan->insurerTrimId) ? $listQuotePlan->insurerTrimId : null;

		$createdAt = '';
		if(isset($listQuotePlan->createdAt)){
			$createdAt = date('d/m/Y H:i:s',strtotime($listQuotePlan->createdAt));
		}

		//if($listQuotePlan->isManualPlan)
		$updatedAt = '';
		if(isset($listQuotePlan->updatedAt)){
			$updatedAt = date('d/m/Y H:i:s',strtotime($listQuotePlan->updatedAt));
		}
	}
}
$readonlyFieldCss = isset($repairType) && $repairType == CarPlanType::TPL ? "pointer-events: none;background-color: #f6f6f6;" : "";
@endphp
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<script src="{{ asset('js/bootstrap-toggle.min.js') }}"></script>
<link href="{{ asset('css/bootstrap-toggle.css') }}" rel="stylesheet">
<script>
	$.ajaxSetup({
		headers: {
			'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
		}
	});

	$(document).ready(function() {
		var discountedPremium = $('#discounted_premium').val();
		var carPlanTypeTpl = JSON.parse('<?php echo json_encode(CarPlanType::TPL) ?>');
		var shouldReviseQuote = JSON.parse('<?php echo json_encode(isset($shouldReviseQuote) ? $shouldReviseQuote : false) ?>');

		$('.update-car-quote-plan-button').on('click', function(e) {
			var actual_premium = $("#actual_premium").val();
			var discounted_premium = $("#discounted_premium").val();
			var premium_vat = $("#premium_vat").val();
			var car_value = $("#car_value").val();
			var excess = $("#excess").val();
			var repair_type = $("#repair_type").val();
			var car_value_lower_limit = $("#car_value_lower_limit").val();
			var car_value_upper_limit = $("#car_value_upper_limit").val();
			var carPlanTypeComp = JSON.parse(
				'<?php echo json_encode(CarPlanType::COMP) ?>');
			var carPlanTypeTpl = JSON.parse(
				'<?php echo json_encode(CarPlanType::TPL) ?>');
			var is_manual_update = $('#is_manual_update').is(':checked');
            let insurer_quote_no = $('#insurer_quote_no').val();

            if(insurer_quote_no.length > 50) {
                validationDivText('red', '.car-quote-plan-validation-div',
                    'Maximum allowed length for insurer quote no is 50.');
                return false;
            }

			if (repair_type == carPlanTypeComp) { // if non-tpl
				// Car Value should be within the given min/max range
				if (is_manual_update == false &&
					(parseInt(car_value) < parseInt(car_value_lower_limit) || parseInt(car_value) > parseInt(car_value_upper_limit))) {
					validationDivText('red', '.car-quote-plan-validation-div',
						'Car Value should be within the displayed acceptable range.');
					return false;
				}
			}

			// discounted_premium always must filled
			if (discounted_premium == '') {
				validationDivText('red', '.car-quote-plan-validation-div',
					'Please enter the Discounted Premium');
				return false;
			} else {
				if (parseFloat(discounted_premium) > parseFloat(actual_premium)) {
					validationDivText('red', '.car-quote-plan-validation-div',
						'Discounted Premium must be lower than Actual Premium');
					return false;
				}
				validationDivText('', '.car-quote-plan-validation-div', '');
				// Validations as per plan_type
				if (repair_type == carPlanTypeTpl) { // TPL Plan
					if (parseFloat(discounted_premium) > 0) {
						if (parseFloat(actual_premium) > 0) { // actual_premium should not empty or 0
							validationDivText('', '.car-quote-plan-validation-div', '');
						} else {
							validationDivText('red', '.car-quote-plan-validation-div',
								'Please enter the Actual Premium');
							return false;
						}
					}
				} else { // COMP Plan
					if (parseFloat(discounted_premium) > 0) {
						// actual_premium, excess, car_value should not empty or 0
						if (parseFloat(actual_premium) > 0 && parseFloat(excess) > 0 && parseFloat(
								car_value) > 0) { // actual_premium should not empty or 0
							validationDivText('', '.car-quote-plan-validation-div', '');
						} else {
							validationDivText('red', '.car-quote-plan-validation-div',
								'Please enter the Actual Premium, Excess and Car Value');
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
					'isSelected': $('.addon_is_selected').eq($a).is(':checked') ? true : false,
				});
			}
			$(".loader").show();
			e.preventDefault();
			$.ajax({
				url: "{{ url('/car-plan-manual-update-process') }}",
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
					is_disabled: $('#is_disabled').is(':checked'),
					is_create: $('#is_create').val(),
					addons,
					_token: '{{ csrf_token() }}',
					insurerTrim: $("#insurerTrim").val(),
					insurer_quote_no: $("#insurer_quote_no").val(),
					is_manual_update: $('#is_manual_update').is(':checked'),
					ancillary_excess: $("#ancillary_excess").val(),
					current_url: $('#current_url').val(),
				}),
				success: function(result) {
					$(".loader").hide();
					validationDivText('green', '.car-quote-plan-validation-div', result);
					conditionallyLockFields(shouldReviseQuote);

					var totalPriceSelectedAddonsWithVat = 0;
					$.each(addons, function(i, jsondata) {
						if (jsondata.isSelected == true) {
							totalPriceSelectedAddonsWithVat += jsondata.price +
								jsondata.vat;
						}
					});

					const premiumWithVat = getPremiumWithVat(5, actual_premium);
					totalPremium('.car-quote-plan-total-premium', discounted_premium,
						premiumWithVat, totalPriceSelectedAddonsWithVat);
					location.reload();
				},
				error: function(jqXhr, textStatus, errorMessage) {
					$(".loader").hide();
					validationDivText('red', '.car-quote-plan-validation-div', jqXhr
						.responseText);
				}
			});
		});

		// Show total on load
		totalPremium('.car-quote-plan-total-premium',
				'{{ isset($discountPremium) ? $discountPremium : 0 }}', '{{ isset($vat) ? $vat : 0 }}',
				'{{ isset($totalSelectedAddonsPriceWithVat) ? $totalSelectedAddonsPriceWithVat : 0 }}');

		// Conditionally lock fields
		conditionallyLockFields(shouldReviseQuote);

		// For manual plan if actualPremium changed, copy value to discountPremium
		$('#actual_premium').change(function() {
			if ($('#is_manual_update').is(':checked') == true) {
				$('#discounted_premium').val($('#actual_premium').val());
			}
		});

		// onchange if manual plan, do unlock fields
		$(document).on("change", "#is_manual_update", function() {
			var ischecked = $(this).is(':checked');
			manualPlanToggle(ischecked, shouldReviseQuote);
		});

		// onload if manual plan, hide range text and unlock addons
		var is_manual_update = $('#is_manual_update').is(':checked');
		var addon_price = document.getElementsByName("addon_price");
		var addon_is_selected = document.getElementsByName("addon_is_selected");
		if (is_manual_update) {
			hide_element("#car-value-range-validation-text");
			unlock_addons([addon_price, addon_is_selected]);
		} else {
			show_element("#car-value-range-validation-text");
			lock_addons([addon_price, addon_is_selected]);
		}

		function manualPlanToggle(ischecked, shouldReviseQuote) {
			var addon_price = document.getElementsByName("addon_price");
			var addon_is_selected = document.getElementsByName("addon_is_selected");
			if (ischecked) {
				unlockField(['#insurer_quote_no', '#actual_premium', '#discounted_premium', , '#car_value',
					'#excess', '#insurerTrim'
				]);
				hide_element("#car-value-range-validation-text");
				unlock_addons([addon_price, addon_is_selected]);
			} else {
				if (shouldReviseQuote == true) {
					lockField(['#insurer_quote_no', '#actual_premium', '#excess']);
				}

				// Revert to original values
				$('#insurer_quote_no').val($('#insurer_quote_no').data('value'));
				$('#actual_premium').val($('#actual_premium').data('value'));
				$('#excess').val($('#excess').data('value'));
				$('#discounted_premium').val($('#discounted_premium').data('value'));

				show_element("#car-value-range-validation-text");
				lock_addons([addon_price, addon_is_selected]);
			}
		}

		function lock_addons(elementId) {
			for (a = 0; a <= elementId.length; a++) {
				$(elementId[a]).prop("disabled", true);
			}
		}

		function unlock_addons(elementId) {
			for (a = 0; a <= elementId.length; a++) {
				$(elementId[a]).prop("disabled", false);
			}
		}

		function hide_element(div_id) {
			$(div_id).hide();
		}

		function show_element(div_id) {
			$(div_id).show();
		}

		function lockField(elementId) {
			for (a = 0; a <= elementId.length; a++) {
				$(elementId[a]).css({
					"pointer-events": "none",
					"background-color": "#f6f6f6"
				});
			}
		}

		function unlockField(elementId) {
			for (a = 0; a <= elementId.length; a++) {
				$(elementId[a]).css({
					"pointer-events": "",
					"background-color": ""
				});
			}
		}

		function validationDivText(color, className, text) {
			$(className).text(text).attr('style', 'font-weight:bold;text-align:right;width:700px;color:' + color);
		}

		function totalPremium(elementId, discountedPremium, premiumVat, totalPriceSelectedAddonsWithVat) {
			var totalPremium = Number(discountedPremium) + Number(premiumVat) + Number(
				totalPriceSelectedAddonsWithVat);
			var totalPremiumHtml =
				'<div><span style="padding-top: 7px;border-top: 2px solid #E6E9ED;"><b>Total Premium with VAT:</b> AED ' +
				totalPremium.toFixed(2) + '</span></div>';
			$(elementId).show().html(totalPremiumHtml).delay(5000);
		}

		function conditionallyLockFields(shouldReviseQuote) {
			lockField(['#insurer_quote_no', '#actual_premium', '#discounted_premium', , '#car_value', '#excess',
				'#insurerTrim'
			]);

			if (shouldReviseQuote == true) {
				unlockField(["#discounted_premium", "#car_value", "#insurerTrim"]);
			}
			if (shouldReviseQuote == false) {
				unlockField(['#insurer_quote_no', '#actual_premium', '#discounted_premium', , '#car_value',
					'#excess', '#insurerTrim'
				]);
			}
		}

		function getPremiumWithVat(percent, total) {
			return ((percent / 100) * total).toFixed(2)
		}
	});
</script>
@php
}
@endphp

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
											<td style="width: 200px;">{{
												\Carbon\Carbon::createFromTimestamp(strtotime($listQuotePlansMember->dob))->format('d-m-Y')}}
											</td>
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
										@if(isset($listQuotePlanBenefitsFeatures) &&
										!empty($listQuotePlanBenefitsFeatures))
										<tr>
											<td>
												<h6 style="font-weight: bold;">Features & Benefits</h6>
											</td>
										</tr>
										@foreach ($listQuotePlanBenefitsFeatures as $key =>
										$listQuotePlanBenefitsFeature)
										<tr>
											<td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsFeature->text) }}
											</td>
											<td>{{ ucwords($listQuotePlanBenefitsFeature->value) }}</td>
										</tr>
										@endforeach
										@endif
										@if(isset($listQuotePlanBenefitstravelInconvenienceCover) &&
										!empty($listQuotePlanBenefitstravelInconvenienceCover))
										<tr>
											<td>
												<h6 style="font-weight: bold;">Travel Inconvenience Cover</h6>
											</td>
										</tr>
										@foreach ($listQuotePlanBenefitstravelInconvenienceCover as $key =>
										$listQuotePlanBenefitstravelInconvenience)
										<tr>
											<td style="width: 300px;">{{
												ucwords($listQuotePlanBenefitstravelInconvenience->text) }}</td>
											<td>{{ ucwords($listQuotePlanBenefitstravelInconvenience->value) }}</td>
										</tr>
										@endforeach
										@endif
										@if(isset($listQuotePlanBenefitsemergencyMedicalCover) &&
										!empty($listQuotePlanBenefitsemergencyMedicalCover))
										<tr>
											<td>
												<h6 style="font-weight: bold;">Emergency Medical Cover</h6>
											</td>
										</tr>
										@foreach ($listQuotePlanBenefitsemergencyMedicalCover as $key =>
										$listQuotePlanBenefitsemergencyMedical)
										<tr>
											<td style="width: 300px;">{{
												ucwords($listQuotePlanBenefitsemergencyMedical->text) }}</td>
											<td>{{ ucwords($listQuotePlanBenefitsemergencyMedical->value) }}</td>
										</tr>
										@endforeach
										@endif
										@if(isset($listQuotePlanBenefitsInclusions) &&
										!empty($listQuotePlanBenefitsInclusions))
										<tr>
											<td>
												<h6 style="font-weight: bold;">Included in the plan</h6>
											</td>
										</tr>
										@foreach ($listQuotePlanBenefitsInclusions as $key =>
										$listQuotePlanBenefitsInclusion)
										<tr>
											<td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsInclusion->text)
												}}</td>
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
										@foreach ($listQuotePlanBenefitsExclusions as $key =>
										$listQuotePlanBenefitsExclusion)
										<tr>
											<td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsExclusion->text)
												}}</td>
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
											<td style="width: 500px;">{{ ucwords($listQuotePlanBenefitsCovid->text) }}
											</td>
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
							@foreach ($listQuotePlanBenefitsPolicyDetails as $listQuotePlanBenefitsPolicyDetail)
							<tr>
								<td>
									<a href="{{ $listQuotePlanBenefitsPolicyDetail->link }}" target="_blank" title="click to open">
										📃 {{ $listQuotePlanBenefitsPolicyDetail->text }}
									</a>
								</td>
							</tr>
							@endforeach
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
						<a class="nav-link" id="members-tab" data-toggle="tab" href="#members" role="tab" aria-controls="members" aria-selected="true">Members</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" id="benefits-inpatient-tab" data-toggle="tab" href="#benefits-inpatient" role="tab" aria-controls="benefits-inpatient" aria-selected="false">In Patient</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" id="benefits-outpatient-tab" data-toggle="tab" href="#benefits-outpatient" role="tab" aria-controls="benefits-outpatient" aria-selected="false">Out Patient</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" id="benefits-coInsurance-tab" data-toggle="tab" href="#benefits-coInsurance" role="tab" aria-controls="benefits-coInsurance" aria-selected="false">Co-pay/Co-insurance</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" id="benefits-regionCover-tab" data-toggle="tab" href="#benefits-regionCover" role="tab" aria-controls="benefits-regionCover" aria-selected="false">Region coverage &
							Network list</a>
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

					<div class="tab-pane fade show" id="members" role="tabpanel" aria-labelledby="members-tab">
						{{csrf_field()}}
						<input type="hidden" id="healthplanId" name="planId" value="{{ $planId }}">
						<input type="hidden" id="healthquoteUID" name="quoteUID" value="{{ $quoteId }}">
						<table cellpadding="3" cellspacing="3">
							@foreach($members as $i => $member)
							<tr>
								<td style="width: 100px;font-weight: bold;">Member {{$i+1}}:</td>
							</tr>
							<tr>
								<td style="width: 50px;font-weight: bold;">Category</td>
								<td style="width: 120px;">{{$member->memberCategoryText}}</td>
								<td style="width: 50px;font-weight: bold;">DOB</td>
								<td style="width: 120px;">{{\Carbon\Carbon::createFromTimestamp(strtotime($member->dob))->format('d-m-Y')}}</td>
								<td style="width: 50px;font-weight: bold;">Gender</td>
								<td style="width: 120px;">@if($member->gender ==GenericRequestEnum::MALE_SINGLE_VALUE ){{GenericRequestEnum::MALE_SINGLE}}@endif
									@if($member->gender ==GenericRequestEnum::FEMALE_SINGLE_VALUE ){{GenericRequestEnum::FEMALE_SINGLE}}@endif
									@if($member->gender ==GenericRequestEnum::FEMALE_MARRIED_VALUE ){{GenericRequestEnum::FEMALE_MARRIED}}@endif</td>
								<td style="width: 50px;font-weight: bold;">Premium</td>
								<td style="width: 120px;">
									<input type="hidden" class="member_id" value="{{ $member->memberId }}">
									<input type="hidden" class="member_dob" value="{{ $member->dob }}">
									<input type="hidden" class="member_gender" value="{{ $member->gender }}">
									<input type="hidden" class="member_category_text" value="{{ $member->memberCategoryText }}">
									<input type="hidden" class="member_basmah" value="{{ $member->basmah }}">
									<input type="hidden" class="member_vat" value="{{ $member->vat }}">
									<input type="text" value="{{$member->premium}}" class="member_premium form-control" @if($member->premium != 0 && !$isManualPlan) disabled @endif>
								</td>
							</tr>
							@endforeach
							@if($member->premium === 0 || $isManualPlan)
							<tr>
								<td colspan="7"></td>
								<td align="right">
                                    @if($access['carManagerCanEdit'] || $access['carAdvisorCanEdit'])
                                        <button type="submit" class="btn btn-warning btn-sm" id="updatePremium">Update</button>@
                                    @elseif(!auth()->user()->hasAnyRole([RolesEnum::CarAdvisor, RolesEnum::CarManager,RolesEnum::PA]))
                                        <button type="submit" class="btn btn-warning btn-sm" id="updatePremium">Update</button>@
                                    @endif
								</td>
							</tr>
							<tr>
								<td colspan="4">
									<div class="health-quote-plan-validation-div"></div>
								</td>
							</tr>
							@endif
						</table>
					</div>

					<div class="tab-pane fade" id="benefits-inpatient" role="tabpanel" aria-labelledby="benefits-inpatient-tab">
						<table cellpadding="3" cellspacing="3">
							<tr>
								<td>

									<table cellpadding="3" cellspacing="3">
										@foreach ($listQuotePlanBenefitsInpatient as $key =>
										$listQuotePlanBenefitsInpat)
										<tr>
											<td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsInpat->text)
												}}</td>
											<td>{{ ucwords($listQuotePlanBenefitsInpat->value) }}</td>
										</tr>
										@endforeach
									</table>
								</td>
							</tr>
						</table>
					</div>

					<div class="tab-pane fade" id="benefits-outpatient" role="tabpanel" aria-labelledby="benefits-outpatient-tab">
						<table cellpadding="3" cellspacing="3">
							<tr>
								<td>

									<table cellpadding="3" cellspacing="3">
										@foreach ($listQuotePlanBenefitsOutpatient as $key =>
										$listQuotePlanBenefitsOutpat)
										<tr>
											<td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsOutpat->text)
												}}</td>
											<td>{{ ucwords($listQuotePlanBenefitsOutpat->value) }}</td>
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
										@foreach ($listQuotePlanBenefitsCoInsurance as $key =>
										$listQuotePlanCoInsurance)
										<tr>
											<td style="width: 300px;">{{ ucwords($listQuotePlanCoInsurance->text) }}
											</td>
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
										@foreach ($listQuotePlanBenefitsRegionCover as $key =>
										$listQuotePlanRegionCover)
										<tr>
											<td style="width: 300px;">{{ ucwords($listQuotePlanRegionCover->text) }}
											</td>
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
										@foreach ($listQuotePlanBenefitsMaternityCover as $key =>
										$listQuotePlanMaternityCover)
										<tr>
											<td style="width: 300px;">{{ ucwords($listQuotePlanMaternityCover->text) }}
											</td>
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
										@foreach ($listQuotePlanBenefitsExclusions as $key =>
										$listQuotePlanBenefitsExclusion)
										<tr>
											<td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsExclusion->text)
												}}</td>
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
										@foreach ($listQuotePlanBenefitsExclusions as $key =>
										$listQuotePlanBenefitsExclusion)
										<tr>
											<td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsExclusion->text)
												}}</td>
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
							@foreach ($listQuotePlanBenefitsPolicyDetails as $listQuotePlanBenefitsPolicyDetail)
							<tr>
								<td>
									<a href="{{ $listQuotePlanBenefitsPolicyDetail->link }}" target="_blank" title="click to open">
										📃 {{ $listQuotePlanBenefitsPolicyDetail->text }}
									</a>
								</td>
							</tr>
							@endforeach
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
	$("#updatePremium").click(function() {
		var details = $('.member_id').map(function() {
			return {
				memberId: $(this).val(),
				dob: $(this).parent().find('.member_dob').val(),
				gender: $(this).parent().find('.member_gender').val(),
				memberCategoryText: $(this).parent().find('.member_category_text').val(),
				premium: $(this).parent().find('.member_premium').val(),
				basmah: $(this).parent().find('.member_basmah').val(),
				vat: $(this).parent().find('.member_vat').val(),
			};
		}).get();

		$(".loader").show();
		$.ajax({
			url: "{{ url('/health-plan-manual-update-process') }}",
			type: 'post',
			contentType: "application/json; charset=utf-8",
			data: JSON.stringify({
				quoteUID: $('#healthquoteUID').val(),
				planId: $('#healthplanId').val(),
				planDetails: details,
				_token: '{{ csrf_token() }}'
			}),
			success: function(result) {
				$(".loader").hide();
				validationDivText('green', '.health-quote-plan-validation-div', result);
				setTimeout(() => window.location.reload(), 5000);
			},
			error: function(jqXhr, textStatus, errorMessage) {
				$(".loader").hide();
				validationDivText('red', '.health-quote-plan-validation-div', jqXhr
					.responseText);
			}
		});

		function validationDivText(color, className, text) {
			$(className).text(text).attr('style', 'font-weight:bold;text-align:right;color:' + color);
		}
	});
</script>
@else
<div class="row">
	<div class="col-md-12 col-sm-12 admin-detail">
		<div class="x_panel" style="border: none">
			<div class="x_title" style="text-align: center;">
				<div class="h5">{{ isset($listQuotePlanName) ? ucwords($listQuotePlanName) : '' }}</div>
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
						{{csrf_field()}}
						<input type="hidden" id="car_plan_id" name="car_plan_id" value="{{ isset($planId) ? $planId : NULL }}">
						<input type="hidden" id="car_quote_uuid" name="car_quote_uuid" value="{{ isset($quoteId) ? $quoteId : NULL }}">
						<input type="hidden" id="car_value_lower_limit" name="car_value_lower_limit" value="{{ isset($carValueLowerLimit) ? $carValueLowerLimit : 0 }}">
						<input type="hidden" id="car_value_upper_limit" name="car_value_upper_limit" value="{{ isset($carValueUpperLimit) ? $carValueUpperLimit : 0 }}">
						<input type="hidden" id="repair_type" name="repair_type" value="{{ isset($repairType) ? $repairType : NULL }}">
						<input type="hidden" id="is_create" name="is_create" value="0">
						<input type="hidden" id="current_url" name="current_url" value="{{ url()->current() }}">
						<table cellpadding="10" cellspacing="10">
							<tr>
								<td>Hide Plan?</td>
								<td style="width: 10%;">
									<span class="status-text"></span><label class="switch" style="float: left;margin-top: 5px;">
										<input type="checkbox" class="success" id="is_disabled" name="is_disabled" @if(isset($isDisabled) && $isDisabled==GenericRequestEnum::FALSE ) checked="checked" @endif>
										<span class="slider round"></span>
								</td>
								<td>Manual</td>
								<td>
									<span class="status-text"></span><label class="switch" style="float: left;margin-top: 5px;">
										<input type="checkbox" class="success" id="is_manual_update" name="is_manual_update" @if(isset($isManualUpdate) && $isManualUpdate=="true" ) checked="checked" @endif {{ !isset($shouldReviseQuote) && isset($isManualUpdate) ? "disabled" : "" }}>
										<span class="slider round"></span>
								</td>
							</tr>
							<tr>
								<td>Provider Name:</td>
								<td>{{ isset($providerName) ? $providerName : NULL }}</td>
								<td>Repair Type:</td>
								<td>{{ isset($repairType) && $repairType == CarPlanType::COMP ? 'NON-AGENCY' : @$repairType }}</td>
							</tr>
							<tr>
								<td>Insurer Quote No.:</td>
								<td><input type="text" maxlength="50" id="insurer_quote_no" name="insurer_quote_no" value="{{ isset($insurerQuoteNo) ? $insurerQuoteNo : NULL }}" data-value="{{ isset($insurerQuoteNo) ? $insurerQuoteNo : NULL }}" class="form-control"></td>
								<td>Actual Premium:</td>
								<td><input type="text" id="actual_premium" name="actual_premium" value="{{ isset($actualPremium) ? $actualPremium : 0 }}" data-value="{{ isset($actualPremium) ? $actualPremium : 0 }}" class="form-control" onkeypress="return isNumberKey(event,this)"></td>
							</tr>
							<tr>
								<td>Discounted Premium:</td>
								<td>
									<input type="text" id="discounted_premium" name="discounted_premium" value="{{ isset($discountPremium) ? $discountPremium : 0 }}" data-value="{{ isset($discountPremium) ? $discountPremium : 0 }}" class="form-control" onkeypress="return isNumberKey(event,this)">
									<input type="hidden" id="premium_vat" name="premium_vat" value="{{ isset($vat) ? $vat : 0 }}">
								</td>
								<td>Car value:</td>
								<td><input type="text" id="car_value" name="car_value" value="{{ isset($carValue) ? $carValue : 0 }}" class="form-control" style="{{ $readonlyFieldCss }}" onkeypress="return isNumberKey(event,this)">
									<span id="car-value-range-validation-text" style="font-size: 10px;">Min: AED {{ isset($carValueLowerLimit) ? number_format($carValueLowerLimit) : 0 }} -
										Max: AED {{ isset($carValueUpperLimit) ? number_format($carValueUpperLimit) : 0 }}</span>
								</td>
							</tr>
							<tr>
								<td>Excess:</td>
								<td><input type="text" id="excess" name="excess" value="{{ isset($excess) ? $excess : 0 }}" data-value="{{ isset($excess) ? $excess : 0 }}" class="form-control" style="{{ $readonlyFieldCss }}" onkeypress="return isNumberKey(event,this)">
								</td>
								<td>Ancillary Excess:</td>
								<td><select class="form-control" id='ancillary_excess' name="ancillary_excess">
										@for ($i = 0; $i <= 20; $i++) <option value="{{$i}}" {{ isset($ancillaryExcess) && $i == $ancillaryExcess ? 'selected="selected"' : '' }}>{{$i}}%</option>
											@endfor
									</select>
								</td>
							</tr>
							<tr>
								<td>Car Trim</td>
								<td>
									@if(isset($insurerAvailableTrims))
										<select class="form-control car-quote-plan-popup-trim-dropdown" id='insurerTrim' name="insurerTrim">
											@foreach($insurerAvailableTrims as $trim)
											<option value="{{$trim->admeId}}" {{ $trim->admeId == $insurerSelectedTrim ?
												'selected="selected"' : '' }}>{{$trim->description}}</option>
											@endforeach
										</select>
									@endif
								</td>
								<td valign="top"></td>
								<td align="right">
									@if($access['carManagerCanEdit'] || $access['carAdvisorCanEdit'])
										<button type="submit" class="btn btn-warning btn-sm update-car-quote-plan-button" {{ $carQuoteEditDisable }}>Update</button>
                                    @elseif(!auth()->user()->hasAnyRole([RolesEnum::CarAdvisor, RolesEnum::CarManager,RolesEnum::PA]))
                                        <button type="submit" class="btn btn-warning btn-sm update-car-quote-plan-button" {{ $carQuoteEditDisable }}>Update</button>
									@endif
								</td>
							</tr>
							<tr>
								<td colspan="4">
									<div class="car-quote-plan-validation-div"></div>
								</td>
							</tr>
						</table>
						<p>
							<strong>Features</strong>
						<table cellpadding="3" cellspacing="3">
							@if(isset($listQuotePlanBenefitsFeatures))
								@foreach ($listQuotePlanBenefitsFeatures as $key => $listQuotePlanBenefitsFeature)
								<tr>
									<td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsFeature->text) }}</td>
									<td>{{ ucwords($listQuotePlanBenefitsFeature->value) }}</td>
								</tr>
								@endforeach
							@endif
							<tr>
								<td colspan="4"></td>
							</tr>
							<tr>
								<td colspan="4"></td>
							</tr>
							<tr>
								<td colspan="4"><span class="car-quote-plan-total-premium"></span></td>
							</tr>
							<tr>
								<td colspan="3"></td>
								<td> </td>
							</tr>
						</table>
						@if(isset($createdAt))
							<p>
								<span style="float: right;"><strong>Created Date:</strong> {{ $createdAt }}</span><br>
								@if($updatedAt!='')
								<span style="float: right;"><strong>Updated At:</strong> {{ $updatedAt }}</span>							
								@endif
							</p>
						@endif
						</p>
					</div>
					<div class="tab-pane fade" id="addons" role="tabpanel" aria-labelledby="addons-tab">
						<table cellpadding="3" cellspacing="3">
							<tr>
								<td>
									<table cellpadding="3" cellspacing="3">
										@if(isset($listQuotePlanAddons))
											@foreach ($listQuotePlanAddons as $listQuotePlanAddon)
												@foreach ($listQuotePlanAddon->carAddonOption as $carAddonOption)
												<tr>
													<td style="width: 430px;height: 30px;">{{ ucwords($listQuotePlanAddon->text) }}</td>
													<td style="width: 800px;height: 30px;">{{ ucwords($carAddonOption->value) }}</td>
													<td style="width: 430px;height: 30px;">
														<input type="text" id="addon_price" name="addon_price" value="{{ $carAddonOption->price }}" style="width: 100px;" onkeypress="return isNumberKey(event,this)" class="form-control addon_price">
														<input type="hidden" id="addon_vat" name="addon_vat" value="{{ $carAddonOption->vat }}" class="addon_vat">
														<input type="hidden" id="addon_id" name="addon_id" value="{{ $listQuotePlanAddon->id }}" class="addon_id">
														<input type="hidden" id="addon_option_id" name="addon_option_id" value="{{ $carAddonOption->id }}" class="addon_option_id">
													</td>
													<td style="width: 430px;height: 30px;" id="plan_addons">
														<span class="status-text"></span><label class="switch" style="float: left;margin-top: 5px;">
															<input type="checkbox" class="success addon_is_selected" id="addon_is_selected" name="addon_is_selected" @if(isset($carAddonOption->isSelected) &&
															$carAddonOption->isSelected==true ) checked="checked" @endif>
															<span class="slider round"></span>
													</td>
												</tr>
												@endforeach
											@endforeach
										@endif
										<tr>
											<td colspan="3"> </td>
											<td align="center"> </td>
										</tr>
										<tr>
											<td colspan="3"> </td>
											<td align="center"> </td>
										</tr>
										<tr>
											<td colspan="4" align="right">
                                                    @if($access['carManagerCanEdit'] || $access['carAdvisorCanEdit'])
                                                        <button type="submit" class="btn btn-warning btn-sm update-car-quote-plan-button" {{ $carQuoteEditDisable }}>Update</button>
                                                    @elseif(!auth()->user()->hasAnyRole([RolesEnum::CarAdvisor, RolesEnum::CarManager,RolesEnum::PA]))
                                                        <button type="submit" class="btn btn-warning btn-sm update-car-quote-plan-button" {{ $carQuoteEditDisable }}>Update</button>
                                                    @endif
											</td>
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
										@if(isset($listQuotePlanBenefitsInclusions))
											@foreach ($listQuotePlanBenefitsInclusions as $key => $listQuotePlanBenefitsInclusion)
											<tr>
												<td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsInclusion->text)
													}}</td>
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
										@if(isset($listQuotePlanBenefitsExclusions))
											@foreach ($listQuotePlanBenefitsExclusions as $key => $listQuotePlanBenefitsExclusion)
											<tr>
												<td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsExclusion->text)
													}}</td>
												<td>{{ ucwords($listQuotePlanBenefitsExclusion->value) }}</td>
											</tr>
											@endforeach
										@endif
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
										@if(isset($listQuotePlanBenefitsRsas))
											@foreach ($listQuotePlanBenefitsRsas as $key => $listQuotePlanBenefitsRsa)
											<tr>
												<td style="width: 300px;">{{ ucwords($listQuotePlanBenefitsRsa->text) }}
												</td>
												<td>{{ ucwords($listQuotePlanBenefitsRsa->value) }}</td>
											</tr>
											@endforeach
										@endif
									</table>
								</td>
							</tr>
						</table>
					</div>
					<div class="tab-pane fade" id="policy-detail" role="tabpanel" aria-labelledby="policy-detail-tab">
						<table cellpadding="3" cellspacing="3">
							@if(isset($listQuotePlanBenefitsPolicyDetails))
								@foreach ($listQuotePlanBenefitsPolicyDetails as $listQuotePlanBenefitsPolicyDetail)
								<tr>
									<td>
										<a href="{{ $listQuotePlanBenefitsPolicyDetail->link }}" target="_blank" title="click to open">
											📃 {{ $listQuotePlanBenefitsPolicyDetail->text }}
										</a>
									</td>
								</tr>
								@endforeach
							@endif
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
@endif
