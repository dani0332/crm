<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Plans Comparison PDF</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&family=Raleway:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            font-family: 'Prompt', sans-serif !important;
        }

        html {
            line-height: 1.5;
            margin: 0px;
        }
        body {
            margin: 0;
            line-height: 1;
            font-family: "DejaVu Sans", sans-serif;
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
        table.tbl-dec tr td, table.tbl-dec tr td a {border: none;}
        table {
            min-width: 1150px;
            width: 1150px;
            text-indent: 0;
            border-color: #bfbfbf;
            max-width: 1150px;
            margin: 7px 12px auto;
            border-spacing: 0;
        }
        .header {
            color: #ffffff;
            font-size: 14px;
            /*font-weight: 600;*/
            text-align: center;
            padding: 8px 0px;
            width: 100%;
            height: 150px;
            max-height: 150px;
        }
        .header .logo {
            background-color: white;
            border-radius: 5px;
            padding: 5px 10px 5px 0px;
            height: 125px;
            max-height: 125px;
        }
        .header .logo img {
            max-height: 125px;
            height: 125px;
        }
        /* .header h3 {
            float: right;
            text-align: right;
            padding-right: 18px;
        } */
        tbody > tr > td {
            border: 1px solid #bfbfbf;
        }
        td > p,
        th > p {
            padding: 4px;
            font-size: 12px;
            text-align: center;
            font-weight: 400;
        }

        .main-table thead th {
            border: 1px solid #bfbfbf;
        }

        .main-table tbody td.label,
        .main-table thead th.label {
            background: #EFF6FF;
            text-align: left;
        }

        .main-table tbody td.label p,
        .main-table thead th.label p {
            text-align: left;
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
        .blue-box {
            background: #ddfdfc;
        }
        .bg-light-blue {
            border: 1px solid #bfbfbf;
            padding: 1px 8px;
            color: #5B5F60;
        }
        .bg-light-blue p {
            /*padding: 1px !important;*/
        }
        .section {
            color: #333393;
            text-align: left;
        }
        .text-black{color: #000000;}
        .provider {
            border: 1px solid #bfbfbf;
            font-size: 14px;
            line-height: 1;
            font-weight: 400;
            color: #4ea4a8;
            vertical-align: middle;
            max-height: 35px;
            height: 35px;
        }
        .spacer {
            padding: 3px;
        }
        .alfred { text-align: right;padding-right: 0;vertical-align: bottom;}
        .quote-info {
            vertical-align: bottom;
            margin-top: -1px;
            font-size: 14px;
            font-weight: 600;
            text-align: left;
            padding: 0;
            max-width: 100%;
        }
        div.quote-info  {

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
            margin-top: 6px;
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
        padding: 3px 35px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            font-weight: normal;
            border-radius: 5px;
        }
        .btn-buy:hover{
            background-color: #d7fbd0;
        }
        .text-heading {
            color: #ffffff;
            background-color: #1d83bc;
        }
        .provider-logo {
            width: 100px;
        }
        @page {
            margin: 0;
            padding: 0;
            margin-bottom: 170px;
        }
        .container
        {
            padding: 0px 50px;
        }
        .no-border {border: none;}
        /* footer {
            position: fixed;
            bottom: 0px;
            left: 0px;
            right: 0px;
            padding: 0px;
            margin: 0px;
            background-color: #1d83bc;
            color: black;
            text-align: center;
        }
        table.tbl-footer {
            padding: 7px 12px;
            margin: 0;
            width: 100%;
            border: none;
        }
        table.tbl-footer tr td, table.tbl-footer tr td a {
            color: #ffffff;
            border: none;
            font-size: 16px;
        } */
        .text-left {text-align: left;}
        .text-right {text-align: right;}
        .full-page-image {
            width: 100%;
            z-index: 999;
            height: 88%;
        }
        .text-center {text-align: center;}
        /*.badge-success {
            color: #fff;
            background-color: #1d83bc;
        }
        .badge {
            display: inline-block;
            padding: 0.25em 0.4em;
            font-size: 50%;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
        }*/

        /* Start Header styles from home_quote_plans.blade.php */
        .raleway-font {
            font-family: 'Raleway', sans-serif !important;
        }

        /* Bottom Header Container */
        .header-bottom {
            width: 100%;
            display: table;
            border-top: 2px solid #D3D3D3;
            border-bottom: 2px solid #D3D3D3;
            font-size: 14px;
            color: #5B5F60;
            padding: 10px 10px;
        }

        /* Left Side Text */
        .header-text {
            display: table-cell;
            text-align: left;
            /*width: 75%;*/
            vertical-align: middle;
        }

        /* Right Side Quote Number */
        .quote-number {
            display: table-cell;
            text-align: right;
            white-space: nowrap;
            width: 25%;
            /*padding-right: 20px;*/
            vertical-align: middle;
        }

        .quote-number strong {
            font-weight: 600; /* Reduce boldness */
        }

        /* Highlighted Text */
        .header-text-highlight {
            font-weight: 600;
        }

        /* Separator Styling */
        .separator {
            color: #D3D3D3; /* Match border color */
            font-weight: normal; /* Ensure it's not bold */
            padding: 0 5px; /* Adjust spacing */
        }

        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            height: 150px;
        }

        main {
            padding: 10px 20px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            background-color: #1d83bc;
            color: #ffffff;
            padding: 0;
            text-align: center;
            height: 160px;
        }

        .footer-table {
            width: 100%;
            /*table-layout: fixed;*/
            border-collapse: collapse;
            color: #ffffff;
            margin: 0;

        }

        .footer-td {
            padding: 0px 8px;
            vertical-align: top;
            border: none;
            text-align: center;

        }

        .footer-box {
            border-radius: 24px;
            border: 2px solid #CF9E3C;
            padding: 6px 10px;
            text-align: left;

        }

        .footer-link {
            color: #ffffff;
            text-decoration: none;
        }

        .footer-link:hover {
            text-decoration: underline;
        }

        .material-icons {
            font-size: 14px;
            color: #ffffff;
            margin-right: 5px;
            vertical-align: middle;
        }

        .footer-header {
            font-size: 16px;
            /*font-weight: bold;*/
            text-align: center;
        }

        .footer-content-1{
            font-size: 9px !important;
        }

        .footer-content-2{
            font-size: 13px !important;
        }

        .advisor-section {
            display: table;
            width: 100%;
            text-align: left;
        }

        .advisor-photo-container {
            display: table-cell;
            vertical-align: middle;
            width: 70px;
        }

        .advisor-photo {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: block;
            margin: auto;
        }

        .advisor-details {
            display: table-cell;
            vertical-align: middle;
            line-height: 0.9;
            padding-left: 10px;
        }

        .advisor-name {
            font-weight: bold;
            font-size: 12px;
            margin: 0;
        }

        .advisor-role {
            font-size: 10px;
            margin: 0;
        }

        .advisor-contact {
            font-size: 12px;
            line-height: 1;
        }

        .advisor-contact .icon {
            width: 10px;
            height: 10px;
            vertical-align: baseline;
            display: inline-block;
        }
        /* End Header styles from home_quote_plans.blade.php */

        /* table td,
        table th {
            max-width: 160px;
            width: 160px;
            height: auto;
            padding: 2px;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
            white-space: normal;
        } */

        .re_border{
            border: 1px solid red;
        }
        .bl_border{
            border: 1px solid blue;
        }
        .gr_border{
            border: 1px solid green;
        }
        .ye_border{
            border: 1px solid yellow;
        }
        .pu_border{
            border: 1px solid magenta;
        }
    </style>
</head>

<body>

@php


    $websitURL = config('constants.AFIA_WEBSITE_DOMAIN');
    $plans = [];

    foreach ($quotePlans->quotes->plans as &$quotePlan)
    {
        $addonsPrice = 0;
        $addonsVat   = 0;

        if (! isset($quotePlan->id) || ! in_array($quotePlan->id, $planIds)) {
            continue;
        }

        $quotePlan->exclusion = json_decode(collect($quotePlan->benefits->exclusion)->keyBy('code')->toJson());
        $quotePlan->inclusion = json_decode(collect($quotePlan->benefits->inclusion)->keyBy('code')->toJson());
        $quotePlan->feature = json_decode(collect($quotePlan->benefits->feature)->keyBy('code')->toJson());
        $quotePlan->roadSideAssistance = json_decode(collect($quotePlan->benefits->roadSideAssistance)->keyBy('code')->toJson());
        $quotePlan->addons = (isset($addons[$quotePlan->id])) ? json_decode(json_encode($addons[$quotePlan->id])) : json_decode(collect($quotePlan->addons)->keyBy('code')->toJson());

        foreach ($quotePlan->addons as &$addon) {

            $addon = (object) $addon;
            //set default value to excluded
            $addon->value = "Excluded";

            //set default values
            $addon->price = 0;
            $addon->vat = 0;

            if(sizeof($addon->carAddonOption))
            {
                //replace exclude with selected value if found

                foreach ($addon->carAddonOption as $index =>  $carAddonOption) {

                    $carAddonOption = (object) $carAddonOption;

                    if($carAddonOption->isSelected) {
                        $addon->value = 'Included';
                        $addonsPrice += $carAddonOption->price;
                        $addonsVat += $carAddonOption->vat;
                        $addon->price = $carAddonOption->price;
                        $addon->vat   = $carAddonOption->vat;
                        break;//only one value will be selected
                    }
                }
            }
        }

        $quotePlan->repairTypeInfo = ($quotePlan->repairType == \App\Enums\CarPlanType::COMP) ? \App\Enums\CarPlanType::NONAGENCY : $quotePlan->repairType;
        $quotePlan->discountPremium += $addonsPrice;
        $quotePlan->vat += $addonsVat;
        $quotePlan->total = $quotePlan->discountPremium  + $quotePlan->vat;
        $plans[$quotePlan->id] = $quotePlan;
    }

    $plans = collect($plans);

    if(!isset($quotePlans->isDataSorted)) {
        $plans->sortByDesc('isRenewal');
    }

    $planIds = $plans->pluck('id')->toArray();


    $features = [
        ["code" => "heading",   "title" => "Vehicle Detail"],
        ["code" => "excess",            "heading_class" => "", "title" => "Excess", "type" => "info",  ],
        ["code" => "ancillaryExcess",   "heading_class" => "", "title" => "Ancillary Excess", "type" => "info", ],
        ["code" => "carValue",          "heading_class" => "", "title" => "Vehicle Value" , 'type' => 'info'],

        ["code" => "heading", "title" => "Benefits"],
        ["code" => "damage", "title" => "Loss or Damage to the Insured Vehicle", "type" => ["feature", "inclusion", "exclusion"]],
        ["code" => "damageLimit", "title" => "Third Party Property Liability", "type" => "feature"],
        ["code" => "bloodMoney", "title" => "Blood Money", "type" => ["inclusion", "exclusion"]],
        ["code" => "fireAndTheft", "title" => "Fire and Theft Cover", "type" => ["inclusion", "exclusion"]],
        ["code" => "stormAndFlood", "title" => "Storm, Flood", "type" => ["inclusion", "exclusion"]],
        ["code" => "riotAndStrike", "title" => "Natural Perils Riot and Strike", "type" => ["inclusion", "exclusion"]],
        ["code" => "repairTypeInfo", "title" => "Repairs", "type" => "prop"],
        ["code" => "emergencyMedicalExpenses", "title" => "Emergency Medical Expenses", "type" => ["inclusion", "exclusion"]],
        ["code" => "personalBelongings", "title" => "Personal belongings", "type" => ["inclusion", "exclusion"]],
        ["code" => "omanCover", "title" => "Oman Cover (Orange card not Included)", "type" => ["inclusion", "exclusion"]],//also exists in addons, discussed with mujeeb to show from include/exclusion
        ["code" => "offRoadCover", "title" => "Off-road Cover", "type" => ["addons", "inclusion", "inclusion", "roadSideAssistance"]],
        ["code" => "guaranteedRepairs", "title" => "Guaranteed Repairs", "type" => ["inclusion", "exclusion"]],
        ["code" => "breakdownCover", "title" => "24 Hour Accident and Breakdown Recovery", "type" => "addons"],
        ["code" => "ambulanceCover", "title" => "Ambulance Cover", "type" => ["inclusion", "exclusion"]],
        ["code" => "excessForWindscreenDamage", "title" => "Excess for Windscreen Damage", "type" => ["inclusion", "exclusion"]],

        ["code" => "heading", "title" => "Optional Covers", "type" => ""],
        ["code" => "driverCover", "title" => "Driver Cover", "type" => "addons"],
        ["code" => "passengerCover", "title" => "Passengers Cover", "type" => "addons"],
        ["code" => "carHire", "title" => "Hire car Benefit", "type" => "addons"],

        /*["code" => "spacer"],
        ["code" => "discountPremium", "title" => "Price", "type" => "info",  "heading_class" => "text-heading", "row_class" => 'row-spacing'],
        ["code" => "spacer"],
        ["code" => "vat", "title" => "VAT Amount", "type" => "info",  "heading_class" => "text-heading", "row_class" => 'row-spacing'],
        ["code" => "spacer"],
        ["code" => "total", "title" => "Payable Amount", "type" => "info",  "heading_class" => "text-heading", "row_class" => 'row-spacing'],
        ["code" => "spacer"],
        ["type" => "buy", "heading_class" => "no-border"],*/
    ];

@endphp
{{--First Page --}}
<img src="{{public_path('images/quote_plans_pages/P1-1.jpg')}}" class="full-page-image" />
<div style="page-break-after: always;"></div>

{{-- Second Page --}}
<img src="{{ public_path('images/quote_plans_pages/commercial_car/commercial_car_second_page.jpg') }}" class="full-page-image" />
<div style="page-break-after: always;"></div>

{{-- PDF Page Header --}}
<header>
    <div class="header">
        <div class="logo">
            <img class="im-logo" src="{{ getIMLogo(true, true) }}" alt="logo">
        </div>
        <div class="header-bottom">
            <!-- Left Side Text -->
            <div class="header-text">
                <strong class="raleway-font" style="font-weight: 600 !important;">Car Insurance Comparison Table</strong>
                <span class="separator">|</span>
                Name: <span class="header-text-highlight">{{ $quote->first_name }} {{ $quote->last_name }}</span>
                <span class="separator">|</span>
                Car Type: <span class="header-text-highlight">{{ @$quote->carMake->text . ' ' . @$quote->carModel->text . ' ' . @$quote->year_of_manufacture }}</span>
                <span class="separator">|</span>
                Year: <span class="header-text-highlight">{{ @$quote->year_of_manufacture }}</span>
            </div>

            <!-- Right Side Quote Number -->
            <div class="quote-number">
                Quote reference number: <strong>{{ $quote->code }}</strong>
            </div>
        </div>

    </div>
</header>


{{-- PDF Page Inner Content --}}
<main>
    <table class="main-table {{ $tableClass ?? 'is-full' }}" style="margin-top:200px">
        <thead>
        <tr>
            <th class="bg-light-blue" rowspan="2">
                <p class="quote-info raleway-font" style="">Insurance company
                </p>
            </th>
            @foreach ($planIds as $planId)
                <th class="provider" style="border: solid 1px #bfbfbf;">
                    <div class="rounded-full">
                        <p class="relative top-[40%] m-auto text-xs">
                            @php
                                $providerLogoImage = public_path(
                                    'images/insurance_providers/' .
                                        strtolower($plans[$planId]->providerCode) .
                                        '.png',
                                );
                                if (!file_exists($providerLogoImage)) {
                                    $providerLogoImage = public_path('images/insurance_providers/default.png');
                                }
                            @endphp
                            <img class="provider-logo" alt="" src="{{ $providerLogoImage }}" />
                        </p>
                    </div>
                </th>
            @endforeach
        </tr>
        <tr>
            @foreach ($planIds as $planId)
                <th style="border: solid 1px #bfbfbf; text-align: center;">
                    <p class="text-center" style="font-size: 14px">
                        {{ $plans[$planId]->providerName ?? '' }}
                    </p>
                </th>
            @endforeach
        </tr>

        {{-- Plan Name --}}
        <tr>
            <th class="bg-light-blue">
                <p class="quote-info raleway-font" style="">Plan name
                </p>
            </th>
            @foreach ($planIds as $planId)
                <th>
                    <p class="text-center" style="font-size: 14px">
                        {{ $plans[$planId]->name ?? '' }}
                    </p>
                </th>
            @endforeach
        </tr>

        <tr>
            <th class="bg-light-blue">
                <p class="quote-info raleway-font" style="font-size: 14px; ">Gross Price</p>
            </th>
            @foreach ($planIds as $planId)
                <th>
                    <p class="text-center" style="font-size: 14px">
                        AED {{$plans[$planId]->discountPremium ?? 0 }}
                    </p>
                </th>
            @endforeach
        </tr>

        <tr>
            <th class="bg-light-blue">
                <p class="quote-info raleway-font" style="font-size: 14px; ">VAT</p>
            </th>
            @foreach ($planIds as $planId)
                <th>
                    <p class="text-center" style="font-size: 14px">
                        AED {{$plans[$planId]->vat ?? 0 }}
                    </p>
                </th>
            @endforeach
        </tr>

        {{-- Total price (with VAT) --}}
        <tr>
            <th class="bg-light-blue">
                <p class="quote-info raleway-font" style="font-size: 14px; ">Total price (with VAT)</p>
            </th>
            @foreach ($planIds as $planId)
                <th>
                    <p class="text-center" style="text-align: center; margin: 0; padding: 2px;">
                        @php
                            if (isset($plans[$planId])) {
                                $buyNowFullLink = $websitURL . '/car-insurance/quote/' . $quote->uuid . '/payment/?providerCode=' . $plans[$planId]->providerCode . '&planId=' . $planId;
                                $buyNowText = 'BUY NOW';

                                $totalPrice = ($plans[$planId]->discountPremium ?? 0) + ($plans[$planId]->vat ?? 0);
                                $totalPriceFormatted = number_format($totalPrice, 2);

                                if ($plans[$planId]->discountPremium) {
                                    echo '<a target="_blank" class="btn-buy" href="' .
                                        $buyNowFullLink .
                                        '">' .
                                        $buyNowText .
                                        '<br/>' .
                                        '<span style="font-size: 10px; font-weight: normal;">AED </span>' .
                                        '<strong>' .
                                        $totalPriceFormatted .
                                        '</strong>' .
                                        '</a>';
                                } else {
                                    echo 'N/A';
                                }
                            } else {
                                echo 'N/A';
                            }
                        @endphp
                    </p>
                </th>
            @endforeach
        </tr>

        {{-- vehicle detail / exact value --}}
        {{--                <tr>--}}
        {{--                    <th class="bg-light-blue">--}}
        {{--                        <p class="quote-info raleway-font" style="">EXACT VEHICLE (INSURER SPECIFIC)</p>--}}
        {{--                    </th>--}}
        {{--                    @foreach ($planIds as $planId)--}}
        {{--                        <th>--}}
        {{--                            <p class="text-center raleway-font" style="font-size: 14px">--}}
        {{--                                {!!   @$quote->carMake->text . ' ' . @$quote->carModel->text . ' ' . @$quote->year_of_manufacture !!}--}}
        {{--                            </p>--}}
        {{--                        </th>--}}
        {{--                    @endforeach--}}
        {{--                </tr>--}}



        <tr style="page-break-inside: avoid;">
            <td class="no-border" colspan="{{ sizeof($planIds) + 1 }}">
                <div class="spacer"></div>
            </td>
        </tr>
        </thead>
        <tbody>
        @foreach($features as $feature)

            {{-- heading row --}}
            @if(@$feature['code'] == 'heading')
                <tr>
                    <td colspan="{{ sizeof($planIds)+1}}" class="text-heading">
                        <p class="text-left">{{$feature['title']}}</p>
                    </td>
                    {{--                            <td   class="text-heading"></td>--}}
                </tr>
                @php continue; @endphp
            @endif

            {{-- spacer row --}}
            @if(@$feature['code'] == 'spacer')
                <tr>
                    <td class="no-border" colspan="{{ sizeof($planIds) + 1 }}"><div class="spacer"></div></td>
                </tr>
                @php continue; @endphp
            @endif

            {{-- feature rows --}}
            <tr class="{{ ($feature['row_class'] ?? "")}}" style="">
                <td class="{{@$feature['heading_class'] ?: 'label' }}"><p class="text-left">{{@$feature['title']}}</p></td>
                @foreach($planIds as $planId)
                    <td class="{{@$feature['col_class']}} ">
                        <p>
                            @if($feature['type'] == 'info')

                                @if($feature['code'] == 'ancillaryExcess')
                                    {!!  $plans[$planId]->{$feature['code']} ? ($plans[$planId]->{$feature['code']} . '%')  : 'TBA' !!}
                                @elseif($feature['code'] == 'carValue')
                                    {!!  $plans[$planId]->{$feature['code']} ? formatAmount($plans[$planId]->{$feature['code']},0)  : 'TBA' !!}
                                @else
                                    {!!  $plans[$planId]->{$feature['code']} ? ($plans[$planId]->repairType == \App\Enums\CarPlanType::TPL) ? formatAmount($plans[$planId]->{$feature['code']}) : 'N/A'  : 'TBA' !!}
                                @endif

                            @elseif($feature['type'] == 'prop')

                                {!!  $plans[$planId]->{$feature['code']} !!}

                            @elseif($feature['type'] == 'buy')

                                @if($plans[$planId]->discountPremium)
                                    <a target="_blank" class="btn-buy" href="{{($websitURL . '/car-insurance/quote/' . $quote->uuid .  '/payment/?providerCode=' . $plans[$planId]->providerCode . '&planId=' . $planId)}}" >Buy Now</a>
                                @else
                                    N/A
                                @endif

                            @elseif(is_array($feature['type']))

                                {{-- we need to check of value is in inclusion or exclusion object, only one value will be printed --}}
                                @php $value = "Excluded"; @endphp
                                @foreach($feature['type'] as $type)
                                    @if(isset($plans[$planId]->{$type}->{$feature['code']}->value))
                                        @php $value = $plans[$planId]->{$type}->{$feature['code']}->value; break; @endphp
                                    @endif
                                @endforeach

                                {!! ($value)  !!}

                            @else
                                {!!  $plans[$planId]->{$feature['type']}->{$feature['code']}->value ?? 'Excluded' !!}
                            @endif
                        </p>
                    </td>
                @endforeach
            </tr>

        @endforeach
        <tr>
            <td colspan="{{sizeof($planIds) + 1}}" class="no-border text-center">
                <a target="_blank" class="btn-all-quotes" href="{{($websitURL . '/car-insurance/quote/' . $quote->uuid )}}" >View All Quotes</a>
            </td>
        </tr>
        </tbody>
    </table>

    <table class="tbl-dec re_border">
        <tbody>
        <tr>
            <td class="gr_border">
                <p class="text-left text-xs">
                    <b>Disclaimer: </b>Quotes are based on the details you provided and may change after the insurer reviews your profile. If there are differences, the insurer's policy terms will apply. Please check your policy once issued to ensure it meets your needs.
{{--                    Whilst we try to ensure the currency and accuracy of the details in the comparison table, there may occasion where there are differences in the covers provided. In such cases, the covers detailed in the insurer's policy wordings and schedules will supersede the details provided by us.<br/><br/>--}}
{{--                    To view the full text of <b>MATERIAL INFORMATION DECLARATION</b> and <b>DISCLAIMER</b>, please refer to the <a class="text-black" href="{{($websitURL . '/car-insurance/quote/' . $quote->uuid )}}"><b>quote</b></a>.--}}
                </p>
            </td>
        </tr>
        </tbody>
    </table>

</main>



{{-- PDF Page Footer --}}
<div class="footer">
    <h4 class="footer-header">
        InsuranceMarket.ae is the registered trademark of AFIA Insurance Brokerage Services LLC
    </h4>

    <table class="footer-table" >
        <tr>
            <td class="footer-td" style="width: 42%">
                <div class="footer-box" style="line-height: 0.8;">
                    <p class="footer-content-1">UAE Central Bank Registration No. 85</p>
                    <p class="footer-content-1">Registered Member of Gulf Insurance Federation</p>
                    <p class="footer-content-1">Registered Member of Emirates Insurance Federation, number B6</p>
                    <p class="footer-content-1">Department of Economy & Tourism in Dubai Trade Licence No. 238534</p>
                    <p class="footer-content-1">Registered member of the DIFC Insurance Association with membership number 10049</p>
                    <p class="footer-content-1">Holder of Health Insurance Intermediary Permit ID No. BRK-00003 from Dubai Health Authority</p>
                    <p class="footer-content-1">Registered member of Insurance Business Group under the Dubai Chamber of Commerce and Industry, number 34774</p>
                </div>
            </td>

            <td class="footer-td" style="width: 28%">
                <div class="footer-box" style="margin-top: 8px; line-height: 0.8; position: relative;">
                    <p class="footer-content-2">27th floor, Control Tower, Detroit Road,<br>Motor City, PO Box - 26423,<br>Dubai, United Arab Emirates.
                    </p>
                    <p class="footer-content-2">Happiness Center number:</p>
                    <p class="footer-content-2">800 ALFRED (800 256 733)</p>
                    <div class="open-new-icon" style="position: absolute; right:5px; top:42px;">
                        <a
                            class="text-white"
                            href="https://www.google.com/maps/place//data=!4m2!3m1!1s0x3e5f42d8a8e59cff:0x24d4afc0d969548c?source=g.page.share">
                            <img src="{{ public_path('images/quote_plans_pages/ecom_home/open_in_new_icon.png') }}"
                                 style="width: 22px; height: 22px; vertical-align: baseline; display: inline-block;">
                        </a>
                    </div>
                </div>
            </td>

            <td class="footer-td" style="width: 30%;">
                <div class="footer-box" style="/*margin-right: 20px;*/ padding: 5px 10px">
                    <div class="advisor-section">
                        @if($quote->advisor)
                            <div class="advisor-photo-container">
                                <img src="{{ $quote->advisor->profile_photo_path != null ? $quote->advisor->profile_photo_path : public_path('images/headset-1.png') }}"
                                     alt="Advisor Photo" class="advisor-photo">
                            </div>
                            <div class="advisor-details">
                                <p class="advisor-name">{{ $quote->advisor->name }}</p>
                                <p class="advisor-role">Insurance Advisor</p>
                                <p class="advisor-contact">
                                    <img src="{{ public_path('images/quote_plans_pages/ecom_home/mail_icon.png') }}" alt="" class="icon">
                                    @if (strlen($quote->advisor->email) > 35)
                                        <span style="text-decoration: underline; font-size:10px">{{ $quote->advisor->email }}</span>
                                    @else
                                        <span style="text-decoration: underline;">{{ $quote->advisor->email }}</span>
                                    @endif
                                    <br>
                                    <img src="{{ public_path('images/quote_plans_pages/ecom_home/smartphone_icon.png') }}" alt="" class="icon">
                                    <a href="tel:{{ removeSpaces(formatMobileNoDisplay($quote->advisor->mobile_no)) }}" class="text-white" style="color: #ffffff; text-decoration: none;">
                                        <span>{{ formatMobileNumber($quote->advisor->mobile_no) }}</span>
                                    </a>
                                    <a href="https://wa.me/{{ removeSpaces(formatMobileNoDisplay($quote->advisor->mobile_no)) }}">
                                        <img src="{{ public_path('images/whatsapp-small.png') }}" alt="" class="icon" style="margin-left: 1px;">
                                    </a>
                                    <br>
                                    <img src="{{ public_path('images/quote_plans_pages/ecom_home/phone_callback_icon.png') }}" alt="" class="icon">
                                    <a href="tel:{{ removeSpaces($quote->advisor->landline_no) }}" class="text-white" style="color: #ffffff; text-decoration: none;">
                                        <span>{{ $quote->advisor->landline_no }}</span>
                                    </a>
                                    <br>
                                    <img src="{{ public_path('images/quote_plans_pages/ecom_home/call_icon.png') }}" alt="" class="icon">
                                    <span>800 ALFRED (800 253 733)</span>
                                </p>
                            </div>
                        @else
                            <div style="text-align: center; width: 100%;">
                                <img src="{{ public_path('image/alfred-theme.png') }}"
                                     alt="Advisor Photo" class="advisor-photo" style="margin: 0 auto; display: block;">
                                <p class="advisor-contact" style="text-align: center; margin-top: 8px;">
                                    <img src="{{ public_path('images/quote_plans_pages/ecom_home/call_icon.png') }}" alt="" class="icon">
                                    <span>800 ALFRED (800 253 733)</span>
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </td>

        </tr>
    </table>
</div>



{{-- Second Last Page --}}
<div style="page-break-after: always;"></div>
<img src="{{ public_path('images/quote_plans_pages/commercial_car/commercial_car_second_last_page.jpg') }}"
     class="full-page-image" />


{{-- Last Page --}}
<div style="page-break-after: always;"></div>
<img src="{{ public_path('images/quote_plans_pages/commercial_car/commercial_car_last_page.jpg') }}"
     class="full-page-image" />
</body>
</html>
