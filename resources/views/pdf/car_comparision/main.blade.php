<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insurance Comparison Table</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&family=Raleway:wght@300;400;500;600;700&display=swap');
        
        * {
            font-family: 'Prompt', sans-serif !important;
            box-sizing: border-box;
        }

        .raleway-font {
            font-family: 'Raleway', sans-serif !important;
        }
        
        html, body {
            margin: 0;
            padding: 0;
            line-height: 1;
            font-size: 12px;
            font-weight: 400;
            color: #333333;
        }
        
        div, span, table, tbody, tfoot, thead, tr, th, td, blockquote, dl, dd, h1, h2, h3, h4, h5, h6, hr, figure, p, pre {
            margin: 0;
            font-size: 12px;
            font-weight: 400;
        }

        .separator {
            color: #D3D3D3;
            font-weight: normal;
            padding: 0 5px;
        }
        
        .page {
            width: 100%;
            background-color: white;
            margin: 0;
            padding: 0;
            position: relative;
        }
        
        .font-700 {
            font-weight: 700;
        }

        /* Header section styling */
        .header {
            width: 100%;
            margin: 0 auto 15px;
            padding: 8px 10px;
            border-top: 2px solid #D3D3D3;
            border-bottom: 2px solid #D3D3D3;
            background-color: white;
            display: block;
        }
        
        .header-content {
            width: 100%;
            display: table;
        }
        
        .header-details {
            display: table-cell;
            vertical-align: middle;
            width: 70%;
        }
        
        .header-item {
            display: inline-block;
            padding-right: 6px;
            margin-right: 6px;
            border: 0;
        }
        
        .header-item:last-child {
            border-right: none;
        }
        
        .header-title {
            font-family: 'Raleway', sans-serif !important;
            font-weight: 600;
            font-size: 12px;
            color: #5B5F60;
            margin: 0;
        }
        
        .header-text-highlight {
            font-weight: 500 !important;
            font-size: 8px !important;
            font-family: 'Prompt', sans-serif !important;
        }

        .header-text {
            font-family: 'Prompt', sans-serif;
            font-weight: 400;
            font-size: 8px;
            color: #5B5F60;
            margin: 0;
        }
        
        .quote-ref {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            width: 30%;
            padding-right: 20px;
        }
        
        .quote-ref p {
            font-family: 'Prompt', sans-serif;
            font-weight: 400;
            font-size: 12px;
            margin: 0;
        }
        
        /* Table styling */
        table {
            width: 90%;
            margin: 0 auto;
            border-collapse: collapse;
            table-layout: fixed;
            text-indent: 0;
            border-color: #bfbfbf;
            color: #333333;
            border-spacing: 0;
        }
        
        table tr td, table tr th {
            border: 1px solid #bfbfbf;
            font-size: 12px;
            padding: 2px;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
            white-space: normal;
        }
        
        .table-headers th {
            font-family: 'Raleway', sans-serif !important;
            font-weight: 700;
            font-size: 14px;
            color: #5B5F60;
            background-color: rgba(0, 162, 255, 0.32);
        }
        
        tr {
            page-break-inside: avoid;
        }
        
        table.nested {
            border: none;
            width: 100%;
            padding: 0;
            margin: 0;
            table-layout: fixed;
        }
        
        table.nested td {
            border: none;
            padding: 0;
            margin: 0;
        }
        
        .detail-table {
            margin-top: 10px;
            width: 90%;
            table-layout: fixed;
        }
        
        .detail-table tr td:first-child {
            background-color: rgba(0, 162, 255, 0.32);
            color: #5B5F60;
            font-family: 'Raleway', sans-serif !important;
            font-weight: 700;
            width: 25% !important;
            text-align: left;
            padding-left: 6px;
        }

        /* Section headers */
        .section-header {
            background-color: #1D83BC !important;
            color: white !important;
            font-family: 'Raleway', sans-serif !important;
            font-weight: 700;
            font-size: 14px;
            padding: 5px;
            width: 100% !important;
            text-align: left;
        }
        
        /* Buy button styling */
        .buy-button {
            background-color: #FE7333;
            color: #ffffff;
            padding: 7px 15px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 12px;
            border-radius: 5px;
            line-height: 1.1;
        }

        .btn-buy {
            background-color: #FE7333;
            color: #ffffff;
            padding: 3px 35px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            border-radius: 5px;
            line-height: 0.7;
        }
        
        .buy-button p {
            font-size: 14px;
            margin: 0;
            text-align: center;
            font-weight: 500;
        }
        
        /* View quotes button */
        .view-quotes {
            background-color: #1D83BC;
            color: white;
            border-radius: 5px;
            font-weight: 500;
            text-align: center;
            font-size: 16px;
            height: 30px;
            line-height: 30px;
            padding: 0 10px;
            font-family: 'Prompt', sans-serif;
            display: inline-block;
            text-decoration: none;
            width: 200px;
            position: relative;
        }
        
        .view-quotes a {
            margin: 0;
            text-decoration: none;
            color: white;
            font-weight: 600;
        }
        
        .disclaimer {
            font-size: 14px;
            line-height: 1;
            text-align: left;
            font-family: 'Prompt', sans-serif;
            padding: 10px 20px;
            margin-bottom: 0;
        }

        /* Prevent extra page */
        @page {
            margin: 0;
            padding: 0;
        }

        
        /* footer section */
        .footer{
            background: #1D83BC !important;
            color: white;
            padding: 10px;
            width: 100%;
            height: 160px;
            box-sizing: border-box;
            position: fixed;
            bottom: 0;
            left: 0;
            text-align: center;
        }
        
        .footer-header {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
        }
        
        .footer-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            color: #ffffff;
        }
        
        .footer-td {
            padding: 10px;
            vertical-align: top;
            border: none;
        }
        
        .footer-box {
            border-radius: 24px;
            border: 2px solid #CF9E3C;
            padding: 6px 10px;
            text-align: left;
        }
        
        .hero-image {
            width: 100%;
            margin: 0;
            padding: 0;
            line-height: 0;
        }
        
        .hero-image img {
            width: 100%;
            display: block;
            margin: 0;
            padding: 0;
        }
        
        .content-page {
            /* page-break-before: always; */
            page-break-after: auto;
        }

        .banner-page {
            width: 100%;
            height: 100%;
            display: block;
            position: relative;
        }
        
        .banner-image {
            width: 100%;
            height: 90vh; /* Reduced height to ensure it fits on one page */
            margin: 0 auto;
            text-align: center;
            display: block;
        }
        
        .full-page-image {
            width: 100%;
            z-index: 999;
            height: 88%;
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
        ["code" => "heading", "title" => "Benefits"],
        ["code" => "damage", "title" => "Loss or damage to the insured vehicle", "type" => ["feature", "inclusion", "exclusion"]],
        ["code" => "damageLimit", "title" => "Third party property liability", "type" => "feature"],
        ["code" => "bloodMoney", "title" => "Blood money", "type" => ["inclusion", "exclusion"]],
        ["code" => "fireAndTheft", "title" => "Fire and theft cover", "type" => ["inclusion", "exclusion"]],
        ["code" => "stormAndFlood", "title" => "Storm, flood", "type" => ["inclusion", "exclusion"]],
        ["code" => "riotAndStrike", "title" => "Natural perils riot and strike", "type" => ["inclusion", "exclusion"]],
        ["code" => "repairTypeInfo", "title" => "Repairs", "type" => "prop"],
        ["code" => "emergencyMedicalExpenses", "title" => "Emergency medical expenses", "type" => ["inclusion", "exclusion"]],
        ["code" => "personalBelongings", "title" => "Personal belongings", "type" => ["inclusion", "exclusion"]],
        ["code" => "omanCover", "title" => "Oman cover (orange card not included)", "type" => ["inclusion", "exclusion"]],//also exists in addons, discussed with mujeeb to show from include/exclusion
        ["code" => "offRoadCover", "title" => "Off-road cover", "type" => ["addons", "inclusion", "inclusion", "roadSideAssistance"]],
        ["code" => "guaranteedRepairs", "title" => "Guaranteed repairs", "type" => ["inclusion", "exclusion"]],
        ["code" => "breakdownCover", "title" => "24 hour accident and breakdown recovery", "type" => "addons"],
        ["code" => "ambulanceCover", "title" => "Ambulance cover", "type" => ["inclusion", "exclusion"]],
        ["code" => "excessForWindscreenDamage", "title" => "Excess for windscreen damage", "type" => ["inclusion", "exclusion"]],
        ["code" => "heading", "title" => "Optional covers", "type" => ""],
        ["code" => "driverCover", "title" => "Driver cover", "type" => "addons"],
        ["code" => "passengerCover", "title" => "Passengers cover", "type" => "addons"],
        ["code" => "carHire", "title" => "Hire car benefit", "type" => "addons"],
        ["code" => "spacer"],
        ["code" => "discountPremium", "title" => "Price", "type" => "info",  "heading_class" => "text-heading", "row_class" => 'row-spacing'],
        ["code" => "spacer"],
        ["code" => "vat", "title" => "Vat amount", "type" => "info",  "heading_class" => "text-heading", "row_class" => 'row-spacing'],
        ["code" => "spacer"],
        ["code" => "total", "title" => "Payable amount", "type" => "info",  "heading_class" => "text-heading", "row_class" => 'row-spacing'],
        ["code" => "spacer"],
        ["type" => "buy", "heading_class" => "no-border"],
        ["code" => "spacer"],
        ["code" => "excess", "title" => "Excess", "type" => "info",  "heading_class" => "text-heading", "row_class" => 'row-spacing'],
        ["code" => "spacer"],
        ["code" => "ancillaryExcess", "title" => "Ancillary excess", "type" => "info",  "heading_class" => "text-heading", "row_class" => 'row-spacing'],
    ];
@endphp


	 <!-- first page with first banner image -->
     <div class="page">
        <div class="hero-image">
            <img src="{{ public_path('images/car-banner-pdf-1.png') }}" 
            alt="Car Banner 1">
        </div>
    </div>

    <!-- second page with second banner image -->
    <div class="page">
        <div class="hero-image">
            <img src="{{ public_path('images/car-pdf-banner-2.png') }}" alt="Car Banner 2">
        </div>
    </div>

<div class="page">
    <div class="header">
        <div class="header-content">
            <div class="header-details">
                <div class="header-item">
                    <h1 class="header-title raleway-font">Car Insurance comparison Table <span class="separator">|</span></h1>
                </div>
                <div class="header-item">
                    <p class="header-text">Customer name: <span class="header-text-highlight">{{ $quote->first_name }} {{ $quote->last_name }}</span> <span class="separator">|</span></p> 
                </div>
                <div class="header-item">
                    <p class="header-text">Car make/model: <span class="header-text-highlight">{{ @$quote->carMake->text }} {{ @$quote->carModel->text }}</span> <span class="separator">|</span> </p>
                </div>
                <div class="header-item">
                    <p class="header-text">Year: <span class="header-text-highlight">{{ @$quote->year_of_manufacture }}</span> </p>
                </div>
            </div>
            <div class="quote-ref">
                <p>Quote reference number:<strong> CAR-{{ $quote->uuid }}</strong></p>
            </div>
        </div>
    </div>

    <div style="margin: 20px auto;">
        @if(count($planIds) > 0)
        @php
            // Limit to maximum 5 plans
            $displayPlans = array_slice($planIds, 0, 5);
            $planCount = count($displayPlans);
            
            $firstColWidth = 25;
            $planColWidth = (90 - $firstColWidth) / $planCount;
        @endphp
        <!-- First Table -->
        <table cellspacing="0" cellpadding="0" style="width: 90%; table-layout: fixed; border-collapse: collapse;">
            <colgroup>
                <col style="width: {{ $firstColWidth }}%;">
                @foreach($displayPlans as $planId)
                <col style="width: {{ $planColWidth }}%;">
                @endforeach
            </colgroup>
            <tr>
                <td style="text-align: left; padding: 6px;" class="bg-light-blue raleway-font font-700">Insurance company</td>
                @foreach($displayPlans as $planId)
                <td style="padding:0px;">
                    <table class="nested">
                        <tr>
                            <td style="width: 100%; vertical-align: middle; text-align: center; height: 30px; padding: 5px 0;">
                                <div style="width: 100%; text-align: center;">
                                    @php
                                        $providerCode = strtolower($plans[$planId]->providerCode);
                                        $providerLogoImage = "https://cdn.alfred.ae/assets/logo/partners/{$providerCode}.png";

                                        // Check if the image exists
                                        $headers = @get_headers($providerLogoImage);
                                        if (!$headers || strpos($headers[0], '404') !== false) {
                                            $providerLogoImage = public_path('images/insurance_providers/default.png');
                                        }
                                    @endphp
                                    <img src="{{ $providerLogoImage }}" 
                                    style="max-width: 45px; max-height: 20px; margin: 0 auto; display: block; object-fit: contain;" />
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="border:none; border-top:1px solid #bfbfbf; width: 100%; padding: 0; margin: 0; font-size: 0; line-height: 0;"></td>
                        </tr>
                        <tr>
                            <td style="width: 100%; vertical-align: middle; text-align: center; height: 35px;">
                                <div style="font-size:12px; padding:4px 2px;">{{ $plans[$planId]->providerName }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
                @endforeach
            </tr>
            <tr>
                <td style="text-align: left; padding: 6px;" class="bg-light-blue raleway-font font-700">
                    Plan name
                </td>
                @foreach($displayPlans as $planId)
                <td style="padding: 6px; text-align: center;">
                    {{ $plans[$planId]->name }}
                    @if(isset($plans[$planId]->isRenewal) && $plans[$planId]->isRenewal)
                        <span class="badge badge-success">Renewal Quote</span>
                    @endif
                </td>
                @endforeach
            </tr>
            <tr>
                <td style="text-align: left; padding: 6px;" class="bg-light-blue raleway-font font-700">
                    Price
                </td>
                @foreach($displayPlans as $planId)
                <td style="padding: 6px; text-align: center;">
                    {{ formatAmount($plans[$planId]->discountPremium) }}
                </td>
                @endforeach
            </tr>
            <tr>
                <td style="text-align: left; padding: 6px;" class="bg-light-blue raleway-font font-700">
                    VAT
                </td>
                @foreach($displayPlans as $planId)
                <td style="padding: 6px; text-align: center;">
                    {{ formatAmount($plans[$planId]->vat) }}
                </td>
                @endforeach
            </tr>
            <tr>
                <td style="text-align: left; padding: 6px;" class="bg-light-blue raleway-font font-700">
                    Total price with (VAT)
                </td>
                @foreach($displayPlans as $planId)
                <td style="padding: 6px; text-align: center;">
                    <a class="buy-button" href="{{($websitURL . '/car-insurance/quote/' . $quote->uuid .  '/payment/?providerCode=' . $plans[$planId]->providerCode . '&planId=' . $planId)}}">
                        BUY NOW   <br />
                        <span style="font-size: 10px; font-weight: normal">AED</span> <strong>{{ number_format($plans[$planId]->actualPremium ?? '0.0', 2) }}</strong>
                    </a>
                </td>
                @endforeach
            </tr>
        </table>

        <!-- Second Table -->
        <table class="detail-table" cellspacing="0" cellpadding="0" style="width: 90%; table-layout: fixed; border-collapse: collapse;">
            <colgroup>
                <col style="width: {{ $firstColWidth }}%;">
                @foreach($displayPlans as $planId)
                <col style="width: {{ $planColWidth }}%;">
                @endforeach
            </colgroup>
            <tr>
                <td colspan="{{ count($displayPlans) + 1 }}" class="section-header">
                    Vehicle detail
                </td>
            </tr>
            <tr>
                <td style="text-align: left; padding: 6px;" class="bg-light-blue raleway-font">
                    Excess (Deductible)
                </td>
                @foreach($displayPlans as $planId)
                <td style="padding: 6px; text-align: center;">
                    {{ formatAmount($plans[$planId]->excess) }}
                </td>
                @endforeach
            </tr>
            <tr>
                <td style="text-align: left; padding: 6px;" class="bg-light-blue raleway-font">
                    Ancillary excess
                </td>
                @foreach($displayPlans as $planId)
                <td style="padding: 6px; text-align: center; ">
                    {!! $plans[$planId]->ancillaryExcess ? ($plans[$planId]->ancillaryExcess . '%') : 'TBD' !!}
                </td>
                @endforeach
            </tr>
            <tr>
                <td style="text-align: left; padding: 6px;" class="bg-light-blue raleway-font">
                    Vehicle value
                </td>
                @foreach($displayPlans as $planId)
                <td style="padding: 6px; text-align: center; ">
                    @php
                        $carValue = formatAmount($plans[$planId]->carValue, 0);
                        if($plans[$planId]->repairType == \App\Enums\CarPlanType::TPL) $carValue = 'N/A';
                    @endphp
                    {{ $carValue }}
                </td>
                @endforeach
            </tr>
        </table>

        <!-- Third Table -->
        <table class="detail-table" cellspacing="0" cellpadding="0" style="width: 90%; 
        table-layout: fixed; border-collapse: collapse;">
            <colgroup>
                <col style="width: {{ $firstColWidth }}%;">
                @foreach($displayPlans as $planId)
                <col style="width: {{ $planColWidth }}%;">
                @endforeach
            </colgroup>
            <tr>
                <td colspan="{{ count($displayPlans) + 1 }}" class="section-header">
                    Contents
                </td>
            </tr>
            
            @foreach($features as $feature)
                @if(@$feature['code'] == 'heading' || @$feature['code'] == 'spacer')
                    @continue
                @endif
                
                @if(isset($feature['title']))
                <tr>
                    <td style="text-align: left; padding: 6px;" class="bg-light-blue raleway-font">{{ $feature['title'] }}</td>
                    @foreach($displayPlans as $planId)
                    <td style="padding: 6px; text-align: center;">
                        @if($feature['type'] == 'info')
                            @if($feature['code'] == 'ancillaryExcess')
                                {!! $plans[$planId]->{$feature['code']} ? ($plans[$planId]->{$feature['code']} . '%') : '<span style="color:red">Not applicable</span>' !!}
                            @else
                                {!! $plans[$planId]->{$feature['code']} ? formatAmount($plans[$planId]->{$feature['code']}) : '<span style="color:red">Not applicable</span>' !!}
                            @endif
                        @elseif($feature['type'] == 'prop')
                            {{ $plans[$planId]->{$feature['code']} }}
                        @elseif($feature['type'] == 'buy')
                            @if($plans[$planId]->discountPremium)
                                <a target="_blank" class="buy-button" href="{{($websitURL . '/car-insurance/quote/' . $quote->uuid .  '/payment/?providerCode=' . $plans[$planId]->providerCode . '&planId=' . $planId)}}" >
                                    Buy Now
                                </a>
                            @else
                                N/A
                            @endif
                        @elseif(is_array($feature['type']))
                            @php 
                                $value = "Excluded"; 
                                foreach($feature['type'] as $type) {
                                    if(isset($plans[$planId]->{$type}->{$feature['code']}->value)) {
                                        $value = $plans[$planId]->{$type}->{$feature['code']}->value; 
                                        break;
                                    }
                                }
                            @endphp
                            {{ $value }}
                        @else
                            {{ $plans[$planId]->{$feature['type']}->{$feature['code']}->value ?? 'Excluded' }}
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endif
            @endforeach
        </table>
        @endif

        <!-- View all quotes button -->
        <div style="width: 100%; text-align: center; margin-top: 15px;">
            <a href="{{ $websitURL . '/car-insurance/quote/' . $quote->uuid }}" style="background-color: #1D83BC; color: white; border-radius: 5px; font-weight: 500; padding: 6px 20px; font-size: 16px; display: inline-block; width: 250px; text-align: center; text-decoration: none; line-height: 1.5;">
                View all quotes
                <img src="{{ public_path('images/quote_plans_pages/ecom_home/open_in_new_icon.png') }}" style="width: 16px; height: 16px; vertical-align: middle; margin-left: 5px;margin-top: -4px;">
            </a>
        </div>

        <!-- disclaimer -->
        <div class="disclaimer">
            <p><strong>Disclaimer:</strong> This is a comparison table for illustrative purposes only. The prices and benefits are subject to change without prior notice. Please refer to the official terms and conditions of the insurance provider for the most accurate and current information.</p>
        </div>
    </div>


     <!-- third page with second banner image -->
     <div class="page" style="page-break-before: always !important;">
        <div class="hero-image">
            <img src="{{ public_path('images/home_pdf_second_last_page_with_header.jpg') }}" alt="Car Banner 2">
        </div>
    </div>

    <!-- fourth page with second banner image -->
    <div class="page">
        <div class="hero-image" style="height: auto; max-height: 1170px;">
            <img src="{{ public_path('images/car-comparision-4-image-1.png') }}" alt="Car Banner 2" style="height: auto; max-height: 1170px;">
        </div>
    </div>
    

</div>
</body>
</html>