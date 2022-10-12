<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Plans Comparison PDF</title>

    <style>
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
        table.tbl-dec tr td {border: none;}
        table {
            min-width: 1140px;
            width: 1140px;
            text-indent: 0;
            border-color: #bfbfbf;
            max-width: 1140px;
            margin: 30px auto;
            border-spacing: 0;
        }
        .header {
            background: #1d83bc;
            color: #ffffff;
            font-size: 19px;
            text-align: center;
            padding: 10px 10px;
            width: 100%;
            height: 70px;
            max-height: 70px;
        }
        .header .logo {
            float: left;
            background-color: white;
            border-radius: 5px;
            padding: 5px 10px 5px 0px;
        }
        .header h2 {
            float: right;
            text-align: right;
            padding-right: 18px;
        }

        tbody > tr > td {
            border: 1px solid #bfbfbf;
        }
        td > p {
            padding: 4px;
            font-size: 14px;
            text-align: center;
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
            background: #EFF6FF;
            padding: 8px;
            color: #252525;
        }
        .section {
            color: #333393;
            text-align: left;
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
        }
        .spacer {
            padding: 3px;
        }
        .quote-info {
            text-align: right;padding-right: 0;vertical-align: bottom;
        }
        div.quote-info  {
            margin-top: -1px;
            border: 1px solid #bfbfbf;
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
            padding: 12px 25px;
            margin-top: 8px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            font-weight: bold;
            border-radius: 5px;
        }
        .btn-buy
        {
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
            margin-bottom:0px;
            margin-top:20px;
        }
        .container
        {
            padding: 0px 50px;
        }
        .no-border {border: none;}
        footer {
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
            padding: 16px 12px;
            margin: 0;
            width: 100%;
            border: none;
        }
        table.tbl-footer tr td, table.tbl-footer tr td a {
            color: #ffffff;
            border: none;
            font-size: 17px;
        }
        .text-left {text-align: left;}
        .text-right {text-align: right;}
        .full-page-image {
            width: 100%;
        }
        .text-center {text-align: center;}
    </style>
</head>

<body>

<img src="{{public_path('images/quote_plans_pages/p1.jpg')}}" class="full-page-image" />

<footer>
    <table class="tbl-footer">
        <tr>
            <td class="text-left"><h4>AFIA Insurance Brokerage Services LLC</h4></td>
            <td class="text-right">&nbsp;</td>
        </tr>
        <tr>
            <td class="text-left">27th Floor, Control Tower, Motor City,</td>
            <td class="text-right">Tel: <a href="tel:+97144215819">+971 4 421 5819</a> </td>
        </tr>
        <tr>
            <td class="text-left">Dubai, United Arab Emirates, P.O Box 26423</td>
            <td class="text-right">Fax: +971 4 421 5984</td>
        </tr>
        <tr>
            <td class="text-left">Ministry of Economy and Commerce - Registration number 85</td>
            <td class="text-right">Email: <a href="mailto:hello@afia.ae">hello@afia.ae</a> </td>
        </tr>
        <tr>
            <td class="text-left"><a href="https://afia.ae/">www.afia.ae</a>, <a href="https://insurancemarket.ae/">www.insurancemarket.ae</a> </td>
            <td class="text-right">
                @if(isset($quote->advisor->email))
                    Email: <a href="mailto:{{$quote->advisor->email}}">{{$quote->advisor->email}}</a>
                @endif
            </td>
        </tr>
    </table>
</footer>

<div class="font">

    <div class="header">
        <div class="logo">
            <img class="im-logo" src="{{public_path('images/im_logo.png')}}" />
        </div>
        <h2>Your Tailor Made <br />Car Insurance Comparison Table</h2>
    </div>

    @php

        $websitURL = Config::get('constants.AFIA_WEBSITE_DOMAIN');

        $plans = [];
        foreach ($quotePlans->quotes->plans as $quotePlan)
        {
            //dd($quotePlan);
            if (! isset($quotePlan->id) || ! in_array($quotePlan->id, $planIds)) {
                continue;
            }

            $quotePlan->exclusion = json_decode(collect($quotePlan->benefits->exclusion)->keyBy('code')->toJson());
            $quotePlan->inclusion = json_decode(collect($quotePlan->benefits->inclusion)->keyBy('code')->toJson());
            $quotePlan->feature = json_decode(collect($quotePlan->benefits->feature)->keyBy('code')->toJson());
            $quotePlan->roadSideAssistance = json_decode(collect($quotePlan->benefits->roadSideAssistance)->keyBy('code')->toJson());
            $quotePlan->addons = json_decode(collect($quotePlan->addons)->keyBy('code')->toJson());

            foreach ($quotePlan->addons as &$addon) {

                //set default value to excluded
                $addon->value = "&cross;";
                if(sizeof($addon->carAddonOption)) {
                    //replace exclude with selected value if found
                    foreach ($addon->carAddonOption as $index => $carAddonOption) {

                        if($carAddonOption->isSelected) {

                            $addon->value = $carAddonOption->value;
                            if($carAddonOption->price) {
                               $addon->value .= ' (' . formatAmount($carAddonOption->price, 0) . ')';
                            }

                            break;//only one value will be selected
                        }
                    }
                }

            }

            $quotePlan->repairTypeInfo = ($quotePlan->repairType == \App\Enums\CarPlanType::COMP) ? \App\Enums\CarPlanType::NONAGENCY : $quotePlan->repairType;
            $quotePlan->total = $quotePlan->discountPremium + $quotePlan->vat;
            $plans[$quotePlan->id] = $quotePlan;
        }

        $features = [
            ["code" => "heading", "title" => "BENEFITS"],
            ["code" => "damage", "title" => "Loss or Damage to the Insured Vehicle", "type" => ["feature", "inclusion", "exclusion"]],
            ["code" => "liability", "title" => "Third Party Property Liability", "type" => "feature"],
            ["code" => "bloodMoney", "title" => "Blood Money", "type" => ["inclusion", "exclusion"]],
            ["code" => "fireAndTheft", "title" => "Fire and Theft Cover", "type" => ["inclusion", "exclusion"]],
            ["code" => "stormAndFlood", "title" => "Storm, Flood", "type" => ["inclusion", "exclusion"]],
            ["code" => "riotAndStrike", "title" => "Natural Perils Riot and Strike", "type" => ["inclusion", "exclusion"]],
            ["code" => "repairTypeInfo", "title" => "Repairs", "type" => "prop"],
            ["code" => "emergencyMedicalExpenses", "title" => "Emergency Medical Expenses", "type" => ["inclusion", "exclusion"]],
            ["code" => "personalBelongings", "title" => "Personal belongings", "type" => ["inclusion", "exclusion"]],
            ["code" => "omanCover", "title" => "Oman Cover (Orange card not Included)", "type" => ["inclusion", "exclusion"]],//also exists in addons, discussed with mujeeb to show from include/exclusion
            ["code" => "offRoadCover", "title" => "Off-road Cover", "type" => "roadSideAssistance"],
            ["code" => "guaranteedRepairs", "title" => "Guaranteed Repairs", "type" => ["inclusion", "exclusion"]],
            ["code" => "breakdownRecovery", "title" => "24 Hour Accident and Breakdown Recovery", "type" => "roadSideAssistance"],//** exists in ["inclusion", "exclusion"] as well, discussed with mujeeb to show from roadSideAssistance
            ["code" => "ambulanceCover", "title" => "Ambulance Cover", "type" => ["inclusion", "exclusion"]],
            ["code" => "excessForWindscreenDamage", "title" => "Excess for Windscreen Damage", "type" => ["inclusion", "exclusion"]],
            ["code" => "heading", "title" => "Optional Covers", "type" => ""],
            ["code" => "driverCover", "title" => "Driver Cover", "type" => "addons"],
            ["code" => "passengerCover", "title" => "Passengers Cover", "type" => "addons"],
            ["code" => "carHire", "title" => "Hire car Benefit", "type" => "addons"],
            ["code" => "spacer"],
            ["code" => "discountPremium", "title" => "Premium", "type" => "info",  "heading_class" => "text-heading", "row_class" => 'row-spacing'],
            ["code" => "spacer"],
            ["code" => "vat", "title" => "VAT Amount", "type" => "info",  "heading_class" => "text-heading", "row_class" => 'row-spacing'],
            ["code" => "spacer"],
            ["code" => "total", "title" => "Payable Amount", "type" => "info",  "heading_class" => "text-heading", "row_class" => 'row-spacing'],
            ["code" => "spacer"],
            ["type" => "buy", "heading_class" => "no-border"],
            ["code" => "spacer"],
            ["code" => "excess", "title" => "Excess", "type" => "info",  "heading_class" => "text-heading", "row_class" => 'row-spacing']
        ];

    @endphp

    <div class="container">
        <table class="table-fixed text-center tbl-plans">
            <thead>
            <tr>

                <th class="quote-info" rowspan="3">
                    <img style="" src="{{public_path('images/alfred.png')}}" />
                </th>

                @foreach($planIds as $planId)
                    <th class="provider">
                        <div class="rounded-full">
                            <p class="relative top-[40%] m-auto text-xs">
                                @php
                                    $providerLogoImage = public_path('images/insurance_providers/' . strtolower($plans[$planId]->providerCode) . '.png');

                                    if(!file_exists($providerLogoImage)) {
                                        $providerLogoImage = public_path('images/insurance_providers/default.png');
                                    }

                                @endphp
                                <img class="provider-logo" alt="" src="{{$providerLogoImage}}" />
                            </p>
                        </div>
                    </th>
                @endforeach

            </tr>
            </thead>
            <tbody>

            <tr>

                @foreach($planIds as $planId)
                    <td>
                        <p class="text-center">
                            {{ $plans[$planId]->providerName }}
                        </p>
                    </td>
                @endforeach
            </tr>

            <tr>
                @foreach($planIds as $planId)
                    <td>
                        <p class="text-center">
                            {{ $plans[$planId]->name }}
                        </p>
                    </td>
                @endforeach
            </tr>

            {{-- buy now row --}}
            <tr>
                <td class="no-border" >
                    <div class="quote-info">
                        <p class="">Car insurance comparison for: <b>{{ $quote->customer->first_name  }} {{$quote->customer->last_name}}</b></p>
                    </div>
                </td>
                @foreach($planIds as $planId)
                    <td>
                        <p class="text-center">
                            @if($plans[$planId]->discountPremium)
                                <a target="_blank" class="btn-buy" href="{{($websitURL . '/car-insurance/quote/' . $quote->uuid .  '/payment/?providerCode=' . $plans[$planId]->providerCode . '&planId=' . $planId)}}" >Buy Now</a>
                            @else
                                N/A
                            @endif
                        </p>
                    </td>
                @endforeach
            </tr>

            {{-- vehicle detail / exact value --}}
            <tr class="bg-light-blue">
                <td><p>EXACT VEHICLE (INSURER SPECIFIC)</p></td>
                @foreach($planIds as $planId)
                    <td>

                        <p class="text-center">{!! $quote->carMake->text . ' ' . $quote->carModel->text . ' ' . $quote->year_of_manufacture  !!}</p>
                    </td>
                @endforeach
            </tr>

            <tr class="bg-light-blue">
                <td><p>VEHICLE VALUE</p></td>
                @foreach($planIds as $planId)
                    <td>
                        @php
                            $plan = $plans[$planId];
                            $carValue = formatAmount($plan->carValue, 0);
                            if($plan->repairType == \App\Enums\CarPlanType::TPL) $carValue = 'N/A';
                        @endphp

                        <p class="text-center">{!! $carValue !!}</p>
                    </td>
                @endforeach
            </tr>

            @foreach($features as $feature)

                {{-- heading row --}}
                @if(@$feature['code'] == 'heading')
                    <tr>
                        <td colspan="1">
                            <p class="text-left text-heading">{{$feature['title']}}</p>
                        </td>
                        <td  colspan="{{ sizeof($planIds) }}" class=""></td>
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
                    <td class="{{@$feature['heading_class']}}"><p class="text-left">{{@$feature['title']}}</p></td>
                    @foreach($planIds as $planId)
                        <td class="{{@$feature['col_class']}}">
                            <p>
                                @if($feature['type'] == 'info')

                                    {!!  $plans[$planId]->{$feature['code']} ? formatAmount($plans[$planId]->{$feature['code']})  : 'N/A' !!}

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
                                    @php $value = "&cross;"; @endphp
                                    @foreach($feature['type'] as $type)
                                        @if(isset($plans[$planId]->{$type}->{$feature['code']}->value))
                                            @php $value = $plans[$planId]->{$type}->{$feature['code']}->value; break; @endphp
                                        @endif
                                    @endforeach

                                    {!! ($value)  !!}

                                @else
                                    {!!  $plans[$planId]->{$feature['type']}->{$feature['code']}->value ?? '&cross;' !!}
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

        <table class="tbl-dec">
            <tbody>
                <tr>
                    <td class="text-heading text-justify"><h5>MATERIAL INFORMATION DECLARATION</h5></td>
                </tr>
                <tr>
                    <td>
                        <p class="text-left text-xs">All quotes we provide are indicative and based on the information that you, as a proposer, have provided to us. It is important that this information accurately reflects your
                            position and needs and before you purchase your policy, you are advised to check all the details relevant to the risk to be insured have been supplied. Failure to provide all material information
                            may result in the insurer declining future claims on the ground of misrepresentation.</p>
                    </td>
                </tr>
            </tbody>
        </table>

    </div>
</div>

    <img src="{{public_path('images/quote_plans_pages/p3.jpg')}}" class="full-page-image"  />
    </body>
</html>
