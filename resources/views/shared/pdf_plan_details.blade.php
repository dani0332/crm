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
            background: #ddfdfc;
            color: #333393;
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
        .green-box {
            border: 1px solid #bfbfbf;
            background: #d7fbd0;
            padding: 8px;
            color: #333393;
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
            background: #d7fbd0;
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
            background: #333393;
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
           font-size: 18px;
            font-weight: bold;
            color: #333393;
        }
        .bg-blue
        {
            background-color: #a8d4f7;
        }
        .text-blue {
            color: #1d68a3;
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
    </style>
</head>

<body>
<div class="font">
    <div class="header">
        <h2>Your Tailor Made <br />Car Insurance Comparison Table</h2>
    </div>

    @php
        $websitURL = Config::get('constants.AFIA_WEBSITE_DOMAIN');

        $features = [
            ["code" => "heading", "title" => "BENEFITS"],
            ["code" => "damage", "title" => "Loss or Damage to the Insured Vehicle", "object" => "exclusion"],
            ["code" => "liability", "title" => "Third Party Property Liability", "object" => "feature"],
            ["code" => "bloodMoney", "title" => "Blood Money", "object" => "inclusion"],
            ["code" => "fireAndTheft", "title" => "Fire and Theft Cover", "object" => "exclusion"],
            ["code" => "stormAndFlood", "title" => "Storm, Flood", "object" => "exclusion"],
            ["code" => "perils", "title" => "Natural Perils", "object" => "exclusion"],
            ["code" => "riotAndStrike", "title" => "Natural Perils Riot and Strike", "object" => "exclusion"],
            ["code" => "repairs", "title" => "Repairs", "object" => "exclusion"],
            ["code" => "emergencyMedicalExpenses", "title" => "Emergency Medical Expenses", "object" => "exclusion"],
            ["code" => "personalBelongings", "title" => "Personal belongings", "object" => "exclusion"],
            ["code" => "omanCover", "title" => "Oman Cover (Orange card not Included)", "object" => "addons"],
            ["code" => "offRoadCover", "title" => "Off-road Cover", "object" => "roadSideAssistance"],
            ["code" => "guaranteedRepairs", "title" => "Guaranteed Repairs", "object" => "exclusion"],
            ["code" => "breakdownRecovery", "title" => "24 Hour Accident and Breakdown Recovery", "object" => "inclusion"],
            ["code" => "ambulanceCover", "title" => "Ambulance Cover", "object" => "inclusion"],
            ["code" => "excessForWindscreenDamage", "title" => "Excess for Windscreen Damage", "object" => "exclusion"],
            ["code" => "heading", "title" => "Optional Covers", "object" => ""],
            ["code" => "driverCover", "title" => "Driver Cover", "object" => "addons"],
            ["code" => "passengerCover", "title" => "Passengers Cover", "object" => "addons"],
            ["code" => "carHire", "title" => "Hire car Benefit", "object" => "exclusion"],
            ["code" => "spacer"],
            ["code" => "discountPremium", "title" => "Premium", "object" => "info", "col_class" => "bg-blue"],
            ["code" => "vat", "title" => "VAT Amount", "object" => "info", "col_class" => "bg-blue"],
            ["code" => "total", "title" => "Total", "object" => "info", "col_class" => "bg-blue"],
            ["code" => "excess", "title" => "Excess", "object" => "info", "col_class" => "bg-blue"]
        ];

    @endphp

    <div class="container ">
        <table class="table-fixed text-center">
            <thead>
            <tr>

                <th class="quote-info">
                    <div>
                        <p>Comparison of Car Insurance Quote For: {{ auth()->user()->name }}</p>
                    </div>
                </th>

                @foreach($planIds as $planId)
                    <th class="provider">
                        <div class="mx-auto my-2 h-20 w-20 rounded-full bg-blue-50">
                            <p class="relative top-[40%] m-auto text-xs">
                                <img class="provider-logo" alt="" src="{{public_path('images/insurance_providers/' . strtolower($plans[$planId]->providerCode) . '.png')}}" />
                            </p>
                        </div>
                        <div class="mb-2">
                        {{ $plans[$planId]->providerName }}
                        </div>
                    </th>
                @endforeach
            </tr>
            </thead>
            <tbody>

            {{-- buy now row --}}
            <tr>
                <td>&nbsp;</td>
                @foreach($planIds as $planId)
                    <td>
                        <p class="text-center">
                            <a target="_blank" class="btn-buy" href="{{($websitURL . '/car-insurance/quote/' . $quoteId .  '/payment/?providerCode=' . $plans[$planId]->providerCode . '&planId=' . $planId)}}" >Buy Now</a>
                        </p>
                    </td>
                @endforeach
            </tr>

            {{-- vehicle detail / exact value --}}
            <tr class="green-box">
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
                            <p class="text-left text-blue">{{$feature['title']}}</p>
                        </td>
                        <td  colspan="{{ sizeof($planIds) }}" class="bg-blue"></td>
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
                    <td ><p class="text-left">{{$feature['title']}}</p></td>
                    @foreach($planIds as $planId)
                        <td class="{{@$feature['col_class']}}">
                            <p>
                                @if($feature['object'] == 'info')
                                    {!!  $plans[$planId]->{$feature['code']} ? formatAmount($plans[$planId]->{$feature['code']})  : formatAmount(0) !!}
                                @else
                                    {!!  $plans[$planId]->{$feature['object']}->{$feature['code']}->value ?? '&cross;' !!}
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
