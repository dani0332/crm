<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Plans Comparison PDF</title>

    <style>
        @page {
            margin:0;
            padding: 0;
        }
        html {
            line-height: 1.5;
            /*margin: 0;*/
            margin:0;
            padding: 0;
        }
        body {
            /*margin: 0;*/
            line-height: 1;
            font-family: "DejaVu Sans", sans-serif;
            padding: 80px 0 120px 0;
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
            min-width: 1220px;
            width: 1220px;
            text-indent: 0;
            border-color: #bfbfbf;
            max-width: 1220px;
            margin: 7px 12px auto;
            border-spacing: 0;
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
        .text-black{color: #000000;}
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
        .alfred { text-align: right;padding-right: 0;vertical-align: bottom;}
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
        .heading-desc {
            font-size: 11px;
        }
        .provider-logo {
            width: 100px;
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
            margin: 80px 0 0 0;
            background-color: #1d83bc;
            color: black;
            text-align: center;
            position: fixed;
            bottom: 0px;
            height: 120px;
        }
        header{
            position: fixed;
            top: 0px;
            height: 120px;
            width: 100%;
            margin-bottom: 200px;

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
            font-size: 14px;
        }
        .text-left {text-align: left;}
        .text-right {text-align: right;}
        .full-page-image {
            width: 100%;
        }
        .text-center {text-align: center;}
        .badge-success {
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
        }

    </style>
</head>
<body>
<img src="{{public_path('images/quote_plans_pages/rm-p1.png')}}" class="" style="height: 100%;width: 100%;padding-top: 0;margin-top: 0;" />
@php
    $websitURL = config('constants.AFIA_WEBSITE_DOMAIN');
    $plans = [];

    foreach ($quotePlans->quote->plans as &$quotePlan)
    {
        $addonsPrice = 0;
        $addonsVat   = 0;

        if (! isset($quotePlan->id) || ! in_array($quotePlan->id, $planIds)) {
            continue;
        }

        $quotePlan->feature = json_decode(collect($quotePlan->benefits->feature)->keyBy('code')->toJson());
        //$quotePlan->inclusion = json_decode(collect($quotePlan->benefits->inclusion)->keyBy('code')->toJson());
        $quotePlan->inpatient = json_decode(collect($quotePlan->benefits->inpatient)->keyBy('code')->toJson());

        $quotePlan->outpatient = json_decode(collect($quotePlan->benefits->outpatient)->keyBy('code')->toJson());

        $quotePlan->exclusion = json_decode(collect($quotePlan->benefits->exclusion)->keyBy('code')->toJson());

        $quotePlan->coInsurance = json_decode(collect($quotePlan->benefits->coInsurance)->keyBy('code')->toJson());

        $quotePlan->regionCover = json_decode(collect($quotePlan->benefits->regionCover)->keyBy('code')->toJson());

        $quotePlan->maternityCover = json_decode(collect($quotePlan->benefits->maternityCover)->keyBy('code')->toJson());

        $quotePlan->networkList = json_decode(collect($quotePlan->benefits->networkList)->keyBy('code')->toJson());

       // $quotePlan->roadSideAssistance = json_decode(collect($quotePlan->benefits->roadSideAssistance)->keyBy('code')->toJson());
        $quotePlan->addons = (isset($addons[$quotePlan->id])) ? json_decode(json_encode($addons[$quotePlan->id])) : json_decode(collect($quotePlan->addons)->keyBy('code')->toJson());

        foreach ($quotePlan->benefits as &$benefit) {

            $benefit = (object) $benefit;
            //set default value to excluded
            $benefit->value = "Excluded";

            //set default values
            $benefit->price = 0;
            $benefit->vat = 0;

            $policyFee = (isset($providers[$quotePlan->providerId]->policy_fee)) ? $providers[$quotePlan->providerId]->policy_fee : 0;
            $quotePlan->discountPremium += $addonsPrice;
            $quotePlan->vat += $addonsVat;
            $quotePlan->total = $quotePlan->discountPremium  + $quotePlan->vat + $policyFee;
            $quotePlan->policyFee = $policyFee;
            $plans[$quotePlan->id] = $quotePlan;
        }
    }

    $planIds = collect($plans)->sortByDesc('isRenewal')->pluck('id')->toArray();

    $features = [
        ["code" => "heading", "title" => "BENEFITS"],
        ["code" => "annualLimit", "title" => "Annual Claim Limit", "type" => 'feature'],
        ["code" => "regionsCovered", "title" => "Regions Covered", "type" => 'regionCover'],
        ["code" => "billingHospitalsOutpatient", "title" => "Direct Billing Network (Outpatient)", "type" => 'networkList'],
        ["code" => "billingHospitalsInpatient", "title" => "Direct Billing Network (Inpatient)", "type" => 'networkList'],
        ["code" => "heading", "title" => "Outpatient Benefits", "description" => "*All benefits, limits and sublimits are subject to applicable excess, co‐insurance/co‐pays and prior authorization; Specific benefits varies for each insurer, please refer to table of benefits for more details"],
        ["code" => "preExistingOrChronic", "title" => "Pre‐existing & Chronic Conditions*", "type" => 'inpatient'],
        ["code" => "medicine", "title" => "Medicines*", "type" => 'outpatient'],
        ["code" => "vaccination", "title" => "Vaccinations* (as per MOH list up to 6 years old)", "type" => 'outpatient'],
        ["code" => "diagnostics", "title" => "Diagnostic*", "type" => 'outpatient'],
        ["code" => "alternativeMedicineAndTreatment", "title" => "Alternative Medicine*", "type" => ['outpatient', 'exclusion']],
        ["code" => "consultation", "title" => "Consultation*", "type" => 'outpatient'],
        ["code" => "Physiotherapy", "title" => "Physiotherapy*", "type" => 'outpatient'],
        ["code" => "dentalCover", "title" => "Dental*", "type" => ['outpatient', 'exclusion'] ],
        ["code" => "damage", "title" => "Optical*", "type" => ['outpatient', 'inclusion', 'exclusion']],//not found
        ["code" => "heading", "title" => "Inpatient Benefits", "description" => "*All benefits, limits and sublimits are subject to applicable excess co‐insurance/co‐pays and prior authorization; Specific benefits varies for each insurer, please refer to table of benefits for more details"],
        ["code" => "preExistingOrChronic", "title" => "Pre‐existing & Chronic Conditions*", "type" => 'inpatient'],
        ["code" => "medicine", "title" => "Medicines*", "type" => 'inpatient'],
        ["code" => "surgeryRecovery", "title" => "Surgery and Recovery*", "type" => 'inpatient'],
        ["code" => "diagnostics", "title" => "Diagnostics*", "type" => 'inpatient'],
        ["code" => "roomBoard", "title" => "Room and board", "type" => 'inpatient'],
        ["code" => "medicine", "title" => "Medicines*", "type" => 'inpatient'],
        ["code" => "physiotherapy", "title" => "Physiotherapy*", "type" => 'inpatient'],
        ["code" => "heading", "title" => "Co‐pay or Co‐insurance"],
        ["code" => "consultation", "title" => "Consultation*", "type" => 'coInsurance'],
        ["code" => "diagnostics", "title" => "Diagnostics*", "type" => 'coInsurance'],
        ["code" => "damage", "title" => "Inpatient*", "type" => 'coInsurance'],
        ["code" => "physiotherapy", "title" => "Physiotherapy*", "type" => 'coInsurance'],
        ["code" => "medicineCo", "title" => "Medicine*", "type" => 'coInsurance'],
        ["code" => "dental", "title" => "Dental*", "type" => 'coInsurance'],//todo: find exact later
        ["code" => "optical", "title" => "Optical*", "type" => 'coInsurance'],//todo: find exact later

        ["code" => "heading", "title" => "Maternity Cover"],
        ["code" => "outpatient", "title" => "Outpatient", "type" => 'maternityCover'],
        ["code" => "coInsurance", "title" => "Co‐insurance", "type" => 'maternityCover'],
        ["code" => "damage", "title" => "Outpatient Maternity", "type" => 'maternityCover'],
        ["code" => "medicallyNecessary", "title" => "Medically necessary C‐section", "type" => 'maternityCover'],
        ["code" => "normalDelivery", "title" => "Normal Delivery", "type" => 'maternityCover'],

        ["code" => "spacer"],
        ["code" => "discountPremium", "title" => "Premium", "type" => "info",  "heading_class" => "text-heading", "row_class" => 'row-spacing'],
        ["code" => "spacer"],
        ["code" => "policyFee", "title" => "Policy Fee", "type" => "info",  "heading_class" => "text-heading", "row_class" => 'row-spacing'],
        ["code" => "spacer"],
        ["code" => "vat", "title" => "Vat Amount", "type" => "info",  "heading_class" => "text-heading", "row_class" => 'row-spacing'],
        ["code" => "spacer"],
        ["code" => "total", "title" => "Total Indicative Premium", "type" => "info",  "heading_class" => "text-heading", "row_class" => 'row-spacing'],
//        ["code" => "spacer"],
//        ["type" => "buy", "heading_class" => "no-border"],
        //["code" => "spacer"],
        //["code" => "excess", "title" => "Excess", "type" => "info",  "heading_class" => "text-heading", "row_class" => 'row-spacing']
    ];
@endphp
<header>
    <div class="header" style="">
        <div class="logo">
            <img class="im-logo" src="{{public_path('images/im_logo.png')}}" />
        </div>
        <h3>Your Tailor Made <br />Health Insurance Comparison Table</h3>
    </div>
</header>

<footer style="">
    <table class="tbl-footer" style="">
        <tr>
            <td colspan="2" class="text-center"><h4>InsuranceMarket.ae™by AFIA Insurance Brokerage Services LLC</h4></td>
        </tr>
        <tr>
            <td class="text-left">27th Floor, Control Tower, Motor City,</td>
            <td class="text-right">Tel: <a href="tel:+800253733">800 ALFRED (800-253-733)</a> </td>
        </tr>
        <tr>
            <td class="text-left">Dubai, United Arab Emirates, P.O Box 26423</td>
            <td class="text-right"><a href="mailto:askalfred@insurancemarket.ae">askalfred@insurancemarket.ae</a> | <a href="https://insurancemarket.ae">www.insurancemarket.ae</a> </td>
        </tr>
        <tr>
            <td class="text-left">Registration No. 85 under Central Bank of UAE (UAE Insurance Authority)</td>
            <td class="text-right">
                @if(isset($quote->advisor->email))
                    Advisor Email: <a href="mailto:{{$quote->advisor->email}}">{{$quote->advisor->email}}</a>
                @endif
            </td>
        </tr>
        <tr>
            <td class="text-left">Holder of HIIP from HA, Intermediary ID No. BRK-00003</td>
            <td class="text-right">
                @if(!empty($quote->advisor->mobile_no) || !empty($quote->advisor->landline_no)) Advisor Phone: @endif
                @if(isset($quote->advisor->mobile_no)) <a href="tel:{{$quote->advisor->mobile_no}}">{{$quote->advisor->mobile_no}}</a> @endif
                @if(!empty($quote->advisor->mobile_no) && !empty($quote->advisor->landline_no)) | @endif
                @if(!empty($quote->advisor->landline_no)) <a href="tel:{{$quote->advisor->landline_no}}">{{$quote->advisor->landline_no}}</a> @endif
            </td>
        </tr>
    </table>
</footer>
<main>
    <table class="table-fixed text-center tbl-plans" style="">
        <thead>

        </thead>
        <tbody style="">

        <tr>

            <th class="alfred" rowspan="3">
                <img style="" src="{{public_path('images/alfred.png')}}"  />
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

        <tr>
            @foreach($planIds as $planId)
                <td style="padding: 0;margin: 0;">
                    <p class="text-center">
                        {{ $plans[$planId]->providerName }}
                    </p>
                </td>
            @endforeach
        </tr>

        <tr>
            @foreach($planIds as $planId)
                <td style="padding: 0;margin: 0;">
                    <p class="text-center">
                        {{ $plans[$planId]->name }}
                    </p>
                </td>
            @endforeach
        </tr>

        {{-- buy now row --}}
        <tr>
            <td class="bg-light-blue" >
                <p class="quote-info">Car insurance comparison for: <b>{{ $quote->first_name  }} {{$quote->last_name}}</b></p>
            </td>
            @foreach($planIds as $planId)
                <td>
                    <p class="text-center">
                        @if($plans[$planId]->discountPremium)
                            <a target="_blank" class="btn-buy" href="{{($websitURL . '/car-insurance/quote/' . $quote->uuid .  '/payment/?providerCode=' . $plans[$planId]->providerCode . '&planId=' . $planId)}}" >Apply Now</a>
                        @else
                            N/A
                        @endif
                    </p>
                </td>
            @endforeach
        </tr>

        @foreach($features as $feature)

            {{-- heading row --}}
            @if(@$feature['code'] == 'heading')
                <tr>
                    <td colspan="1" class="text-heading">
                        <p class="text-left">{{$feature['title']}}</p>
                    </td>
                    <td  colspan="{{ sizeof($planIds) }}" class="heading-desc">{{ $feature['description'] ?? "" }}</td>
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
                <a target="_blank" class="btn-all-quotes" href="{{($websitURL . '/health-insurance/quote/' . $quote->uuid )}}" >Compare Quotes</a>
            </td>
        </tr>
        </tbody>
    </table>

    <table class="tbl-dec">
        <tbody>
        <tr>
            <td>
                <span class="text-sm"><b>MATERIAL INFORMATION DECLARATION</b></span>
                <p class="text-left text-xs">All quotes we provide are indicative and based on the information you have provided to us.</p>
                <span class="text-sm"><b>DISCLAIMER</b></span>
                <p class="text-left text-xs">
                    Whilst we try to ensure the currency and accuracy of the details in the comparison table, there may occasion where there are differences in the covers provided. In such cases, the covers detailed in the insurer's policy wordings and schedules will supersede the details provided by us.<br/><br/>
                    To view the full text of <b>MATERIAL INFORMATION DECLARATION</b> and <b>DISCLAIMER</b>, please refer to the <a class="text-black" href="{{($websitURL . '/health-insurance/quote/' . $quote->uuid )}}"><b>quote</b></a>.
                </p>
            </td>
        </tr>
        </tbody>
    </table>
    </div>
</main>
{{-- z-index: 9000;margin-bottom: -100px !important;padding-bottom: -100px !important; --}}
<img src="{{public_path('images/quote_plans_pages/rm-p3.jpg')}}" class="full-page-image" style="position: relative;top: -80px;height: 100%;"  />
</body>
</html>
