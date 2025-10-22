<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Plans Comparison PDF</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&family=Raleway:wght@300;400;500;600;700&display=swap" rel="stylesheet">

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
            height: 88%;
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

        $dirhamIcon = 'data:image/svg+xml;base64,' . base64_encode('
        <svg viewBox="0 0 14 14" color="#333333" xmlns="http://www.w3.org/2000/svg">
            <path d="M13.0747 6.85449L12.9829 6.76976C12.8346 6.62854 12.6581 6.55792 12.4674 6.55792H11.4788C11.4929 6.72739 11.5 6.89687 11.5 7.08045C11.5 7.26405 11.4929 7.43351 11.4788 7.61004H12.1496C12.6581 7.61004 13.0747 8.0902 13.0747 8.69041V8.95873L12.9829 8.86694C12.8346 8.73277 12.6581 8.66216 12.4674 8.66216H11.3305C10.7868 11.0418 8.88736 12.334 5.89341 12.334H2.05212C2.05212 12.334 2.57464 11.9315 2.57464 10.5828V8.66216H1.93208C1.41661 8.66216 1 8.17494 1 7.5818V7.31347L1.09886 7.39821C1.24008 7.53237 1.41661 7.61004 1.60726 7.61004H2.57464V6.55792H1.93208C1.41661 6.55792 1 6.0707 1 5.47756V5.20924L1.09886 5.30103C1.24008 5.4352 1.41661 5.50581 1.60726 5.50581H2.57464V3.66283C2.57464 2.27178 2.05212 1.83398 2.05212 1.83398H5.89341C8.80262 1.83398 10.7515 3.11206 11.3234 5.50581H12.1496C12.6581 5.50581 13.0747 5.98597 13.0747 6.58617V6.85449ZM5.75219 2.35651H4.1493V5.50581H9.53699C9.16981 3.31683 7.91997 2.35651 5.75219 2.35651ZM9.66409 7.08045C9.66409 6.89687 9.65703 6.72739 9.64996 6.55792H4.1493V7.61004H9.64996C9.65703 7.43351 9.66409 7.26405 9.66409 7.08045ZM4.1493 11.8044H5.76631C8.0612 11.7479 9.19099 10.6464 9.53699 8.66216H4.1493V11.8044Z" fill="currentColor"/>
        </svg>
    ');
        $dirhamIconWhite = 'data:image/svg+xml;base64,' . base64_encode('
        <svg viewBox="0 0 14 14" color="#ffffff" xmlns="http://www.w3.org/2000/svg">
            <path d="M13.0747 6.85449L12.9829 6.76976C12.8346 6.62854 12.6581 6.55792 12.4674 6.55792H11.4788C11.4929 6.72739 11.5 6.89687 11.5 7.08045C11.5 7.26405 11.4929 7.43351 11.4788 7.61004H12.1496C12.6581 7.61004 13.0747 8.0902 13.0747 8.69041V8.95873L12.9829 8.86694C12.8346 8.73277 12.6581 8.66216 12.4674 8.66216H11.3305C10.7868 11.0418 8.88736 12.334 5.89341 12.334H2.05212C2.05212 12.334 2.57464 11.9315 2.57464 10.5828V8.66216H1.93208C1.41661 8.66216 1 8.17494 1 7.5818V7.31347L1.09886 7.39821C1.24008 7.53237 1.41661 7.61004 1.60726 7.61004H2.57464V6.55792H1.93208C1.41661 6.55792 1 6.0707 1 5.47756V5.20924L1.09886 5.30103C1.24008 5.4352 1.41661 5.50581 1.60726 5.50581H2.57464V3.66283C2.57464 2.27178 2.05212 1.83398 2.05212 1.83398H5.89341C8.80262 1.83398 10.7515 3.11206 11.3234 5.50581H12.1496C12.6581 5.50581 13.0747 5.98597 13.0747 6.58617V6.85449ZM5.75219 2.35651H4.1493V5.50581H9.53699C9.16981 3.31683 7.91997 2.35651 5.75219 2.35651ZM9.66409 7.08045C9.66409 6.89687 9.65703 6.72739 9.64996 6.55792H4.1493V7.61004H9.64996C9.65703 7.43351 9.66409 7.26405 9.66409 7.08045ZM4.1493 11.8044H5.76631C8.0612 11.7479 9.19099 10.6464 9.53699 8.66216H4.1493V11.8044Z" fill="currentColor"/>
        </svg>
    ');

@endphp
{{--First Page --}}
<img src="{{public_path('images/quote_plans_pages/personal-car-cover.jpg')}}" class="full-page-image"  style="height:90%;"/>
@component('pdf.components.pdf_footer_section',['quote' => $quote,'ecomInsuranceLink'=>config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$quote->uuid])
      
     
@endcomponent
<div style="page-break-after: always;"></div>

{{-- Second Page --}}
<img src="{{ public_path('images/quote_plans_pages/commercial_car/commercial_car_second_page.jpg') }}" class="full-page-image"   style="height:90%;"/>
@component('pdf.components.pdf_footer_section',['quote' => $quote,'ecomInsuranceLink'=>config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$quote->uuid])
      
     
@endcomponent
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
                <th class="provider" style="padding: 1px;">
                    <div style="display: grid; grid-template-columns: 1fr auto; width: 100%; height: 70px; position: relative; overflow: hidden;">
                        <!-- Center logo area -->
                        <div style="display: flex; align-items: center; justify-content: center; grid-column: 1 / -1; z-index: 1;">
                            @php
                                $providerCode = strtolower($plans[$planId]->providerCode);
                                $providerLogoImage = "https://cdn.alfred.ae/assets/logo/partners/{$providerCode}.png";

                                // Check if the image exists
                                $headers = @get_headers($providerLogoImage);
                                if (!$headers || strpos($headers[0], '404') !== false) {
                                    $providerLogoImage = public_path('images/insurance_providers/default.png');
                                }
                            @endphp
                            <img class="provider-logo" alt="" src="{{ $providerLogoImage }}" style="max-height: 65px; width: auto; max-width: 180px;" />
                        </div>

                        <!-- Renewal tag positioned at top-right -->
                        @if(isset($plans[$planId]->isRenewal) && $plans[$planId]->isRenewal)
                                                         <div style="position: absolute; right: 10px; top: 0; z-index: 2;">
                                <img alt="Renewal Plan" src="{{ public_path('images/renewal-plan-tag.png') }}" style="height: 40px; width: auto; max-width: 100px; display: block;" />
                            </div>
                        @endif
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
                    <p class="text-center" style="font-size: 14px;">
                        <img src="{{$dirhamIcon}}" class=""
                             style="width: 16px; height: 16px; position: relative; top: 3px; padding: 0px;"
                             alt="Dirham Icon" /> {{$plans[$planId]->discountPremium ?? 0 }}
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
                        <img src="{{$dirhamIcon}}" class=""
                             style="width: 16px; height: 16px; position: relative; top: 3px; padding: 0px;"
                             alt="Dirham Icon" /> {{$plans[$planId]->vat ?? 0 }}
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
                                        '<span style="font-size: 10px; font-weight: normal;"><img src="'.$dirhamIconWhite.'" class=""
                                 style="width: 16px; height: 16px; position: relative; top: 3px; padding: 0px;"
                                 alt="Dirham Icon" /> </span>' .
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
                            @php
                                if ($feature['type'] == 'info') {
                                    if ($feature['code'] == 'ancillaryExcess') {
                                        $value = $plans[$planId]->{$feature['code']} ? ($plans[$planId]->{$feature['code']} . '%') : 'TBA';
                                    } elseif ($feature['code'] == 'carValue') {
                                        $value = $plans[$planId]->{$feature['code']} ? (($plans[$planId]->repairType == \App\Enums\CarPlanType::TPL) ? 'N/A'  : formatAmount($plans[$planId]->{$feature['code']}, 0)) : 'TBA';
                                    } else {
                                        $value = $plans[$planId]->{$feature['code']} ? formatAmount($plans[$planId]->{$feature['code']}, 0) : 'TBA';
                                    }
                                } elseif ($feature['type'] == 'prop') {
                                    $value = $plans[$planId]->{$feature['code']};
                                } elseif ($feature['type'] == 'buy') {
                                    if ($plans[$planId]->discountPremium) {
                                        $value = '<a target="_blank" class="btn-buy" href="' . ($websitURL . '/car-insurance/quote/' . $quote->uuid . '/payment/?providerCode=' . $plans[$planId]->providerCode . '&planId=' . $planId) . '">Buy Now</a>';
                                    } else {
                                        $value = 'N/A';
                                    }
                                } elseif (is_array($feature['type'])) {
                                    // we need to check of value is in inclusion or exclusion object, only one value will be printed
                                    $value = "Excluded";
                                    foreach ($feature['type'] as $type) {
                                        if (isset($plans[$planId]->{$type}->{$feature['code']}->value)) {
                                            $value = $plans[$planId]->{$type}->{$feature['code']}->value;
                                            break;
                                        }
                                    }
                                } else {
                                    $value = $plans[$planId]->{$feature['type']}->{$feature['code']}->value ?? 'Excluded';
                                }

                                // Check if the value contains "AED" as a standalone word and replace it with dirham icon
                                $dirhamIconHtml = '<img src="'.$dirhamIcon.'" class="" style="width: 16px; height: 16px; position: relative; top: 3px; padding: 0px;" alt="Dirham Icon" />';

                                // Use regex to match AED as a standalone word (with word boundaries)
                                $value = preg_replace('/\bAED\b/', $dirhamIconHtml, $value);
                            @endphp

                            {!! $value !!}
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

    <table class="tbl-dec ">
        <tbody>
        <tr>
            <td class="">
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



    {{-- PDF Page Footer Section --}}
    @component('pdf.components.pdf_footer_section',['quote' => $quote,'ecomInsuranceLink'=>config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$quote->uuid])
    @endcomponent

{{-- End of PDF Page Footer Section --}}


{{-- Second Last Page --}}
<div style="page-break-after: always;"></div>
<img src="{{ public_path('images/quote_plans_pages/commercial_car/commercial_car_second_last_page.jpg') }}"
     class="full-page-image"  style="height:90%;" />
     @component('pdf.components.pdf_footer_section',['quote' => $quote,'ecomInsuranceLink'=>config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$quote->uuid])
      
     
@endcomponent


{{-- Last Page --}}
<div style="page-break-after: always;"></div>
<img src="{{ public_path('images/quote_plans_pages/commercial_car/commercial_car_last_page.jpg') }}"
     class="full-page-image"  style="height:90%;" />
     @component('pdf.components.pdf_footer_section',['quote' => $quote,'ecomInsuranceLink'=>config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$quote->uuid])
@endcomponent
</body>
</html>
