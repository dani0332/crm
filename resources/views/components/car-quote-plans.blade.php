@php
use App\Enums\CarPlanFeaturesCode;
use App\Enums\CarPlanAddonsCode;
use App\Enums\CarPlanExclusionsCode;
use App\Enums\CarPlanType;
@endphp
<div class="row">
	<div class="col-md-12 col-sm-12">
		<div class="x_panel">
			<div class="x_title">
				<h2>Available Plans</h2>
				<div class="clearfix"></div>
			</div>
			<div class="x_content">
				<div class="row">
					<div class="col-auto mr-auto"></div>
					<span class="alert alert-success" id="quotePlansGenerateMsg" style="display: none">Copied</span>
					<div class="col-auto">
						<input type="hidden" id="quotePlansGenerateUrl" name="quotePlansGenerateUrl"
							value="{{ $ecomUrl }}">
						@can('car-quotes-plans-create')
						<a href="{{ url('quotes/car/'.$record->uuid.'/create-quote') }}"
							class="btn btn-primary btn-sm">Create Quote</a>
						@endcan
						@if(gettype($listQuotePlans) != 'string')
						<input type="hidden" id="quoteRequestUuId" name="quoteRequestUuId" value="{{ $record->id }}">
						<button type="submit" class="btn btn-primary btn-sm" id="update_discounted_premium"
							style="display:none;">Update Discounted Premium</button>
						@if(count($listQuotePlans) > 0)
						<button type="button" id="quotePlansGenerateButton" name="quotePlansGenerateButton"
							class="btn btn-warning btn-sm">Copy link</button>
						@endif
						@endif
					</div>
				</div>
				@if(gettype($listQuotePlans) != 'string')
				<form method="post" action={{route('manualPlanToggle')}} class="form-horizontal form-label-left"
					role="form" id="togglePlanForm" data-parsley-validate="" novalidate="" autocomplete="off">
					{{ csrf_field() }}
					<div class="row" id="toggle-plans-div">
						<div class="col-md-12 col-sm-12">
							<div class="x_panel">
								<div class="x_title">
									<h2>Toggle Plans</h2>
									<div class="clearfix"></div>
								</div>
								<div id="">
									<div class="item form-group">
										<label class="col-form-label col-md-2 col-sm-2" for="Assign To"></label>
										<div class="col-md-6 col-sm-6">
											<select class="form-control" id="toggle" name="toggle">
												<option value="">Please Select</option>
												<option value="0">Show</option>
												<option value="1"> Hide</option>
											</select>
										</div>
									</div>
									<div class="item form-group">
										<label class="col-form-label col-md-2 col-sm-2" for="first-name"> </label>
										<div class="col-md-6 col-sm-6">
											<div class="input-group">
												<button id="togglePlans" type="button"
													class="btn btn-warning btn-sm">Update</button>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<input type="hidden" id="planIds" name="planIds" value="">
					<input type="hidden" id="car_quote_uuid" name="car_quote_uuid" value="{{$record->uuid}}">
				</form>
				<table id="dataTableCarQuotePlans" class="table table-striped jambo_table datatable-car-quote-plans"
					style="table-layout: fixed;" style="width:100%">
					<thead>
						<tr>
							<th> <input type="checkbox" id="flowcheckall" value="" /></th>
							<th>Provider Name</th>
							<th>Plan Name</th>
							<th>Repair Type</th>
							<th>Insurer Quote No.</th>
							<th>TPL Limit</th>
							<th>Car Trim</th>
							<th>PAB cover</th>
							<th>Roadside assistance</th>
							<th>Oman cover TPL</th>
							<th>Actual Premium</th>
							<th>Discounted Premium</th>
							<th>Premium with VAT.</th>
							<th>Excess</th>
							<th>Action</th>
						</tr>
					</thead>
					<tbody>
						@foreach ($listQuotePlans as $key => $quotePlan)
							@if(!isset($quotePlan->id))
								@continue;
							@endif
						<tr>
							<td>
								<input type="checkbox" class="car_plans_checkbox" name="toggle_plans_checkbox"
									value="{{$quotePlan->id}}" />
							</td>
							<td>{{ ucwords($quotePlan->providerName) }}
								<br>
								@isset($quotePlan->isDisabled)
								@if($quotePlan->isDisabled)
								<span class="badge badge-danger">Hidden</span>
								@endif
								@endisset
								@isset($quotePlan->isRenewal)
								@if($quotePlan->isRenewal)
								<span class="badge badge-success">Renewal</span>
								@endif
								@endisset
								@isset($quotePlan->isManualUpdate)
								@if($quotePlan->isManualUpdate)
								<span class="badge badge-primary">Manual</span>
								@endif
								@endisset
							</td>
							<td><a href="#" planDetailUrl="{{ $record->uuid }}/plan_details/{{ $quotePlan->id }}"
									data-toggle="modal" data-target="#quotePlanModal" class="quotePlanModalPopup">{{
									ucwords($quotePlan->name) }}</a></td>
							<td>{{ $quotePlan->repairType == CarPlanType::COMP ? 'NON-AGENCY' : $quotePlan->repairType
								}}</td>
							<td>
								@isset($quotePlan->insurerQuoteNo)
								{{ $quotePlan->insurerQuoteNo }}
								@endisset
							</td>
							<td>
								@foreach ($quotePlan->benefits->feature as $quotePlanFeatures)
								@if(isset($quotePlanFeatures->code))
								@if($quotePlanFeatures->code == CarPlanFeaturesCode::TPL_DAMAGE_LIMIT ||
								$quotePlanFeatures->code == CarPlanFeaturesCode::DAMAGE_LIMIT)
								{{ $quotePlanFeatures->value }}
								@endif
								@else
								@if(strtolower($quotePlanFeatures->text) == CarPlanFeaturesCode::TPL_DAMAGE_LIMIT_TEXT)
								{{ $quotePlanFeatures->value }}
								@endif
								@endif
								@endforeach
							</td>
							<td>{{ isset($quotePlan->insurerTrimText) ? $quotePlan->insurerTrimText : '' }}</td>
							<td>
								<table style="margin-left: -10px;margin-top: -10px !important;">
									@php
									$totalSelectedAddonsPriceWithVat = 0;
									@endphp
									@foreach ($quotePlan->addons as $quotePlanAddon)
									@foreach ($quotePlanAddon->carAddonOption as $quotePlanOptions)
									<?php
                                    if (isset($quotePlanOptions->isSelected)) {
                                        if ($quotePlanOptions->isSelected == true && $quotePlanOptions->price != 0) {
                                            $totalSelectedAddonsPriceWithVat += $quotePlanOptions->price + $quotePlanOptions->vat;
                                        }
                                    } else {
                                        $totalSelectedAddonsPriceWithVat = 0;
                                    }
									?>
									@if(isset($quotePlanAddon->code))
									@if(strtolower($quotePlanAddon->code) == strtolower(CarPlanAddonsCode::DRIVER_COVER)
									||
									strtolower($quotePlanAddon->code) == strtolower(CarPlanAddonsCode::PASSENGER_COVER))
									<tr style="background-color: transparent;">
										<td style="border-top: none !important;">{{ $quotePlanAddon->text }}:</td>
										<td style="border-top: none !important;">{{ $quotePlanOptions->value }}</td>
									</tr>
									@endif
									@elseif(strtolower($quotePlanAddon->text) == CarPlanAddonsCode::DRIVER_COVER_TEXT ||
									strtolower($quotePlanAddon->text) == CarPlanAddonsCode::PASSENGER_COVER_TEXT)
									<tr style="background-color: transparent;">
										<td style="border-top: none !important;">{{ $quotePlanAddon->text }}:</td>
										<td style="border-top: none !important;">{{ $quotePlanOptions->value }}</td>
									</tr>
									@endif
									@endforeach
									@endforeach
								</table>
							</td>
							<td>
								<table style="margin-left: -10px;margin-top: -10px !important;">
									@foreach ($quotePlan->benefits->roadSideAssistance as $quotePlanRsa)
									<tr style="background-color: transparent;">
										<td style="border-top: none !important;">{{ $quotePlanRsa->text }}:</td>
										<td style="border-top: none !important;">{{ $quotePlanRsa->value }}</td>
									</tr>
									@endforeach
								</table>
							</td>
							<td>
								<table style="margin-left: -10px;margin-top: -10px !important;">
									@foreach ($quotePlan->benefits->exclusion as $key => $quotePlanExclusion)
									@if(isset($quotePlanExclusion->code))
									@if(strtolower($quotePlanExclusion->code) ==
									strtolower(CarPlanExclusionsCode::TPL_OMAN_COVER) ||
									strtolower($quotePlanExclusion->code) ==
									strtolower(CarPlanExclusionsCode::OMAN_COVER))
									<tr style="background-color: transparent;">
										<td style="border-top: none !important;">{{ $quotePlanExclusion->text }}:</td>
										<td style="border-top: none !important;">{{ $quotePlanExclusion->value }}</td>
									</tr>
									@endif
									@endif
									@endforeach
									@foreach ($quotePlan->benefits->inclusion as $key => $quotePlanInclusion)
									@if(isset($quotePlanInclusion->code) && (strtolower($quotePlanInclusion->code) ==
									strtolower(CarPlanExclusionsCode::TPL_OMAN_COVER) ||
									(strtolower($quotePlanInclusion->code) ==
									strtolower(CarPlanExclusionsCode::OMAN_COVER))))
									<tr style="background-color: transparent;">
										<td style="border-top: none !important;">{{ $quotePlanInclusion->text }}:</td>
										<td style="border-top: none !important;">{{ $quotePlanInclusion->value }}</td>
									</tr>
									@endif
									@endforeach
								</table>
							</td>
							<td>{{ $quotePlan->actualPremium ? number_format($quotePlan->actualPremium, 2) : '0.00' }}
							</td>
							<td>{{ $quotePlan->discountPremium ? number_format($quotePlan->discountPremium, 2) :
								'0.00'}}</td>
							<td>
								@php
								$totalPremium = $quotePlan->discountPremium + $quotePlan->vat +
								$totalSelectedAddonsPriceWithVat;
								@endphp
								{{ $totalPremium ? number_format($totalPremium, 2) : '0.00' }}
							</td>
							<td>{{ isset($quotePlan->excess) ? number_format($quotePlan->excess, 2) : '0.00' }}</td>
							<td><a href="#" planDetailUrl="{{ $record->uuid }}/plan_details/{{ $quotePlan->id }}"
									data-toggle="modal" data-target="#quotePlanModal"
									class="btn btn-warning btn-sm quotePlanModalPopup">View</a>
                                @if($totalPremium > 0)
                                    <button
                                    class="btn btn-success btn-sm payment-link-copy"
                                    data-planId="{{$quotePlan->id}}"
                                    data-quoteUUId="{{$record->uuid}}"
                                    data-providerCode="{{$quotePlan->providerCode}}"
                                    >Copy</button>
                                @endif
                                </td>
						</tr>
						@endforeach
					</tbody>
				</table>
                <span class="alert alert-success" id="payment-link-copy-msg"
                style="display: none;float:right;position: absolute;z-index: 1;top: -16px;right: 0;">Copied</span>
				@else
				<table id="datatable" class="table table-striped jambo_table" style="width:100%">
					<thead>
						<tr>
							<th>Provider Name</th>
							<th>Plan Name</th>
							<th>Repair Type</th>
							<th>Insurer Quote No.</th>
							<th>TPL Limit</th>
							<th>Car Trim</th>
							<th>PAB cover</th>
							<th>Roadside assistance</th>
							<th>Oman cover TPL</th>
							<th>Actual Premium</th>
							<th>Discounted Premium</th>
							<th>Premium with VAT.</th>
							<th>Excess</th>
							<th>Action</th>
						</tr>
					</thead>
				</table>
				@endif
			</div>
		</div>
	</div>
</div>
