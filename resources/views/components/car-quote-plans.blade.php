<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<script>
$(document).ready(function() {
    $(".editDIV").click(function() {
        $(this).find("span")[0].style.display="none";
        $(this).find("input")[0].style.display="block";
        $(this).find("input")[0].focus();
    });
    $(".editINPUT").blur(function() {
        $(this)[0].style.display="none";
        $(this).prev()[0].innerText=$(this)[0].value;
        $(this).prev().show(300);
    });

    $("input[type='number'][name='discountedPremium[]']").on('input', function() {
        $("#update_discounted_premium").show(300);
    });
});
</script>
<?php
    use App\Enums\CarPlanFeaturesCode;
    use App\Enums\CarPlanAddonsCode;
    use App\Enums\CarPlanExclusionsCode;
?>
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
                            <input type="hidden" id="quotePlansGenerateUrl" name="quotePlansGenerateUrl" value="{{ $uuid }}">
                                @can('car-quotes-create')
                                    <a href="{{ url('quotes/car/'.$uuidModal.'/add_quote') }}" class="btn btn-primary btn-sm">Create Quote</a>
                                @endcan
                            @if(gettype($listQuotePlans) != 'string')
                                <input type="hidden" id="quoteRequestUuId" name="quoteRequestUuId" value="{{ $quoteRequestId }}">
                                <button type="submit" class="btn btn-primary btn-sm" id="update_discounted_premium" style="display:none;">Update Discounted Premium</button>
                                @if(count($listQuotePlans) > 0)
                                    <button type="button" id="quotePlansGenerateButton" name="quotePlansGenerateButton" class="btn btn-warning btn-sm">Copy link</button>
                                @endif
                            @endif
                        </div>
                    </div>
                    <div id="quote-plans">
                        @if(gettype($listQuotePlans) != 'string')
                            <form method="post" action="{{ $uuidModal }}/updateDiscountedPremium" class="form-horizontal form-label-left" role="form"
                            data-parsley-validate=""novalidate="" autocomplete="off">
                            {{csrf_field()}}
                            @method('GET')
                                <table id="datatable" class="table table-striped jambo_table" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Provider Name</th>
                                            <th>Plan Name</th>
                                            <th>Repair Type</th>
                                            <th>TPL Limit</th>
                                            <th>PAB cover</th>
                                            <th>Roadside assistance</th>
                                            <th>Oman cover TPL</th>
                                            <th>Actual Premium</th>
                                            <th>Discounted Premium</th>
                                            <th>Premium with VAT</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($listQuotePlans as $key => $quotePlan)
                                            <tr>
                                                <td>{{ ucwords($quotePlan->providerName) }}</td>
                                                <td>{{ ucwords($quotePlan->name) }}</td>
                                                <td>{{ $quotePlan->repairType }}</td>
                                                <td>
                                                    @foreach ($quotePlan->benefits->feature as $quotePlanFeatures)
                                                        @if(isset($quotePlanFeatures->code))
                                                            @if($quotePlanFeatures->code == CarPlanFeaturesCode::TPL_DAMAGE_LIMIT)
                                                                {{ $quotePlanFeatures->value }}
                                                            @endif
                                                        @else
                                                            @if(strtolower($quotePlanFeatures->text) == CarPlanFeaturesCode::TPL_DAMAGE_LIMIT_TEXT)
                                                                {{ $quotePlanFeatures->value }}
                                                            @endif
                                                        @endif
                                                    @endforeach
                                                </td>
                                                <td>
                                                    <table style="margin-left: -10px;margin-top: -10px;">
                                                        @foreach ($quotePlan->addons as $quotePlanAddon)
                                                            @foreach ($quotePlanAddon->carAddonOption as $quotePlanOptions)
                                                                @if(isset($quotePlanAddon->code))
                                                                    @if($quotePlanAddon->code == CarPlanAddonsCode::DRIVER_COVER || $quotePlanAddon->code == CarPlanAddonsCode::PASSENGER_COVER)
                                                                        <tr style="background-color: transparent;">
                                                                            <td style="border-top: none !important;">{{ $quotePlanAddon->text }}:</td>
                                                                            <td style="border-top: none !important;">{{ $quotePlanOptions->value }}</td>
                                                                        </tr>
                                                                    @endif
                                                                @else
                                                                    @if(strtolower($quotePlanAddon->text) == CarPlanAddonsCode::DRIVER_COVER_TEXT || strtolower($quotePlanAddon->text) == CarPlanAddonsCode::PASSENGER_COVER_TEXT)
                                                                        <tr style="background-color: transparent;">
                                                                            <td style="border-top: none !important;">{{ $quotePlanAddon->text }}:</td>
                                                                            <td style="border-top: none !important;">{{ $quotePlanOptions->value }}</td>
                                                                        </tr>
                                                                    @endif
                                                                @endif
                                                            @endforeach
                                                        @endforeach
                                                    </table>
                                                </td>
                                                <td>
                                                    <table style="margin-left: -10px;margin-top: -10px;">
                                                        @foreach ($quotePlan->benefits->roadSideAssistance as $quotePlanRsa)
                                                            <tr style="background-color: transparent;">
                                                                <td style="border-top: none !important;">{{ $quotePlanRsa->text }}:</td>
                                                                <td style="border-top: none !important;">{{ $quotePlanRsa->value }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </table>
                                                </td>
                                                <td>
                                                    <table style="margin-left: -10px;margin-top: -10px;">
                                                    @foreach ($quotePlan->benefits->exclusion as $key => $quotePlanExclusion)
                                                        @if(isset($quotePlanExclusion->code) && $quotePlanExclusion->code == CarPlanExclusionsCode::TPL_OMAN_COVER)
                                                            <tr style="background-color: transparent;">
                                                                <td style="border-top: none !important;">{{ $quotePlanExclusion->text }}:</td>
                                                                <td style="border-top: none !important;">{{ $quotePlanExclusion->value }}</td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                    @foreach ($quotePlan->benefits->inclusion as $key => $quotePlanInclusion)
                                                        @if(isset($quotePlanExclusion->code) && $quotePlanInclusion->code == CarPlanExclusionsCode::TPL_OMAN_COVER)
                                                            <tr style="background-color: transparent;">
                                                                <td style="border-top: none !important;">{{ $quotePlanInclusion->text }}:</td>
                                                                <td style="border-top: none !important;">{{ $quotePlanInclusion->value }}</td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                    </table>
                                                </td>
                                                <td>{{ $quotePlan->actualPremium }}</td>
                                                <td>
                                                    <input type="hidden" id="quote_plan_id[]" name="quote_plan_id[]" value="{{ $quotePlan->id }}">
                                                    <div class="editDIV1">
                                                        <span class="editESPAN" style="display:block;line-height: unset;">{{ $quotePlan->discountPremium }}</span>
                                                        <input type="number" id="discountedPremium[]" name="discountedPremium[]" value="{{ $quotePlan->discountPremium }}" class="editINPUT" style="display:none;" size="8" maxlength="8">
                                                    </div>
                                                </td>
                                                <td>{{ $quotePlan->discountPremium + $quotePlan->vatPremium }}</td>
                                                <td><a href="#" planDetailUrl="{{ $uuidModal }}/plan_details/{{ $quotePlan->id }}"
                                                    data-toggle="modal" data-target="#quotePlanModal" class="quotePlanModalPopup">View</a></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </form>
                            @else
                            <table id="datatable" class="table table-striped jambo_table" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Provider Name</th>
                                        <th>Plan Name</th>
                                        <th>Repair Type</th>
                                        <th>TPL Limit</th>
                                        <th>PAB cover</th>
                                        <th>Roadside assistance</th>
                                        <th>Oman cover TPL</th>
                                        <th>Actual Premium</th>
                                        <th>Discounted Premium</th>
                                        <th>Premium with VAT</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                            <tbody>
                                <tr class="odd">
                                    <td valign="top" colspan="11" class="dataTables_empty">{{ ucfirst($listQuotePlans) }}</td>
                                </tr>
                            </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
