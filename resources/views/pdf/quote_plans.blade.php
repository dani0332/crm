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
        table {
            text-indent: 0;
            border-color: #bfbfbf;
            max-width: 960px;
            margin: 30px auto;
            border-spacing: 0;
        }
        .header {
            background: #1d83bc;
            color: #ffffff;
            font-size: 19px;
            text-align: center;
            padding: 10px 0;
            width: 100%;
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
            font-size: 12px;
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
            padding: 16px;
        }
        .quote-info {
            vertical-align: top;
        }
        .quote-info div {
            margin-top: -1px;
            border: 1px solid #bfbfbf;
            background: #EFF6FF;
            font-size: 14px;
            text-align: left;
            padding: 8px;
            max-width: 90%;
            font-weight: normal;
        }
        .info {
            margin: 0 0 20px 0;
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
        .text-summary {
            color: #1d83bc;
        }
        .no-border {border: none;}
    </style>
</head>

<body>
<div class="font">
    <div class="header">
        <h2>Your Tailor Made <br />Car Insurance Comparison Table</h2>
    </div>

    @php
        $websitURL = Config::get('constants.AFIA_WEBSITE_DOMAIN');

        $plans = [];
        foreach ($quotePlans->quotes->plans as $quotePlan)
        {
            if (! isset($quotePlan->id) || ! in_array($quotePlan->id, $planIds)) {
                continue;
            }

            $quotePlan->exclusion = json_decode(collect($quotePlan->benefits->exclusion)->keyBy('code')->toJson());
            $quotePlan->inclusion = json_decode(collect($quotePlan->benefits->inclusion)->keyBy('code')->toJson());
            $quotePlan->feature = json_decode(collect($quotePlan->benefits->feature)->keyBy('code')->toJson());
            $quotePlan->roadSideAssistance = json_decode(collect($quotePlan->benefits->roadSideAssistance)->keyBy('code')->toJson());
            $quotePlan->addons = json_decode(collect($quotePlan->addons)->keyBy('code')->toJson());

            foreach ($quotePlan->addons as &$addon) {
                $addon->value = $addon->carAddonOption[0]->value;
            }

            $quotePlan->total = $quotePlan->discountPremium + $quotePlan->vat;
            $plans[$quotePlan->id] = $quotePlan;
        }

        $features = [
            ["code" => "heading", "title" => "BENEFITS"],
            ["code" => "damage", "title" => "Loss or Damage to the Insured Vehicle", "type" => ["inclusion", "exclusion"]],
            ["code" => "liability", "title" => "Third Party Property Liability", "type" => "feature"],
            ["code" => "bloodMoney", "title" => "Blood Money", "type" => ["inclusion", "exclusion"]],
            ["code" => "fireAndTheft", "title" => "Fire and Theft Cover", "type" => ["inclusion", "exclusion"]],
            ["code" => "stormAndFlood", "title" => "Storm, Flood", "type" => ["inclusion", "exclusion"]],
            ["code" => "perils", "title" => "Natural Perils", "type" => ["inclusion", "exclusion"]],
            ["code" => "riotAndStrike", "title" => "Natural Perils Riot and Strike", "type" => ["inclusion", "exclusion"]],
            ["code" => "repairs", "title" => "Repairs", "type" => ["inclusion", "exclusion"]],
            ["code" => "emergencyMedicalExpenses", "title" => "Emergency Medical Expenses", "type" => ["inclusion", "exclusion"]],
            ["code" => "personalBelongings", "title" => "Personal belongings", "type" => ["inclusion", "exclusion"]],
            ["code" => "omanCover", "title" => "Oman Cover (Orange card not Included)", "type" => "addons"],
            ["code" => "offRoadCover", "title" => "Off-road Cover", "type" => "roadSideAssistance"],
            ["code" => "guaranteedRepairs", "title" => "Guaranteed Repairs", "type" => ["inclusion", "exclusion"]],
            ["code" => "breakdownRecovery", "title" => "24 Hour Accident and Breakdown Recovery", "type" => ["inclusion", "exclusion"]],//** inclusion or ROAD_SIDE_ASSISTANCE */
            ["code" => "ambulanceCover", "title" => "Ambulance Cover", "type" => ["inclusion", "exclusion"]],
            ["code" => "excessForWindscreenDamage", "title" => "Excess for Windscreen Damage", "type" => ["inclusion", "exclusion"]],
            ["code" => "heading", "title" => "Optional Covers", "type" => ""],
            ["code" => "driverCover", "title" => "Driver Cover", "type" => "addons"],
            ["code" => "passengerCover", "title" => "Passengers Cover", "type" => "addons"],
            ["code" => "carHire", "title" => "Hire car Benefit", "type" => ["inclusion", "exclusion"]],
            ["code" => "spacer"],
            ["code" => "discountPremium", "title" => "Premium", "type" => "info",  "heading_class" => "text-heading"],
            ["code" => "vat", "title" => "VAT Amount", "type" => "info",  "heading_class" => "text-heading"],
            ["code" => "total", "title" => "Total", "type" => "info",  "heading_class" => "text-heading"],
            ["code" => "excess", "title" => "Excess", "type" => "info",  "heading_class" => "text-heading"]
        ];

    @endphp

    <div class="container ">
        <table class="table-fixed text-center">
            <thead>
            <tr>

                <th class="quote-info">
                    <div>
                        <p class="">Comparison of Car Insurance Quote For: {{ auth()->user()->name }}</p>
                    </div>
                </th>

                @foreach($planIds as $planId)
                    <th class="provider">
                        <div class="rounded-full">
                            <p class="relative top-[40%] m-auto text-xs">
                                <img class="provider-logo" alt="" src="{{public_path('images/insurance_providers/' . strtolower($plans[$planId]->providerCode) . '.png')}}" />
                            </p>
                        </div>
                    </th>
                @endforeach

            </tr>
            </thead>
            <tbody>

            <tr>
                <td class="no-border" >&nbsp;</td>
                @foreach($planIds as $planId)
                    <td>
                        <p class="text-center">
                            {{ $plans[$planId]->providerName }}
                        </p>
                    </td>
                @endforeach
            </tr>

            <tr>
                <td class="no-border" >&nbsp;</td>
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
                <td class="no-border">&nbsp;</td>
                @foreach($planIds as $planId)
                    <td>
                        <p class="text-center">
                            <a target="_blank" class="btn-buy" href="{{($websitURL . '/car-insurance/quote/' . $quoteId .  '/payment/?providerCode=' . $plans[$planId]->providerCode . '&planId=' . $planId)}}" >Buy Now</a>
                        </p>
                    </td>
                @endforeach
            </tr>

            {{-- vehicle detail / exact value --}}
            <tr class="bg-light-blue">
                <td><p>EXACT VEHICLE AND VALUE (INSURER SPECIFIC)</p></td>
                @foreach($planIds as $planId)
                    <td><p class="text-center">{{$quote->carMake->text . ' ' . $quote->carModel->text . ' ' . $quote->year_of_manufacture . ' ' . formatAmount($quote->car_value) }}</p></td>
                @endforeach
            </tr>

            @foreach($features as $feature)

                {{-- heading row --}}
                @if($feature['code'] == 'heading')
                    <tr>
                        <td colspan="1">
                            <p class="text-left text-heading">{{$feature['title']}}</p>
                        </td>
                        <td  colspan="{{ sizeof($planIds) }}" class=""></td>
                    </tr>
                    @php continue; @endphp
                @endif

                {{-- spacer row --}}
                @if($feature['code'] == 'spacer')
                    <tr>
                        <td colspan="{{ sizeof($planIds) + 1 }}"><div class="spacer"></div></td>
                    </tr>
                    @php continue; @endphp
                @endif

                {{-- feature rows --}}
                <tr class="{{($feature['row_class'] ?? "")}}">
                    <td class="{{@$feature['heading_class']}}"><p class="text-left">{{$feature['title']}}</p></td>
                    @foreach($planIds as $planId)
                        <td class="{{@$feature['col_class']}}">
                            <p>
                                @if($feature['type'] == 'info')

                                    {!!  $plans[$planId]->{$feature['code']} ? formatAmount($plans[$planId]->{$feature['code']})  : formatAmount(0) !!}

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
            </tbody>
        </table>

        <div class="info">
            <h5>MATERIAL INFORMATION DECLARATION</h5>
            <p>All quotes we provide are indicative and based on the information that you, as a proposer, have provided to us. It is important that this information accurately reflects your
                position and needs and before you purchase your policy, you are advised to check all the details relevant to the risk to be insured have been supplied. Failure to provide all material information
                may result in the insurer declining future claims on the ground of misrepresentation.</p>
        </div>
    </div>
</div>

</body>

</html>
