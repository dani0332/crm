<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Plans Comparison PDF</title>

    <style>
        @page {
            margin: 0;
            padding: 0;
        }

        html {
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        body {
            line-height: 1;
            font-family: "DejaVu Sans", sans-serif;

        }

        header {
            position: fixed;
            top: 0;
            left: 0;
            height: 200px;
            width: 100%;
            display: block;
        }

        div,
        span,
        table,
        tbody,
        tfoot,
        thead,
        tr,
        th,
        td,
        blockquote,
        dl,
        dd,
        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        hr,
        figure,
        p,
        pre {
            margin: 0;
        }

        a {
            text-decoration: inherit;
        }

        b,
        strong {
            font-weight: bolder;
        }

        table.tbl-dec {
            border: none;
        }

        table.tbl-dec tr td,
        table.tbl-dec tr td a {
            border: none;
        }

        table {
            min-width: 1220px;
            width: 1220px;
            text-indent: 0;
            border-color: #bfbfbf;
            max-width: 1220px;
            margin: 7px 12px auto;
            border-spacing: 0;
        }

        tbody {
            margin-bottom: 130px;
        }

        .header {
            background: #1d83bc;
            color: #ffffff;
            font-size: 16px;
            text-align: center;
            padding: 8px 10px;
            width: 100%;
            height: 57px;
            max-height: 57px;
        }

        .header .logo {
            float: left;
            background-color: white;
            border-radius: 5px;
            padding: 5px 10px 5px 0px;
            height: 50px;
            max-height: 50px;
        }

        .header .logo img {
            max-height: 50px;
            height: 50px;
        }

        .header h3 {
            float: right;
            text-align: right;
            padding-right: 18px;
        }

        tbody>tr>td {
            border: 1px solid #bfbfbf;
        }

        thead>tr>th {
            border: 1px solid #bfbfbf;
        }

        td>p,
        td>div>p,
        td>div>div>p,
        th>p {
            padding: 4px;
            font-size: 14px;
            text-align: center;
            font-weight: normal;
        }

        .text-left {
            text-align: left;
        }

        .text-xs {
            font-size: 13px;
        }

        .text-sm {
            font-size: 14px;
        }

        .text-xl {
            font-size: 16px;
        }

        .blue-box {
            background: #ddfdfc;
        }

        .bg-light-blue {
            border: 1px solid #bfbfbf;
            background: #EFF6FF;
            padding: 8px;
            color: #252525;
        }

        .text-black {
            color: #000000;
        }

        .provider {
            border: 1px solid #bfbfbf;
            font-size: 15px;
            line-height: 28px;
            font-weight: 400;
            color: #4ea4a8;
            vertical-align: middle;
            max-height: 50px;
            height: 50px;
            position: relative;
        }

        .spacer {
            padding: 3px;
        }

        .alfred {
            text-align: right;
            padding-right: 0;
            vertical-align: bottom;
            border-left: none;
            border-top: none;
        }

        .quote-info {
            text-align: right;
            vertical-align: bottom;
            margin-top: -1px;
            background: #EFF6FF;
            font-size: 14px;
            text-align: left;
            padding: 8px;
            max-width: 100%;
            font-weight: normal;
        }

        .info h5 {
            background: #1d83bc;
            color: #ffffff;
            padding: 3px;
            font-weight: normal;
            margin: 0 0 10px 0;
        }

        .info p {
            font-size: 12px;
        }

        .btn-all-quotes {
            background-color: #1d83bc;
            color: #ffffff;
            padding: 8px 25px;
            margin-top: 50px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 15px;
            font-weight: bold;
            border-radius: 5px;
            margin-bottom: 0px;
        }

        .btn-buy {
            background-color: #FE7333;
            color: #ffffff;
            padding: 12px 15px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            font-weight: bold;
            border-radius: 5px;
        }

        .btn-buy:hover {
            background-color: #d7fbd0;
        }

        .text-heading {
            color: #ffffff;
            background-color: #1d83bc;
        }

        .heading-desc {
            font-size: 12px;
        }

        .image-wrapper {
            min-width: 150px;
            min-height: 150px;
            width: 150px;
            height: 150px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin: 20px auto;
        }

        .provider-logo {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            display: block;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }


        .no-border {
            border: none;
        }

    

      

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .full-page-image {
            width: 100%;
            z-index: 999;
            height: 100%;
        }

        .text-center {
            text-align: center;
        }

        .text-white {
            color: #ffffff
        }

        .text-underline {
            text-decoration: underline
        }

        .footer {
        position: fixed;
        bottom: 0;
        /* top: 50px !important; */
        left: 0;
        right: 0;
        width: 100%;
        background-color: #1d83bc;
        color: #ffffff;
        padding: 2px 2px 2px 2px;
        text-align: left;
        height: 165px !important;
    }

   
   

        .full-page-image {
            width: 100%;
            z-index: 999;
            height: 100%;
        }


        .footer-content-1 {
        font-size: 8px !important;
        line-height: 0.7 !important;
        word-break: break-word;
        white-space: normal !important;
        width: 100%;
        box-sizing: border-box;
        align-items: left;
        text-align: left;
    }

    @media (max-width: 600px) {
        .footer-content-1 {
            font-size: 8px !important;
            padding-left: 2px;
            padding-right: 2px;
        }
    }

    .footer-content-2{
        font-size: 8px !important;
        align-items: center;
        text-align: center;
        line-height: 0.8 !important;
        word-break: break-word;
        white-space: normal !important;
        width: 100%;
        box-sizing: border-box;
        align-items: left;
        text-align: left;
    }
    @media (max-width: 600px) {
        .footer-content-2 {
            font-size: 9px !important;
            padding-left: 2px;
            padding-right: 2px;
        }
    }
    </style>
</head>

<body>

    @php

        $websitURL = config('constants.AFIA_WEBSITE_DOMAIN');
        $plans = [];

        foreach ($quotePlans->quotes->plans as &$quotePlan) {
            $addonsPrice = 0;
            $addonsVat = 0;

            if (!isset($quotePlan->id) || !in_array($quotePlan->id, $planIds)) {
                continue;
            }

            $quotePlan->exclusion = json_decode(
                collect($quotePlan->benefits->exclusion)
                    ->keyBy('code')
                    ->toJson(),
            );
            $quotePlan->inclusion = json_decode(
                collect($quotePlan->benefits->inclusion)
                    ->keyBy('code')
                    ->toJson(),
            );
            $quotePlan->feature = json_decode(
                collect($quotePlan->benefits->feature)
                    ->keyBy('code')
                    ->toJson(),
            );
            $quotePlan->roadSideAssistance = json_decode(
                collect($quotePlan->benefits->roadSideAssistance)
                    ->keyBy('code')
                    ->toJson(),
            );
            $quotePlan->addons = isset($addons[$quotePlan->id])
                ? json_decode(json_encode($addons[$quotePlan->id]))
                : json_decode(
                    collect($quotePlan->addons)
                        ->keyBy('code')
                        ->toJson(),
                );

            foreach ($quotePlan->addons as &$addon) {
                $addon = (object) $addon;
                //set default value to excluded
                $addon->value = 'Excluded';

                //set default values
                $addon->price = 0;
                $addon->vat = 0;

                if (sizeof($addon->bikeAddonOption)) {
                    //replace exclude with selected value if found

                    foreach ($addon->bikeAddonOption as $index => $bikeAddonOption) {
                        $bikeAddonOption = (object) $bikeAddonOption;

                        if ($bikeAddonOption->isSelected) {
                            $addon->value = 'Included';
                            $addonsPrice += $bikeAddonOption->price;
                            $addonsVat += $bikeAddonOption->vat;
                            $addon->price = $bikeAddonOption->price;
                            $addon->vat = $bikeAddonOption->vat;
                            break; //only one value will be selected
                        }
                    }
                }
            }

            $quotePlan->repairTypeInfo =
                $quotePlan->repairType == \App\Enums\CarPlanType::COMP
                    ? \App\Enums\CarPlanType::NONAGENCY
                    : $quotePlan->repairType;
            $quotePlan->discountPremium += $addonsPrice;
            $quotePlan->vat += $addonsVat;
            $quotePlan->total = $quotePlan->discountPremium + $quotePlan->vat;
            $plans[$quotePlan->id] = $quotePlan;
        }

        $plans = collect($plans);

        if (!isset($quotePlans->isDataSorted)) {
            $plans->sortByDesc('isRenewal');
        }

        $planIds = $plans->pluck('id')->toArray();

        $features = [
            ['code' => 'heading', 'title' => 'BENEFITS'],
            [
                'code' => 'damage',
                'title' => 'Loss or Damage to the Insured Vehicle',
                'type' => ['feature', 'inclusion', 'exclusion'],
            ],
            ['code' => 'damageLimit', 'title' => 'Third Party Property Liability', 'type' => 'feature'],
            ['code' => 'bloodMoney', 'title' => 'Blood Money', 'type' => ['inclusion', 'exclusion']],
            ['code' => 'fireAndTheft', 'title' => 'Fire and Theft Cover', 'type' => ['inclusion', 'exclusion']],
            ['code' => 'stormAndFlood', 'title' => 'Storm, Flood', 'type' => ['inclusion', 'exclusion']],
            [
                'code' => 'riotAndStrike',
                'title' => 'Natural Perils Riot and Strike',
                'type' => ['inclusion', 'exclusion'],
            ],
            ['code' => 'repairTypeInfo', 'title' => 'Repairs', 'type' => 'prop'],
            [
                'code' => 'emergencyMedicalExpenses',
                'title' => 'Emergency Medical Expenses',
                'type' => ['inclusion', 'exclusion'],
            ],
            ['code' => 'personalBelongings', 'title' => 'Personal belongings', 'type' => ['inclusion', 'exclusion']],
            [
                'code' => 'omanCover',
                'title' => 'Oman Cover (Orange card not Included)',
                'type' => ['inclusion', 'exclusion'],
            ], //also exists in addons, discussed with mujeeb to show from include/exclusion
            [
                'code' => 'offRoadCover',
                'title' => 'Off-road Cover',
                'type' => ['addons', 'inclusion', 'inclusion', 'roadSideAssistance'],
            ],
            ['code' => 'guaranteedRepairs', 'title' => 'Guaranteed Repairs', 'type' => ['inclusion', 'exclusion']],
            ['code' => 'breakdownCover', 'title' => '24 Hour Accident and Breakdown Recovery', 'type' => 'addons'],
            ['code' => 'ambulanceCover', 'title' => 'Ambulance Cover', 'type' => ['inclusion', 'exclusion']],
            ['code' => 'heading', 'title' => 'Optional Covers', 'type' => ''],
            ['code' => 'driverCover', 'title' => 'Driver Cover', 'type' => 'addons'],
            ['code' => 'passengerCover', 'title' => 'Passengers Cover', 'type' => 'addons'],
            ['code' => 'spacer'],
            [
                'code' => 'discountPremium',
                'title' => 'Price',
                'type' => 'info',
                'heading_class' => 'text-heading',
                'row_class' => 'row-spacing',
            ],
            ['code' => 'spacer'],
            [
                'code' => 'vat',
                'title' => 'VAT Amount',
                'type' => 'info',
                'heading_class' => 'text-heading',
                'row_class' => 'row-spacing',
            ],
            ['code' => 'spacer'],
            [
                'code' => 'total',
                'title' => 'Payable Amount',
                'type' => 'info',
                'heading_class' => 'text-heading',
                'row_class' => 'row-spacing',
            ],
            ['code' => 'spacer'],
            ['type' => 'buy', 'heading_class' => 'no-border'],
            ['code' => 'spacer'],
            [
                'code' => 'excess',
                'title' => 'Excess',
                'type' => 'info',
                'heading_class' => 'text-heading',
                'row_class' => 'row-spacing',
            ],
            ['code' => 'spacer'],
            [
                'code' => 'ancillaryExcess',
                'title' => 'Ancillary Excess',
                'type' => 'info',
                'heading_class' => 'text-heading',
                'row_class' => 'row-spacing',
            ],
        ];

    @endphp

    <img src="{{ public_path('images/quote_plans_pages/imcrm_plans_bike_first_page.jpg') }}" class="full-page-image" style="height: 90%;"/>

  
    {{-- PDF Page Footer Section --}}
    @component('pdf.components.pdf_footer_section',['quote' => $quote,'ecomInsuranceLink'=>config('constants.ECOM_BIKE_INSURANCE_QUOTE_URL').$quote->uuid])
      
     
    @endcomponent
{{-- End of PDF Page Footer Section --}}

    <div class="font">

        <div class="header">
            <div class="logo">
                <img class="im-logo" src="{{ public_path('images/logo-new.png') }}" />
            </div>
            <h3>Your Tailor Made <br />Bike Insurance Comparison Table</h3>
        </div>

        <div class="container">
            <table class="table-fixed text-center tbl-plans">
                <thead>
                    <tr>

                        <th class="alfred" rowspan="3">
                            <img style="" src="{{ public_path('images/alfred.png') }}" />
                        </th>

                        @foreach ($planIds as $planId)
                            <th class="provider">
                                <div class="rounded-full">
                                    <p class="relative top-[40%] m-auto text-xs">
                                        @php
                                        $providerCode = strtolower($plans[$planId]->providerCode);
                                        $providerLogoImage = "https://cdn.alfred.ae/assets/logo/partners/{$providerCode}.png";
    
                                        // Check if the image exists
                                        $headers = @get_headers($providerLogoImage);
                                        if (!$headers || strpos($headers[0], '404') !== false) {
                                            $providerLogoImage = public_path('images/insurance_providers/default.png');
                                        }
                                    @endphp
                                        <img class="provider-logo" alt="" src="{{ $providerLogoImage }}" />
                                    </p>
                                </div>
                            </th>
                        @endforeach

                    </tr>
                </thead>
                <tbody>

                    <tr>
                        @foreach ($planIds as $planId)
                            <td>
                                <p class="text-center">
                                    {{ $plans[$planId]->providerName }}
                                </p>
                            </td>
                        @endforeach
                    </tr>

                    <tr>
                        @foreach ($planIds as $planId)
                            <td>
                                <p class="text-center">
                                    {{ $plans[$planId]->name }}
                                </p>
                                @if (isset($plans[$planId]->isRenewal) && $plans[$planId]->isRenewal)
                                    <span class="badge badge-success">Renewal Quote</span>
                                @else
                                    <img style="margin-top:3px"
                                        src="{{ public_path('images/quote_plans_pages/imcrm_plan_renewal_empty_tag.png') }}" />
                                @endif
                            </td>
                        @endforeach
                    </tr>

                    {{-- buy now row --}}
                    <tr>
                        <td class="bg-light-blue">
                            <p class="quote-info">Bike insurance comparison for: <b>{{ $quote->first_name }}
                                    {{ $quote->last_name }}</b></p>
                        </td>
                        @foreach ($planIds as $planId)
                            <td>
                                <p class="text-center">
                                    @if ($plans[$planId]->discountPremium)
                                        <a target="_blank" class="btn-buy"
                                            href="{{ $websitURL . '/bike-insurance/quote/' . $quote->uuid . '/payment/?providerCode=' . $plans[$planId]->providerCode . '&planId=' . $planId }}">Buy
                                            Now</a>
                                    @else
                                        N/A
                                    @endif
                                </p>
                            </td>
                        @endforeach
                    </tr>

                    {{-- vehicle detail / exact value --}}
                    <tr class="bg-light-blue">
                        <td>
                            <p>EXACT VEHICLE (INSURER SPECIFIC)</p>
                        </td>
                        @foreach ($planIds as $planId)
                            <td>
                                <p class="text-center">{!! @$quote->bikeQuote->bikeMake->text .
                                    ' ' .
                                    @$quote->bikeQuote->bikeModel->text .
                                    ' ' .
                                    @$quote->year_of_manufacture !!}</p>
                            </td>
                        @endforeach
                    </tr>

                    <tr class="bg-light-blue">
                        <td>
                            <p>VEHICLE VALUE</p>
                        </td>
                        @foreach ($planIds as $planId)
                            <td>
                                @php
                                    $plan = $plans[$planId];
                                    $bikeValue = formatAmount($plan->bikeValue, 0);
                                    if ($plan->repairType == \App\Enums\CarPlanType::TPL) {
                                        $bikeValue = 'N/A';
                                    }
                                @endphp

                                <p class="text-center">{!! $bikeValue !!}</p>
                            </td>
                        @endforeach
                    </tr>

                    @foreach ($features as $feature)
                        {{-- heading row --}}
                        @if (@$feature['code'] == 'heading')
                            <tr>
                                <td colspan="1" class="text-heading">
                                    <p class="text-left">{{ $feature['title'] }}</p>
                                </td>
                                <td colspan="{{ sizeof($planIds) }}" class=""></td>
                            </tr>
                            @php continue; @endphp
                        @endif

                        {{-- spacer row --}}
                        @if (@$feature['code'] == 'spacer')
                            <tr>
                                <td class="no-border" colspan="{{ sizeof($planIds) + 1 }}">
                                    <div class="spacer"></div>
                                </td>
                            </tr>
                            @php continue; @endphp
                        @endif

                        {{-- feature rows --}}
                        <tr class="{{ $feature['row_class'] ?? '' }}" style="">
                            <td class="{{ @$feature['heading_class'] }}">
                                <p class="text-left">{{ @$feature['title'] }}</p>
                            </td>
                            @foreach ($planIds as $planId)
                                <td class="{{ @$feature['col_class'] }}">
                                    <p>
                                        @if ($feature['type'] == 'info')
                                            @if ($feature['code'] == 'ancillaryExcess')
                                                {!! $plans[$planId]->{$feature['code']} ? $plans[$planId]->{$feature['code']} . '%' : 'TBA' !!}
                                            @else
                                                {!! $plans[$planId]->{$feature['code']} ? formatAmount($plans[$planId]->{$feature['code']}) : 'TBA' !!}
                                            @endif
                                        @elseif($feature['type'] == 'prop')
                                            {!! $plans[$planId]->{$feature['code']} !!}
                                        @elseif($feature['type'] == 'buy')
                                            @if ($plans[$planId]->discountPremium)
                                                <a target="_blank" class="btn-buy"
                                                    href="{{ $websitURL . '/bike-insurance/quote/' . $quote->uuid . '/payment/?providerCode=' . $plans[$planId]->providerCode . '&planId=' . $planId }}">Buy
                                                    Now</a>
                                            @else
                                                N/A
                                            @endif
                                        @elseif(is_array($feature['type']))
                                            {{-- we need to check of value is in inclusion or exclusion object, only one value will be printed --}}
                                            @php $value = "Excluded"; @endphp
                                            @foreach ($feature['type'] as $type)
                                                @if (isset($plans[$planId]->{$type}->{$feature['code']}->value))
                                                    @php
                                                        $value = $plans[$planId]->{$type}->{$feature['code']}->value;
                                                        break;
                                                    @endphp
                                                @endif
                                            @endforeach

                                            {!! $value !!}
                                        @else
                                            {!! $plans[$planId]->{$feature['type']}->{$feature['code']}->value ?? 'Excluded' !!}
                                        @endif
                                    </p>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                    <tr>
                        <td colspan="{{ sizeof($planIds) + 1 }}" class="no-border text-center">
                            <a target="_blank" class="btn-all-quotes"
                                href="{{ $websitURL . '/bike-insurance/quote/' . $quote->uuid }}">View All
                                Quotes</a>
                        </td>
                    </tr>
                </tbody>
            </table>

            <table class="tbl-dec">
                <tbody>
                    <tr>
                        <td>
                            <span class="text-sm"><b>MATERIAL INFORMATION DECLARATION</b></span>
                            <p class="text-left text-xs">All quotes we provide are indicative and based on the
                                information you have provided to us.</p>
                            <span class="text-sm"><b>DISCLAIMER</b></span>
                            <p class="text-left text-xs">
                                Whilst we try to ensure the currency and accuracy of the details in the comparison
                                table, there may occasion where there are differences in the covers provided. In such
                                cases, the covers detailed in the insurer's policy wordings and schedules will supersede
                                the details provided by us.<br /><br />
                                To view the full text of <b>MATERIAL INFORMATION DECLARATION</b> and <b>DISCLAIMER</b>,
                                please refer to the <a class="text-black"
                                    href="{{ $websitURL . '/bike-insurance/quote/' . $quote->uuid }}"><b>quote</b></a>.
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>

        </div>
    </div>

    <img src="{{ public_path('images/quote_plans_pages/imcrm_plans_bike_last_page.jpg') }}" class="full-page-image"  style="height: 90%;"/>
    @component('pdf.components.pdf_footer_section',['quote' => $quote,'ecomInsuranceLink'=>config('constants.ECOM_BIKE_INSURANCE_QUOTE_URL').$quote->uuid])
      
     
@endcomponent
</body>

</html>
