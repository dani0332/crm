<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insurance Comparison Table</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Raleway:wght@400;700&family=Prompt:wght@300;400;500&family=Prompt:wght@400;600&family=Inter:wght@500&display=swap');
        
        html {
            font-family: 'Prompt', sans-serif;
        }
        
        body {
            font-family: 'Prompt', sans-serif;
            color: #333333;
            margin: 0;
            padding: 0;
            position: relative;
            min-height: 100vh;
        }
        
        .page {
            width: 100%;
            background-color: white;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            height: 100vh;
            position: relative;
            /* page-in */
        }
        
        /* Redesigned header section for Laravel Snappy compatibility */
        .header {
            width: 100%;
            margin: 0 auto 20px;
            padding: 8px 10px;
            border-top: 1px solid rgba(51, 51, 51, 0.2);
            border-bottom: 1px solid rgba(51, 51, 51, 0.2);
            background-color: white;
            box-sizing: border-box;
            display: block;
            clear: both;
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
            border-right: 1px solid rgba(91, 95, 96, 0.3);
            border-top:0px;
            border-bottom:0px;
            border-left:0px;
        }
        
        .header-item:last-child {
            border-right: none;
        }
        
        .header-title {
            font-family: 'Raleway', sans-serif;
            font-weight: 700;
            font-size: 12px;
            color: #5B5F60;
            margin: 0;
        }
        
        .header-text {
            font-family: 'Prompt', sans-serif;
            font-weight: 300;
            font-size: 10px;
            color: #5B5F60;
            margin: 0;
        }
        
        .quote-ref {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            width: 30%;
        }
        
        .quote-ref p {
            font-family: 'Prompt', sans-serif;
            font-weight: 400;
            font-size: 12px;
            letter-spacing: 1.04%;
            margin: 0;
        }
        
        /* Keep the rest of your existing CSS */
        .logo-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            background-color: white;
        }
        
        .main-content {
            display: flex;
            flex-direction: column;
            gap: 16px;
            padding: 0 40px;
        }
        
        .comparison-table {
            display: flex;
            justify-content: center;
            width: 100%;
            page-break-inside: avoid;
        }
        
        .table-left {
            display: flex;
            flex-direction: column;
            gap: 6px;
            width: 400px;
        }
        
        .table-headers {
            display: flex;
            flex-direction: column;
            width: 100%;
        }
        
        .header-row {
            display: flex;
            align-items: center;
            width: 100%;
            padding: 6px;
            border-right: none;
        }
        
        .header-row:first-child {
            border-top-left-radius: 8px;
        }
        
        .header-row:last-child {
            border-bottom-left-radius: 8px;
        }
        
        .header-row p {
            font-family: 'Raleway', sans-serif;
            font-weight: 700;
            font-size: 14px;
            color: #5B5F60;
            margin: 0;
        }
        
        .section-header {
            background-color: #1D83BC;
            color: white;
            font-family: 'Raleway', sans-serif;
            font-weight: 700;
            font-size: 14px;
            padding: 4px 6px;
            border: 1px solid #B9B9B9;
            border-right: none;
            page-break-after: avoid;
        }
        
        .section-header:first-of-type {
            border-top-left-radius: 8px;
        }
        
        .section-header p {
            font-size: 14px;
            margin: 0;
        }
        
        .content-row {
            background-color: #DBEEFF;
            padding: 6px;
            font-family: 'Raleway', sans-serif;
            font-weight: 700;
            font-size: 8px;
            display: flex;
            border: 1px solid #B9B9B9;
            border-right: none;
            border-top: none;
        }
        
        .content-row p {
            font-size: 8px;
            margin: 0;
        }
        
        .table-right {
            display: flex;
            flex-direction: column;
            gap: 6px;
            width: 400px;
        }
        
        .insurance-header {
            display: flex;
            flex-direction: column;
            width: 100%;
        }
        
        .company-logo {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 6px;
            border: 1px solid #B9B9B9;
            border-top-right-radius: 8px;
        }
        
        .company-name {
            border: 1px solid #B9B9B9;
            border-top: none;
            padding: 6px;
            font-weight: 400;
            font-size: 14px;
            text-align: center;
        }
        
        .company-name p {
            font-size: 14px;
            margin: 0;
        }
        
        .plan-name {
            border: 1px solid #B9B9B9;
            border-top: none;
            padding: 6px;
            font-weight: 400;
            font-size: 14px;
            text-align: center;
        }
        
        .plan-name p {
            font-size: 14px;
            margin: 0;
        }
        
        .price-row {
            border: 1px solid #B9B9B9;
            border-top: none;
            padding: 4px 10px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .price-content {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 4px;
        }
        
        .currency {
            font-size: 16px;
        }
        
        .amount {
            font-size: 20px;
        }
        
        .buy-button {
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #FE7333;
            color: white;
            border-radius: 5.6px;
            font-weight: 500;
            font-size: 12px;
            box-shadow: 0px 1.5px 3.1px -1.5px rgba(0, 0, 0, 0.1), 0px 3.1px 4.6px -0.8px rgba(0, 0, 0, 0.1);
            width: 100%;
            height: 40px;
            margin: 0 auto;
        }
        
        .buy-button p {
            font-size: 14px;
            margin: 0;
            text-align: center;
            font-weight: 500;
        }
        
        .detail-section {
            background-color: #1D83BC;
            padding: 4px 10px;
            border: 1px solid #B9B9B9;
            border-left: none;
            border-top-right-radius: 8px;
        }
        
        .detail-row {
            border: 1px solid #B9B9B9;
            border-left: none;
            border-top: none;
            padding: 6px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .detail-row:last-of-type {
            border-bottom-right-radius: 8px;
        }
        
        .detail-content {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            font-size: 8px;
        }
        
        .detail-content p {
            font-size: 8px;
            margin: 0;
        }
        
        .view-quotes {
            background-color: #1D83BC;
            color: white;
            border-radius: 10px;
            font-weight: 500;
            text-align: center;
            font-size: 14px;
            height: 40px;
            width: 200px;
            font-family: 'Prompt', sans-serif;
            box-shadow: 0px 1.1px 2.2px -1.1px rgba(0, 0, 0, 0.1), 0px 2.2px 3.3px -0.6px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }
        
        .view-quotes a {
            margin: 0;
            text-decoration: none;
            color:white;
            line-height: 40px;
        }
        
        .disclaimer {
            font-size: 14px;
            line-height: 1.51;
            text-align: left;
            font-family: 'Prompt', sans-serif;
            padding:5px 20px 0px 20px;
            
        }

        /* footer section */
         .footer{
            -webkit-print-color-adjust: exact;
            background: #1F84BD !important;
            color: white;
            padding: 2px 8px 15px;
            width: 100%;
            height: 255px;
            min-height: 190px;
            box-sizing: border-box;
            position: fixed;
            bottom: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            gap: 4px;

        }
        
        .trademark {
            font-family: 'Prompt', sans-serif;
            font-weight: 600;
            font-size: 14px;
            line-height: 1.2;
            text-align: center;
            width: 100%;
        }
        
        .footer-content {
            display: flex;
            gap: 5px;
            width: 100%;
        }
        
        .certifications {
            width: 36%;
            background: #1F84BD;
            border: 2px solid #D3A240;
            border-radius: 20px;
            padding: 10px;
            height: 140px;
            margin-bottom: ;
        }
        
        .cert-details {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            width: 100%;
            font-size: 10px;
            line-height: 1.1;
            text-align: left;
        }
        
        .cert-item, .cert-item-alt {
            font-family: 'Prompt', sans-serif;
            font-weight: 400;
            font-size: 10px;
            line-height: 1.2;
            margin: 1px 0;
            text-align: left;
            padding-left: 0;
        }
        
        .address {
            width: 28%;
            background-color: #1D83BC;
            border: 2px solid #CF9E3C;
            border-radius: 20px;
            padding: 10px;
            display: flex;
            font-weight: 400;
            font-size: 18px;
            line-height: 1.5;
            text-align: left;
            font-family: 'Prompt', sans-serif;
            height: 130px;
        }

        .address-text{
            width:80%;
            display: flex;
            font-family: 'Prompt', sans-serif;
        }

        .address-icon{
            width:20%;
            display: flex;
        }

        .address-icon img{
            width:50%;
            height:auto;
            margin-top:50px; 
            margin-left: 20px; 
        }
        
        .address p {
            text-align: left;
            margin: 0;
            padding: 0;
        }
        
        .advisor {
            width: 32%;
            background-color: #1D83BC;
            border: 2px solid #CE9D3B;
            border-radius: 20px;
            padding: 0px 8px;
            height: 150px;
        }
        
        .advisor-title {
            font-family: 'Prompt', sans-serif;
            font-weight: 400;
            font-size: 16px;
            line-height: 1.2;
            margin-bottom: 5px;
            text-align: left;
        }
        
        .advisor-details {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .advisor-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: #7DBCD8;
            border: 2px solid white;
            flex-shrink: 0;
        }
        
        .advisor-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .advisor-name, .advisor-email, .advisor-direct {
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            font-size: 10px;
            line-height: 1.5;
            margin: 0;
            c
        }

        .advisor-email{
            font-family: 'Prompt', sans-serif;
            font-weight: 500;
            font-size: 10px;
            line-height: 1.5;
            margin: 0;
            color:white;
            text-decoration: none;
        }
        
        .mobile-container {
            display: flex;
            align-items: center;
            width: 100%;
            gap: 5px;
        }
        
        .mobile-label {
            font-family: 'Raleway', sans-serif;
            font-weight: 700;
            font-size: 10px;
            line-height: 1.2;
            margin: 0;
        }
        
        .mobile-number {
            font-family: 'Prompt', sans-serif;
            font-weight: 400;
            font-size: 10px;
            line-height: 1.2;
            display: flex;
            align-items: center;
            gap: 5px;
            margin: 0;
        }

        .container{
            width: 100%;
        }

        .table{
            border:1px solid rgba(51, 51, 51, 0.2);
        }

        table tr td{
            border:1px solid rgba(51, 51, 51, 0.2);
            font-size: 14px;
            font-weight: 700;
            /* width: 25%; */
            font-family: 'Raleway', sans-serif;
            padding:10px; 
            text-align: center;
        }
        
        /* Table center styling */
        table {
            width: 90%;
            margin: 0 auto;
            border-collapse: collapse;
            border-radius: 5px;
            table-layout: fixed;
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
            margin: 0px;
        }
        
        .full-width-border {
            border-top: 1px solid #B9B9B9;
            width: 100%;
            margin: 0;
            padding: 0;
            display: block;
        }
        .detail-table{
            margin-top: 10px;
            width: 90%;
            table-layout: fixed;
        }
        
        .detail-table tr td:first-child {
            background-color: rgba(0, 162, 255, 0.32);
            color: black;
            font-family: 'Raleway', sans-serif;
            font-weight: 700;
            width: 25% !important;
        }

        
        .hero-image {
            width: 100%;
            height: 1047px;
        }
        
        .hero-image img {
            width: 100%;
            height: 1047px;
        }
        
        /* Clear the page breaks from the table section */
        .content-page {
            page-break-before: always;
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
        ["code" => "heading", "title" => "BENEFITS"],
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
        ["code" => "spacer"],
        ["code" => "discountPremium", "title" => "Price", "type" => "info",  "heading_class" => "text-heading", "row_class" => 'row-spacing'],
        ["code" => "spacer"],
        ["code" => "vat", "title" => "VAT Amount", "type" => "info",  "heading_class" => "text-heading", "row_class" => 'row-spacing'],
        ["code" => "spacer"],
        ["code" => "total", "title" => "Payable Amount", "type" => "info",  "heading_class" => "text-heading", "row_class" => 'row-spacing'],
        ["code" => "spacer"],
        ["type" => "buy", "heading_class" => "no-border"],
        ["code" => "spacer"],
        ["code" => "excess", "title" => "Excess", "type" => "info",  "heading_class" => "text-heading", "row_class" => 'row-spacing'],
        ["code" => "spacer"],
        ["code" => "ancillaryExcess", "title" => "Ancillary Excess", "type" => "info",  "heading_class" => "text-heading", "row_class" => 'row-spacing'],
    ];

@endphp

        <!-- first page with first banner image -->
       

        <!-- plans page -->
        <div class="page"  style="page-break-after: always !important;">
            <div class="header">
                <div class="header-content">
                    <div class="header-details">
                        <div class="header-item">
                            <h1 class="header-title">Car Insurance comparison Table </h1>
                        </div>
                        <div class="header-item">
                            <p class="header-text">Customer name: <strong>{{ $quote->first_name }} {{ $quote->last_name }}</strong> </p>
                        </div>
                        <div class="header-item">
                            <p class="header-text">Car make/model: <strong>{{ @$quote->carMake->text }} {{ @$quote->carModel->text }}</strong> </p>
                        </div>
                        <div class="header-item">
                            <p class="header-text">Year: <strong>{{ @$quote->year_of_manufacture }}</strong></p>
                        </div>
                    </div>
                    <div class="quote-ref">
                        <p>Quote reference number: {{ $quote->uuid }}</p>
                    </div>
                </div>
            </div> 
            <!-- header detail end -->

            <!-- comparison table -->
            <div style="margin: 30px auto;">
                @if(count($planIds) > 0)
                @php
                    // Limit to maximum 5 plans
                    $displayPlans = array_slice($planIds, 0, 5);
                    $planCount = count($displayPlans);
                    
                    $firstColWidth = 25;
                    $planColWidth = (90 - $firstColWidth) / $planCount;
                @endphp
                <!-- First Table -->
                <table  cellspacing="0" cellpadding="0" style="width: 90%; table-layout: fixed; margin-bottom: 20px; border-collapse: collapse;">
                    <colgroup>
                        <col style="width: {{ $firstColWidth }}%;">
                        @foreach($displayPlans as $planId)
                        <col style="width: {{ $planColWidth }}%;">
                        @endforeach
                    </colgroup>
                    <tr style="height: 80px;">
                        <td style="text-align: left; padding: 6px;">Insurance company</td>
                        @foreach($displayPlans as $planId)
                        <td style="padding:0px;">
                            <table class="nested">
                                <tr>
                                    <td style="width: 100%; vertical-align: middle; text-align: center;">
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
                                            style="max-width: 50px; height: auto; margin: 0 auto 5px auto; display: block;" />
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="border:none; border-top:1px solid rgba(51, 51, 51, 0.2); width: 100%; padding: 0; margin: 0; font-size: 0; line-height: 0;">
                                        {{-- <hr class="full-width-border"> --}}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width: 100%; vertical-align: middle; text-align: center;">
                                        <div style="margin-top: 5px;font-size:12px;">{{ $plans[$planId]->providerName }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        @endforeach
                    </tr>
                    <tr>
                        <td style="text-align: left; padding: 6px;">
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
                        <td style="text-align: left; padding: 6px;">
                            Price
                        </td>
                        @foreach($displayPlans as $planId)
                        <td style="padding: 6px; text-align: center;">
                            {{ formatAmount($plans[$planId]->discountPremium) }}
                        </td>
                        @endforeach
                    </tr>
                    <tr>
                        <td style="text-align: left; padding: 6px;">
                            VAT
                        </td>
                        @foreach($displayPlans as $planId)
                        <td style="padding: 6px; text-align: center;">
                            {{ formatAmount($plans[$planId]->vat) }}
                        </td>
                        @endforeach
                    </tr>
                    <tr>
                        <td style="text-align: left; padding: 6px;">
                            Total price with (VAT)
                        </td>
                        @foreach($displayPlans as $planId)
                        <td style="padding: 6px; text-align: center;">
                            <div class="buy-button">
                                <p>BUY NOW<br>{{ formatAmount($plans[$planId]->total) }}</p>
                            </div>
                        </td>
                        @endforeach
                    </tr>
                </table>

                <!-- Second Table -->
                <table class="detail-table" cellspacing="0" cellpadding="0" style="width: 90%; table-layout: fixed; margin-bottom: 20px; border-collapse: collapse;">
                    <colgroup>
                        <col style="width: {{ $firstColWidth }}%;">
                        @foreach($displayPlans as $planId)
                        <col style="width: {{ $planColWidth }}%;">
                        @endforeach
                    </colgroup>
                    <tr>
                        <td colspan="{{ count($displayPlans) + 1 }}" style="background-color: #1D83BC;color:white;font-size: 24px;font-weight: 700;padding: 5px;width: 100% !important; text-align: left;">
                            Vehicle details
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: left; padding: 6px;">
                            Excess (Deductible)
                        </td>
                        @foreach($displayPlans as $planId)
                        <td style="padding: 6px; text-align: center;">
                            {{ formatAmount($plans[$planId]->excess) }}
                        </td>
                        @endforeach
                    </tr>
                    <tr>
                        <td style="text-align: left; padding: 6px;">
                            Ancillary excess
                        </td>
                        @foreach($displayPlans as $planId)
                        <td style="padding: 6px; text-align: center;">
                            {{ $plans[$planId]->ancillaryExcess ? ($plans[$planId]->ancillaryExcess . '%') : 'TBA' }}
                        </td>
                        @endforeach
                    </tr>
                    <tr>
                        <td style="text-align: left; padding: 6px;">
                            Vehicle Value
                        </td>
                        @foreach($displayPlans as $planId)
                        <td style="padding: 6px; text-align: center;">
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
                <table class="detail-table" cellspacing="0" cellpadding="0" style="width: 90%; table-layout: fixed; border-collapse: collapse;">
                    <colgroup>
                        <col style="width: {{ $firstColWidth }}%;">
                        @foreach($displayPlans as $planId)
                        <col style="width: {{ $planColWidth }}%;">
                        @endforeach
                    </colgroup>
                    <tr>
                        <td colspan="{{ count($displayPlans) + 1 }}" style="background-color: #1D83BC;color:white;font-size: 24px;font-weight: 700;padding: 5px;width: 100% !important; text-align: left;">
                        Benefits
                        </td>
                    </tr>
                    
                    @foreach($features as $feature)
                        @if(@$feature['code'] == 'heading' || @$feature['code'] == 'spacer')
                            @continue
                        @endif
                        
                        @if(isset($feature['title']))
                        <tr>
                            <td style="text-align: left; padding: 6px;">{{ $feature['title'] }}</td>
                            @foreach($displayPlans as $planId)
                            <td style="padding: 6px; text-align: center;">
                                @if($feature['type'] == 'info')
                                    @if($feature['code'] == 'ancillaryExcess')
                                        {{ $plans[$planId]->{$feature['code']} ? ($plans[$planId]->{$feature['code']} . '%') : 'TBA' }}
                                    @else
                                        {{ $plans[$planId]->{$feature['code']} ? formatAmount($plans[$planId]->{$feature['code']}) : 'TBA' }}
                                    @endif
                                @elseif($feature['type'] == 'prop')
                                    {{ $plans[$planId]->{$feature['code']} }}
                                @elseif($feature['type'] == 'buy')
                                    @if($plans[$planId]->discountPremium)
                                        <a target="_blank" class="btn-buy" href="{{($websitURL . '/car-insurance/quote/' . $quote->uuid .  '/payment/?providerCode=' . $plans[$planId]->providerCode . '&planId=' . $planId)}}" >Buy Now</a>
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
                <div style="width: 100%; text-align: center; margin-top: 20px;">
                    <a href="{{ $websitURL . '/car-insurance/quote/' . $quote->uuid }}" style="display: block; margin: 0 auto; width: 200px; text-align: center; text-decoration: none; background: #1D83BC; color: white; padding: 10px; border-radius: 5px;">
                        View all quotes
                    </a>
                </div>

                <!-- disclaimer -->
                <div class="disclaimer">
                    <p><strong>Disclaimer:</strong> This is a comparison table for illustrative purposes only. The prices and benefits are subject to change without prior notice. Please refer to the official terms and conditions of the insurance provider for the most accurate and current information.</p>
                </div>

            </div>
        </div>
        <pagebreak />   

        
</body>
</html>