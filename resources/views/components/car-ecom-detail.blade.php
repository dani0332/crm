@php
    use App\Enums\PaymentStatusEnum;
@endphp
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
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="PRICE"><b>PRICE</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">{{ $record->premium ? $record->premium: '' }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="PAID AT"><b>PAID AT</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">{{ $record->paid_at ? $record->paid_at: '' }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col" >
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="PAYMENT STATUS"><b>PAYMENT STATUS</b></label>
                        <div class="col-md-3 col-sm-3">
                        <p class="label-align-center">{{ $record->payment_status_id_text ? $record->payment_status_id_text: '' }}</p>
                        </div>
                        @if($record->payment_status_id == PaymentStatusEnum::DECLINED)
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="PAYMENT STATUS"><b>Reason</b></label>
                            <div class="col-md-3 col-sm-3">
                                <p class="label-align-center">{{ !empty($mainPayment->payment_status_message) ? $mainPayment->payment_status_message : "" }}</p>
                            </div>
                        @endif
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="PROVIDER NAME"><b>PROVIDER NAME</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">{{ $record->car_plan_provider_id_text ? $record->car_plan_provider_id_text: '' }}</p>
                        </div>
                    </div>
                </div>

                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="PAYMENT METHOD"><b>PAYMENT METHOD</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">
                        {{ $record->payment_gateway == 'NGENIUS' ? 'CREDIT CARD': $record->payment_gateway }}
                        </p>
                        </div>
                    </div>
                    <div class="col">

                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="PLAN NAME"><b>PLAN NAME</b></label>
                        <div class="col-md-6 col-sm-6">
                        <p class="label-align-center">{{ $record->plan_id_text ? ucwords($record->plan_id_text): '' }}</p>
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
                                                        <td style="width: 53px;">{{ $carQuotePlanAddon->car_quote_request_addon_price ? $carQuotePlanAddon->car_quote_request_addon_price: 'Free' }}</td>
                                                        <td>
                                                            @if($carQuotePlanAddon->car_quote_request_addon_price)
                                                                <input type="checkbox" checked class="car-quote-ecom-non-free-plan-check">
                                                            @else
                                                                <input type="checkbox" checked class="car-quote-ecom-free-plan-check">
                                                            @endif
                                                        </td>
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
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="ECOMMERCE"><b>ECOMMERCE</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $record->is_ecommerce == 1 ? 'Yes' : 'No' }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="QUOTE LINK"><b>QUOTE LINK</b></label>
                        <div class="col-md-6 col-sm-6" style="text-overflow: ellipsis;overflow: auto;white-space: nowrap;width: 495px;">
                            <p class="label-align-center">{{ $record->quote_link ? $record->quote_link: '' }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="ORDER REFERENCE"><b>ORDER REFERENCE</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $record->order_reference ? $record->order_reference: '' }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="PAYMENT REFERENCE"><b>PAYMENT REFERENCE</b></label>
                        <div class="col-md-6 col-sm-6" style="text-overflow: ellipsis;overflow: auto;white-space: nowrap;width: 495px;">
                            <p class="label-align-center">{{ $record->payment_reference ? $record->payment_reference: '' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
