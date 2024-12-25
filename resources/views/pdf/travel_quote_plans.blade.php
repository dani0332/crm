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
            font-size: 3px;
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

        thead>tr>th:first-child {
            max-width: 30%;
        }

        tr th:first-child,
        tr td:first-child {
            width: 400px;
            min-width: 400px;
            max-width: 400px;
        }

        td>p,
        th>p {
            padding: 4px;
            font-size: 12px !important;
            text-align: center;
            font-weight: normal;
        }

        .text-left {
            text-align: left;
        }

        .text-xs {
            font-size: 10px;
        }

        .text-sm {
            font-size: 10px;
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
            padding: 1px 8px;
            color: #252525;
        }

        .bg-light-blue p {
            padding: 1px !important;
        }

        .text-black {
            color: #000000;
        }

        .provider {
            border: 1px solid #bfbfbf;
            /*font-size: 15px;*/
            /*line-height: 28px;*/
            font-weight: 400;
            color: #4ea4a8;
            vertical-align: middle;
            /*max-height: 50px;*/
            /*height: 50px;*/
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
            width: 400px;
            min-width: 400px;
        }

        .quote-info {
            text-align: right;
            vertical-align: bottom;
            margin-top: -1px;
            background: #EFF6FF;
            font-size: 10px;
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
            padding: 8px 12px;
            margin-top: 30px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 12px;
            font-weight: bold;
            border-radius: 5px;
            margin-bottom: 0px;
        }

        .btn-buy {
            background-color: #FE7333;
            color: #ffffff;
            padding: 10px 12px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 10px;
            font-weight: bold;
            border-radius: 4px;
        }

        .btn-buy:hover {
            background-color: #d7fbd0;
        }

        .text-heading {
            color: #ffffff;
            background-color: #1d83bc;
        }

        .heading-desc {
            font-size: 10px;
        }

        .provider-logo {
            width: 100px;
        }

        .no-border {
            border: none;
        }

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
            height: 145px;
            z-index: 1500;
        }

        table.tbl-footer {
            padding: 18px 12px;
            margin: 0;
            width: 100%;
            border: none;
        }

        th.provider-name {
            padding: 0;
            margin: 0;
        }

        table.tbl-footer tr td,
        table.tbl-footer tr td a {
            color: #ffffff;
            border: none;
            font-size: 14px;
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

        .hidden {
            display: none;
        }

        tr:has(td) {
            display: none;
        }

        .header {
            background: #1d83bc;
            color: #ffffff;
            font-size: 19px;
            text-align: center;
            padding: 8px 10px;
            width: 100%;
            height: 60px;
            max-height: 60px;
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
    </style>
</head>

<body>
    {{-- First Page --}}
    <img src="{{ public_path('images/quote_plans_pages/travel-p1-1.png') }}" class="full-page-image" />
    @php
        $websitURL = config('constants.AFIA_WEBSITE_DOMAIN');
        $plans = [];
        $benefits = [
            'feature',
            'inpatient',
            'outpatient',
            'exclusion',
            'coInsurance',
            'regionCover',
            'maternityCover',
            'networkList',
        ];
        $vatPercentage =
            \App\Models\ApplicationStorage::where('key_name', \App\Enums\ApplicationStorageEnums::VAT_VALUE)->first()
                ->value ?? 0;

        $buyNow = 'Buy Now';
        $buyNowLink = $websitURL . '/travel-insurance/quote/' . $quote->uuid . '/payment/?planId=';
        if (isset($hasAdultAndSeniorMember)) {
            if ($hasAdultAndSeniorMember == true) {
                $buyNow = 'Add to Cart';
                $buyNowLink = $websitURL . '/travel-insurance/quote/' . $quote->uuid . '/?planAddToCart=';
            }
        }
        if (isset($selectedPlanIds)) {
        } else {
            $selectedPlanIds = [];
        }
        //  'selectedPlanIds','hasAdultAndSeniorMember'

        foreach ($quotePlans->quotes->plans as &$quotePlan) {
            $addonsPrice = $addonsVat = $quotePlan->total = 0;
            if (!isset($quotePlan->vat)) {
                $quotePlan->vat = 0;
            }

            if (!isset($quotePlan->id) || !in_array($quotePlan->id, $planIds)) {
                continue;
            }

            foreach ($benefits as $benefit) {
                $quotePlan->{$benefit} = [];
                if (isset($quotePlan->benefits->{$benefit})) {
                    $quotePlan->{$benefit} = json_decode(
                        collect(@$quotePlan->benefits->{$benefit})
                            ->keyBy('code')
                            ->toJson(),
                    );
                }
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
            $quotePlan->emergencyMedicalCover = json_decode(
                collect($quotePlan->benefits->emergencyMedicalCover)
                    ->keyBy('code')
                    ->toJson(),
            );
            $quotePlan->covid19 = json_decode(
                collect($quotePlan->benefits->covid19)
                    ->keyBy('code')
                    ->toJson(),
            );
            $quotePlan->travelInconvenienceCover = json_decode(
                collect($quotePlan->benefits->travelInconvenienceCover)
                    ->keyBy('code')
                    ->toJson(),
            );

            $quotePlan->addons = (isset($addons[$quotePlan->id])) ? json_decode(json_encode($addons[$quotePlan->id])) : json_decode(collect($quotePlan->addons)->keyBy('code')->toJson());

            foreach ($quotePlan->addons as &$addon) {

                $addon = (object) $addon;
                //set default value to excluded
                $addon->value = "Excluded";

                //set default values
                $addon->price = 0;
                $addon->vat = 0;

                if(sizeof($addon->addonOptions))
                {
                    //replace exclude with selected value if found

                    foreach ($addon->addonOptions as $index =>  $travelAddonOption) {

                        $travelAddonOption = (object) $travelAddonOption;

                        if($travelAddonOption->isSelected) {
                            $addon->value = 'Included';
                            $addonsPrice += $travelAddonOption->price;
                            $addonsVat += $travelAddonOption->vat;
                            $addon->price = $travelAddonOption->price;
                            $addon->vat   = $travelAddonOption->vat;
                            break;//only one value will be selected
                        }
                    }
                }
            }

            $discountPremium = $vat = [];

            // Discount Premium and VAT new Implementation

            $quotePlan->vat += $addonsVat;
            //$quotePlan->vat = 100;
            $quotePlan->discountPremium += $addonsPrice;
            $quotePlan->total = $quotePlan->discountPremium + $quotePlan->vat;

            foreach ($quotePlan->benefits as &$benefit) {
                $benefit = (object) $benefit;
                //set default value to excluded
                $benefit->value = 'Excluded';

                //set default values
                $benefit->price = 0;
                $benefit->vat = 0;
                $plans[$quotePlan->id] = $quotePlan;
            }

            // Add Basma Price
            if ($quote->emirate_of_your_visa_id == \App\Enums\EmirateEnum::DUBAI) {
                $quotePlan->discountPremium += $quotePlan->basmah;
                $quotePlan->total += $quotePlan->basmah;
            }

            // Add Policy Price
            $policyFee =
                property_exists($quotePlan, 'insuranceProviderId') &&
                isset($providers[$quotePlan->insuranceProviderId]['travel_policy_fee'])
                    ? $providers[$quotePlan->insuranceProviderId]['travel_policy_fee']
                    : 0;
            $quotePlan->discountPremium += $policyFee;
            // $quotePlan->vat += ($policyFee * ($vatPercentage / 100 ));
            $quotePlan->total += $policyFee;
        }

        $planIds = collect($plans)->sortByDesc('isRenewal')->pluck('id')->toArray();

        $features = [
            ['code' => 'heading', 'title' => 'TRAVEL INCONVENIENCE BENEFITS'],

            ['code' => 'travelCancellationCurtailment', 'title' => 'Cancellation / Curtailment', 'type' => 'feature'],
            [
                'code' => 'travelCancellationTestPositive',
                'title' => 'Cancellation due to testing positive for COVID prior to departure',
                'type' => 'covid19',
            ],
            [
                'code' => 'travelCancellationRestrictionCovid19',
                'title' => 'Cancellation due to travel restrictions against COVID-19',
                'type' => 'travelInconvenienceCover',
            ],
            ['code' => 'travelDelayedBaggage', 'title' => 'Delayed Baggage', 'type' => 'travelInconvenienceCover'],
            ['code' => 'travelDelayedDeparture', 'title' => 'Delayed Departure', 'type' => 'travelInconvenienceCover'],
            ['code' => 'travelHijack', 'title' => 'Hijack', 'type' => 'exclusion'],
            ['code' => 'travelLegalExpenses', 'title' => 'Legal Expenses', 'type' => ['feature','travelLegalExpenses']],
            ['code' => 'travelMissedDeparture', 'title' => 'Missed Departure', 'type' => 'travelInconvenienceCover'],
            [
                'code' => 'travelPassportAssistance',
                'title' => 'Passport Assistance',
                'type' => 'travelInconvenienceCover',
            ],
            ['code' => 'travelPersonalAccident', 'title' => 'Personal Accident', 'type' => 'feature'],
            ['code' => 'travelPersonalBaggage', 'title' => 'Personal Baggage', 'type' => 'travelInconvenienceCover'],
            [
                'code' => 'travelPersonalLiability',
                'title' => 'Personal Liability',
                'type' => ['feature', 'travelInconvenienceCover'],
            ],
            ['code' => 'travelPersonalMoney', 'title' => 'Personal Money', 'type' => 'travelInconvenienceCover'],
            [
                'code' => 'travelTravelVisaRejection',
                'title' => 'Travel Visa Rejection',
                'type' => 'travelInconvenienceCover',
            ],

            ['code' => 'heading', 'title' => 'Emergency Medical Benefits'],

            ['code' => 'travelEmergencyDental', 'title' => 'Emergency Dental', 'type' => 'emergencyMedicalCover'],
            [
                'code' => 'travelEmergencyMedicalExpenses',
                'title' => 'Emergency Medical Expenses',
                'type' => 'emergencyMedicalCover',
            ],
            ['code' => 'travelEmergencyTransport', 'title' => 'Emergency Transport', 'type' => 'emergencyMedicalCover'],
            [
                'code' => 'travelRepatriationOfMortalRemains',
                'title' => 'Repatriation of mortal remains',
                'type' => 'inclusion',
            ],
            [
                'code' => 'travelRepatriationOtherInsuredPerson',
                'title' => 'Repatriation of other insured Person',
                'type' => 'exclusion',
            ],

            ['code' => 'excess', 'title' => 'Excess', 'type' => 'excess', 'heading_class' => 'text-heading'],
            ['code' => 'heading', 'title' => 'COVID 19'],

            ['code' => 'travelTestingCost', 'title' => 'Testing Cost', 'type' => 'covid19'],
            [
                'code' => 'travelQuarantineTestedPositive',
                'title' => 'Accommodation cost if tested positive',
                'type' => 'covid19',
            ],
            [
                'code' => 'travelTreatmentTestedPositive',
                'title' => 'Treatment if tested positive - hospitalized for more than 24 hrs',
                'type' => 'covid19',
            ],
            [
                "code" => "heading",
                "title" => "Optional Covers",
                "type" => ""
            ],
            [
                "code" => "hazardousActivities",
                "title" => "Hazardous Activities Cover",
                "type" => "addons"
            ],
            [
                "code" => "adventureSports",
                "title" => "Adventure Sports Cover",
                "type" => "addons"
            ],
            [
                "code" => "winterSports",
                "title" => "Winter Sports Cover",
                "type" => "addons"
            ],
            [
                "code" => "waterSports",
                "title" => "Water Sports Cover",
                "type" => "addons"
            ],
            [
                "code" => "business",
                "title" => "Business Cover",
                "type" => "addons"
            ],
            [
                "code" => "golf",
                "title" => "Golf Cover",
                "type" => "addons"
            ],
            [
                "code" => "terrorism",
                "title" => "Terrorism Cover",
                "type" => "addons"
            ],
            [
                "code" => "excessWaiver",
                "title" => "Excess Waiver",
                "type" => "addons"
            ],
            [
                "code" => "rentalCar",
                "title" => "Rental Car Excess",
                "type" => "addons"
            ],
            [
                "code" => "trekking",
                "title" => "Trekking (GIG)",
                "type" => "addons"
            ],
            [
                "code" => "safari",
                "title" => "Safari (GIG)",
                "type" => "addons"
            ],
            [
                'code' => 'actualPremium',
                'title' => 'Premium',
                'type' => 'info',
                'heading_class' => 'text-heading',
                'row_class' => 'row-spacing',
            ],
            [
                'code' => 'vat',
                'title' => 'VAT',
                'type' => 'info',
                'heading_class' => 'text-heading',
                'row_class' => 'row-spacing',
            ],
            [
                'code' => 'total',
                'title' => 'Total Premium',
                'type' => 'info',
                'heading_class' => 'text-heading',
                'row_class' => 'row-spacing',
            ],
        ];

    @endphp

    {{-- PDF Page Header --}}
    <header>
        <div class="header">
            <div class="logo">
                <img class="im-logo" src="{{ getIMLogo(true) }}" alt="logo" />
            </div>
            <h3>Your Tailor Made <br />Travel Insurance Comparison Table</h3>
        </div>
    </header>

    {{-- PDF Page Footer --}}
    <footer>
        <table class="tbl-footer">
            <div style="float: left;">
                <img style="height: 110px; border-radius: 50%;"
                    src="{{ $quote->advisor?->profile_photo_path != null ? $quote->advisor?->profile_photo_path : public_path('image/alfred-theme.png') }}"
                    alt="advisor">
            </div>
            <div style="float: left; margin-left: 10px; margin-top: 10px">
                @if (isset($quote->advisor->name) && !empty($quote->advisor->name))
                    <p class="text-left text-white text-xl">Name: {{ $quote->advisor?->name }}</p>
                @endif
                @if (isset($quote->advisor->email) && !empty($quote->advisor->email))
                    <p class="text-left text-white text-xl">Email: <a class="text-white"
                            href="mailto:{{ $quote->advisor->email }}">{{ $quote->advisor->email }}</a></p>
                @endif
                @if (isset($quote->advisor->mobile_no) && !empty($quote->advisor->mobile_no))
                    <p class="text-left text-white text-xl mar">Mobile number:
                        {{ formatMobileNumber($quote->advisor->mobile_no) }} <span class="text-white"
                            style="margin-top:3px"><img style="height:20px;"
                                src="{{ public_path('images/whatsapp-small.png') }}" alt="advisor phone"></span></p>
                @endif
                @if (isset($quote->advisor->landline_no) && !empty($quote->advisor->landline_no))
                    <p class="text-left text-white text-xl">Direct Line: <a class="text-white"
                            href="tel:{{ $quote->advisor->landline_no }}">{{ $quote->advisor->landline_no }}</a></p>
                @endif

            </div>
            <div>
                <h4 class="text-right text-white">InsuranceMarket.ae</h4>
                <p class="text-right text-white text-xl"><a class="text-white" href="tel:+800253733">Happiness Center:
                        800 ALFRED (800-253-733)</a></p>
                <p class="text-right text-white text-xl"><a class="text-white"
                        href="https://insurancemarket.ae">www.insurancemarket.ae</a></p>
                <p class="text-right text-white text-xl">27th Floor, Control Tower, Motor City, Dubai,</p>
                <p class="text-right text-white text-xl">United Arab Emirates, PO Box 26423 <a
                        class="text-white text-underline"
                        href="https://www.google.com/maps/place//data=!4m2!3m1!1s0x3e5f42d8a8e59cff:0x24d4afc0d969548c?source=g.page.share">(map)</a>
                </p>
            </div>
        </table>
    </footer>

    {{-- PDF Page Inner Content --}}
    <main>
        <table class="table-fixed text-center tbl-plans"
            style="position: relative;top: 90px;margin-bottom: 70px;table-layout: fixed">
            <thead>
                <tr>
                    <th class="alfred" id="alfred-th">
                        <img src="{{ public_path('images/alfred.png') }}" />
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
                    <th></th>
                    @foreach ($planIds as $planId)
                        <th style="border: solid 1px #bfbfbf; text-align: center;">
                                <p class="text-center" style="font-size: 14px">
                                {{$plans[$planId]->planName ?? ''}}
                                </p>
                        </th>
                    @endforeach
                </tr>
                {{-- buy now row --}}
                <tr>
                    <th class="bg-light-blue" style="width: 25% !important;">
                        <p class="quote-info">Travel insurance comparison for: <b>{{ $quote->first_name }}
                                {{ $quote->last_name }}</b></p>
                    </th>
                    @foreach ($planIds as $planId)
                        <th rowspan="4">
                            <p class="text-center">
                                @php
                                    if (in_array($planId, $selectedPlanIds)) {
                                        $buyNowfullLink = '#';
                                        $buyNowText = 'Selected';
                                    } else {
                                        $buyNowfullLink = $buyNowLink . $planId;
                                        $buyNowText = $buyNow;
                                    }
                                @endphp
                                <a target="_blank" class="btn-buy"
                                    href="{{ $buyNowfullLink }}">{{ $buyNowText }}</a>
                                @if ($plans[$planId]->discountPremium)
                                @else
                                @endif
                            </p>
                        </th>
                    @endforeach
                </tr>
                <tr>
                    <th class="bg-light-blue">
                        <p class="quote-info">Main Country: <b>N/A</b></p>
                    </th>
                </tr>
                <tr>
                    <th class="bg-light-blue">
                        <p class="quote-info">Number of Days: <b>{{ $quote->days_cover_for }} </b></p>
                    </th>
                </tr>
                <tr>
                    <th class="bg-light-blue">
                        <p class="quote-info">Region: <b>{{ $quote->regionCoverFor?->text }}</b></p>
                    </th>
                </tr>
            </thead>
            <tbody>
                @php $featCount = 0 @endphp
                @foreach ($features as $feature)
                    @if ($feature['code'] == 'coPayment' && !isset($addons))
                        @php continue; @endphp
                    @endif

                    {{-- heading row --}}
                    @if (@$feature['code'] == 'heading')
                        <tr>
                            <td colspan="1" class="text-heading">
                                <p class="text-left">{{ $feature['title'] }}</p>
                            </td>
                            <td colspan="{{ sizeof($planIds) }}" class="heading-desc">
                                {{ $feature['description'] ?? '' }}</td>
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
                    <?php $planIterate = 0; ?>
                    <tr class="<?php echo 'row_'.$featCount; ?> {{ $feature['row_class'] ?? '' }}">
                        <td class="{{ @$feature['heading_class'] }}">
                            <p class="text-left">{{ @$feature['title'] }}</p>
                        </td>
                        @foreach ($planIds as $planId)
                            <?php $return_value = '';
                    ?>
                            @if ($feature['type'] == 'info')
                                @php $return_value =  $plans[$planId]->{$feature['code']} ? formatAmount($plans[$planId]->{$feature['code']})  : 'N/A' @endphp
                            @elseif($feature['type'] == 'excess')
                                @php $return_value =   $plans[$planId]->excess->premium ?? "" @endphp
                            @elseif($feature['type'] == 'prop')
                                @php $return_value =   $plans[$planId]->{$feature['code']}  @endphp
                            @elseif($feature['type'] == 'buy')
                                @php
                                    if (in_array($planId, $selectedPlanIds)) {
                                        $buyNowfullLink = '#';
                                        $buyNowText = 'Selected';
                                    } else {
                                        $buyNowfullLink = $buyNowLink . $planId;
                                        $buyNowText = $buyNow;
                                    }
                                $return_value = `<a target="_blank" class="btn-buy" href="{{ $buyNowfullLink }}" >'.$buyNowText.'</a>`; @endphp
                            @elseif(is_array($feature['type']))
                                @php $value = "Excluded";  @endphp
                                @foreach ($feature['type'] as $type)
                                    @if (isset($plans[$planId]->{$type}->{$feature['code']}->value))
                                        @php
                                            $value = $plans[$planId]->{$type}->{$feature['code']}->value;
                                            break;
                                        @endphp
                                    @endif
                                @endforeach
                                @php  $return_value  = $value; @endphp
                            @else
                                @if ($feature['code'] == 'coPayment')
                                    @php  $return_value = $addons[$planId]['coPayment']['text'] ?? 'N/A'; @endphp
                                @else
                                    @php  $return_value =  $plans[$planId]->{$feature['type']}->{$feature['code']}->value ?? 'Excluded' ; @endphp
                                @endif
                            @endif
                            <td class="{{ @$feature['col_class'] }}">
                                <p>
                                    <?php if ($return_value == 'Excluded') {
                                        $planIterate++;
                                        ?>
                                    Excluded
                                    <?php } else {
                                        $planIterate = 0;
                                        ?>
                                    <?php echo $return_value; ?>

                                    <?php } ?>
                                </p>

                            </td>
                            <?php if (count($planIds) == $planIterate) {
                                ?>
                            <style>
                                .row_<?php echo $featCount; ?> {
                                    display: none !important;
                                }
                            </style>
                            <?php

                            }
                    ?>
                        @endforeach
                    </tr>
                    <?php $featCount++; ?>
                @endforeach
                <tr>
                    <td>

                    </td>
                    @foreach ($planIds as $planId)
                        <td>
                            <p class="text-center">
                                <a target="_blank" class="btn-buy"
                                    href="{{ $buyNowLink . $planId }}">{{ in_array($planId, $selectedPlanIds) ? 'Selected' : $buyNow }}</a>
                            </p>
                        </td>
                    @endforeach
                </tr>
                <tr>
                    <td colspan="{{ sizeof($planIds) + 1 }}" class="no-border text-center">
                        <a target="_blank" class="btn-all-quotes"
                            href="{{ $websitURL . '/travel-insurance/quote/' . $quote->uuid }}">View all quotes</a>
                    </td>
                </tr>
            </tbody>
        </table>

        <table class="tbl-dec" style="margin-top: 20px; padding-top: 40px;">
            <tbody>
                <tr>
                    <td>
                        <span class="text-sm"><b>MATERIAL INFORMATION DECLARATION</b></span>
                        <p class="text-left text-xs">All quotes provided are indications only and based on the initial
                            information you have provided: as such, they are subject to change in line with any
                            revisions to that information that you declare to us during the application and/or
                            underwriting process. Note that quotes also include all mandatory fees, taxes or charges as
                            stipulated by the UAE Government and/or relevant authorities.</p>
                        <span class="text-sm"><b>DISCLAIMER</b></span>
                        <p class="text-left text-xs">
                            Whilst we try to ensure the currency and accuracy of the details in the comparison table,
                            there may occasion where there are differences in the covers provided. In such cases, the
                            covers detailed in the insurer's policy wordings and schedules will supersede the details
                            provided by us.<br /><br />
                            To view the full text of <b>MATERIAL INFORMATION DECLARATION</b> and <b>DISCLAIMER</b>,
                            please refer to the <a class="text-black"
                                href="{{ $websitURL . '/travel-insurance/quote/' . $quote->uuid }}"><b>quote</b></a>.
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
    </main>

    {{-- Last Page --}}
    <img src="{{ public_path('images/quote_plans_pages/travel-p1-2.png') }}" class="full-page-image" />
</body>

</html>
