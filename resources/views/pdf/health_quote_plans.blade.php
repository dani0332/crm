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
    </style>
</head>

<body>
    {{-- First Page --}}
    <img src="{{ public_path('images/quote_plans_pages/rm-p1-1.png') }}" class="full-page-image" />
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

        foreach ($quotePlans->quote->plans as &$quotePlan) {
            $addonsPrice = $addonsVat = $quotePlan->discountPremium = $quotePlan->vat = $quotePlan->total = 0;

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
            $quotePlan->addons = isset($addons)
                ? $addons[$quotePlan->id] ?? []
                : json_decode(
                    collect($quotePlan->addons)
                        ->keyBy('code')
                        ->toJson(),
                );

            // Discount Premium and VAT new Implementation
            if (isset($quotePlan->addons['coPayment'])) {
                $coPayId = $quotePlan->addons['coPayment']['id'];
                foreach ($quotePlan->ratesPerCopay as $coPayKey => $coPayVal) {
                    if ($coPayVal->healthPlanCoPaymentId == $coPayId) {
                        $quotePlan->discountPremium = $coPayVal->discountPremium;
                        $quotePlan->vat = $coPayVal->vat;
                        $quotePlan->total = $quotePlan->discountPremium + $coPayVal->vat;
                    }
                }
            } else {
                $discountPremium = $vat = [];
                foreach ($quotePlan->ratesPerCopay as $coPayKey => $coPayVal) {
                    $discountPremium[] = $coPayVal->discountPremium;
                    $vat[] = $coPayVal->vat;
                }
                $minVat = collect($vat)->min();
                $quotePlan->discountPremium = collect($discountPremium)->min();
                $quotePlan->vat = $minVat;
                $quotePlan->total = $quotePlan->discountPremium + $minVat;
            }

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
            $policyFee = isset($providers[$quotePlan->providerId]['health_policy_fee'])
                ? $providers[$quotePlan->providerId]['health_policy_fee']
                : 0;
            $quotePlan->discountPremium += $policyFee;
            // $quotePlan->vat += ($policyFee * ($vatPercentage / 100 ));
            $quotePlan->total += $policyFee;

            $quotePlan->hospitals = [];
            $quotePlan->clinics = [];

            if (property_exists($quotePlan, 'healthNetwork')) {
                $featuredFacilities = $quotePlan?->healthNetwork?->featuredFacilities;
                if ($featuredFacilities) {
                    foreach ($featuredFacilities as $facility) {
                        if ($facility->type == 'HOSPITAL') {
                            $quotePlan->hospitals[] = $facility;
                        } elseif ($facility->type == 'CLINIC') {
                            $quotePlan->clinics[] = $facility;
                        }
                    }
                }
            }
            $quotePlan->hospitals = array_slice($quotePlan->hospitals, 0, 5);
            $quotePlan->clinics = array_slice($quotePlan->clinics, 0, 5);
        }

        $planIds = collect($plans)->sortByDesc('isRenewal')->pluck('id')->toArray();

        $features = [
            ['code' => 'heading', 'title' => 'BENEFITS'],
            [
                'code' => 'keyHospitalsClinic',
                'title' => 'Key hospitals & clinics',
                'type' => 'networkList',
            ],
            ['code' => 'annualLimit', 'title' => 'Annual Claim Limit', 'type' => 'feature'],
            ['code' => 'regionsCovered', 'title' => 'Regions Covered', 'type' => 'regionCover'],
            [
                'code' => 'billingHospitalsOutpatient',
                'title' => 'Direct Billing Network (Outpatient)',
                'type' => 'networkList',
            ],
            [
                'code' => 'billingHospitalsInpatient',
                'title' => 'Direct Billing Network (Inpatient)',
                'type' => 'networkList',
            ],
            ['code' => 'preExistingOrChronic', 'title' => 'Pre‐existing & Chronic Conditions', 'type' => 'inpatient'],

            [
                'code' => 'heading',
                'title' => 'Outpatient Benefits*',
                'description' =>
                    '*All benefits, limits and sublimits are subject to applicable excess, co‐insurance/co‐pays and prior authorization; Specific benefits varies for each insurer, please refer to table of benefits for more details',
            ],
            ['code' => 'medicine', 'title' => 'Medicines', 'type' => 'outpatient'],
            ['code' => 'consultation', 'title' => 'Consultation & Diagnostic', 'type' => 'outpatient'],
            [
                'code' => 'alternativeMedicineAndTreatment',
                'title' => 'Alternative Medicine',
                'type' => ['outpatient', 'exclusion'],
            ],
            ['code' => 'physiotherapy', 'title' => 'Physiotherapy', 'type' => 'outpatient'],
            ['code' => 'dentalCover', 'title' => 'Routine Dental', 'type' => ['outpatient', 'exclusion']],
            ['code' => 'visionAndHearingCover', 'title' => 'Routine Optical', 'type' => 'outpatient'],

            [
                'code' => 'heading',
                'title' => 'Inpatient Benefits*',
                'description' =>
                    '*All benefits, limits and sub limits are subject to applicable excess co‐insurance/co‐pays and prior authorization; Specific benefits varies for each insurer, please refer to table of benefits for more details',
            ],
            [
                'code' => 'surgeryRecovery',
                'title' => 'Medicines, Surgery and Recovery, Diagnostics and Physiotherapy',
                'type' => 'inpatient',
            ],
            ['code' => 'roomBoard', 'title' => 'Room and board', 'type' => 'inpatient'],

            ['code' => 'heading', 'title' => 'Maternity Cover'],
            ['code' => 'testCovered', 'title' => 'Outpatient Maternity', 'type' => 'maternityCover'],
            ['code' => 'coInsurance', 'title' => 'Outpatient Maternity Co‐insurance', 'type' => 'maternityCover'],
            ['code' => 'normalDelivery', 'title' => 'Delivery', 'type' => 'maternityCover'],
            ['code' => 'newBorn', 'title' => 'Newborn Cover', 'type' => 'maternityCover'],

            ['code' => 'heading', 'title' => 'Co‐pay or Co‐insurance'],
            ['code' => 'coPayment', 'title' => 'Outpatient co-pay', 'type' => 'coInsurance'],
            ['code' => 'physiotherapy', 'title' => 'Outpatient Physiotherapy', 'type' => 'coInsurance'],
            ['code' => 'dentalCover', 'title' => 'Routine Dental', 'type' => 'coInsurance'],
            ['code' => 'opticalCover', 'title' => 'Routine Optical', 'type' => 'coInsurance'],
            ['code' => 'inpatient', 'title' => 'Inpatient', 'type' => 'coInsurance'],
            ['code' => 'spacer'],
        ];

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

        $first = true;

        foreach ($quotePlans->quote->plans as &$quotePlan) {
            $addonsPrice = $addonsVat =
                // $quotePlan->discountPremium =
                $quotePlan->vat = $quotePlan->total = 0;

            if ($first) {
                foreach ($quotePlan->memberPremiumBreakdown as $key => $memberPremiumBreakdown) {
                    $key = 'discountPremium' . $key;
                    $features[] = [
                        'code' => $key,
                        'title' => \App\Models\HealthQuote::getCustomerMemberName($memberPremiumBreakdown->memberId),
                        'type' => 'info',
                        'heading_class' => 'text-heading',
                        'row_class' => 'row-spacing',
                    ];
                }
            }
            $first = false;

            if (!isset($quotePlan->id) || !in_array($quotePlan->id, $planIds)) {
                continue;
            }

            $firstMember = true;
            $vatValue = 0;
            $totalValue = 0;

            foreach ($quotePlan->memberPremiumBreakdown as $key => $memberPremiumBreakdown) {
                $key = 'discountPremium' . $key;
                $discountPremiumValue = 0;
                if (isset($quotePlan->addons['coPayment'])) {
                    $coPayId = $quotePlan->addons['coPayment']['id'];
                    // Check if ratesPerCopay exists and is an array
                    if (
                        isset($memberPremiumBreakdown->ratesPerCopay) &&
                        is_array($memberPremiumBreakdown->ratesPerCopay)
                    ) {
                        foreach ($memberPremiumBreakdown->ratesPerCopay as $coPayKey => $coPayVal) {
                            if ($coPayVal->healthPlanCoPaymentId == $coPayId) {
                                $discountPremiumValue =
                                    $coPayVal->premium + $coPayVal->basmah + ($coPayVal->loadingPrice ?? 0);
                                $vatValue += $coPayVal->vat;
                                $totalValue += $discountPremiumValue;
                            }
                        }
                    }
                } else {
                    $discountPremium = $vat = [];
                    // Check if ratesPerCopay exists and is an array
                    if (
                        isset($memberPremiumBreakdown->ratesPerCopay) &&
                        is_array($memberPremiumBreakdown->ratesPerCopay)
                    ) {
                        foreach ($memberPremiumBreakdown->ratesPerCopay as $coPayKey => $coPayVal) {
                            $discountPremium[] =
                                $coPayVal->premium + $coPayVal->basmah + ($coPayVal->loadingPrice ?? 0);
                            $vat[] = $coPayVal->vat;
                        }
                        $discountPremiumValue = collect($discountPremium)->min();
                        $vatValue += collect($vat)->min();
                        $totalValue += $discountPremiumValue;
                    }
                }
                if ($firstMember) {
                    $discountPremiumValue += $quotePlan->policyFee;
                    $firstMember = false;
                }
                $value = $discountPremiumValue;
                $quotePlan->{$key} = $value;
            }

            $quotePlan->vat = $vatValue;
            $quotePlan->total = $totalValue + $vatValue;

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
            $quotePlan->addons = isset($addons[$quotePlan->id])
                ? $addons[$quotePlan->id]
                : json_decode(
                    collect($quotePlan->addons)
                        ->keyBy('code')
                        ->toJson(),
                );

            foreach ($quotePlan->benefits as &$benefit) {
                $benefit = (object) $benefit;
                $benefit->value = 'Excluded';
                //set default values
                $benefit->price = 0;
                $benefit->vat = 0;
                $plans[$quotePlan->id] = $quotePlan;
            }
            // Add Basma Price
            if ($quote->emirate_of_your_visa_id == \App\Enums\EmirateEnum::DUBAI) {
                $quotePlan->discountPremium += $quotePlan->basmah;
            }
            // Add Policy Price
            $policyFee = isset($providers[$quotePlan->providerId]['health_policy_fee'])
                ? $providers[$quotePlan->providerId]['health_policy_fee']
                : 0;
            $quotePlan->discountPremium += $policyFee;
            $quotePlan->total += $policyFee;
        }

        $planIds = collect($plans)->sortByDesc('isRenewal')->pluck('id')->toArray();

        $featureItems = [
            [
                'code' => 'vat',
                'title' => 'VAT',
                'type' => 'info',
                'heading_class' => 'text-heading',
                'row_class' => 'row-spacing',
            ],
            ['code' => 'spacer'],
            [
                'code' => 'total',
                'title' => 'Total Price',
                'type' => 'info',
                'heading_class' => 'text-heading',
                'row_class' => 'row-spacing',
            ],
        ];

        $features = array_merge($features, $featureItems);
    @endphp

    {{-- PDF Page Header --}}
    <header>
        <div>
            <img src="{{ public_path('images/header.png') }}">
        </div>
    </header>

    {{-- PDF Page Footer --}}
    <footer>
        <table class="tbl-footer">
            <div style="float: left;">
                <img style="height: 110px; border-radius: 50%;"
                    src="{{ $quote->advisor?->profile_photo_path != null ? $quote->advisor?->profile_photo_path : public_path('image/alfred-theme.png') }}">
            </div>
            <div style="float: left; margin-left: 10px; margin-top: 20px">
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
                                src="{{ public_path('images/whatsapp-small.png') }}"></span></p>
                @endif
                @if (isset($quote->advisor->landline_no) && !empty($quote->advisor->landline_no))
                    <p class="text-left text-white text-xl">Direct Line: <a class="text-white"
                            href="tel:{{ $quote->advisor->landline_no }}">{{ formatLandlineNumber($quote->advisor->landline_no) }}</a>
                    </p>
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
        <table class="table-fixed text-center tbl-plans" style="position: relative;top: 100px;margin-bottom: 130px;">
            <thead>
                <tr>
                    <th class="alfred" rowspan="3">
                        <img src="{{ public_path('images/alfred.png') }}" />
                    </th>
                    @foreach ($planIds as $planId)
                        <th class="provider" style="border: solid 1px #bfbfbf; position : relative">
                            @php
                                $providerLogoImage = public_path(
                                    'images/insurance_providers/' . strtolower($plans[$planId]->providerCode) . '.png',
                                );
                                if (!file_exists($providerLogoImage)) {
                                    $providerLogoImage = public_path('images/insurance_providers/default.png');
                                }
                            @endphp
                            <div class="image-wrapper">
                                <img class="provider-logo" src="{{ $providerLogoImage }}" alt="Provider Logo" />
                              </div>
                        </th>
                    @endforeach
                </tr>
                <tr>
                    @foreach ($planIds as $planId)
                        <th class="provider-name">
                            <p class="text-center">
                                {{ $plans[$planId]->providerName }}
                            </p>
                        </th>
                    @endforeach
                </tr>
                <tr>
                    @foreach ($planIds as $planId)
                        <th style="padding: 0;margin: 0;">
                            <p class="text-center">
                                {{ $plans[$planId]->name }}
                            </p>
                        </th>
                    @endforeach
                </tr>
                {{-- buy now row --}}
                <tr>
                    <th class="bg-light-blue">
                        <p class="quote-info">Health insurance comparison for: <b>{{ $quote->first_name }}
                                {{ $quote->last_name }}</b></p>
                    </th>
                    @foreach ($planIds as $planId)
                        <th>
                            <p class="text-center">
                                @if ($plans[$planId]->discountPremium)
                                    <a target="_blank" class="btn-buy"
                                        href="{{ $websitURL . '/health-insurance/quote/' . $quote->uuid . '/payment/?providerCode=' . $plans[$planId]->providerCode . '&planId=' . $planId . (isset($plans[$planId]->addons['coPayment']['id']) ? '&selectedCopayId=' . $plans[$planId]->addons['coPayment']['id'] : '') }}">APPLY
                                        Now</a>
                                @else
                                    N/A
                                @endif
                            </p>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
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

                    {{-- spacer row --}}
                    @if (@$feature['code'] == 'keyHospitalsClinic')
                        <tr class="text-left">
                            <td class="{{ @$feature['heading_class'] }}">
                                <p class="text-left strong">{{ @$feature['title'] }}</p>
                                <p class="text-xs text-left" style="color: red">*The network of hospitals
                                    and clinics may include multiple facilities within hospital groups and is subject to
                                    change at the insurer's discretion.</p>
                            </td>
                            @foreach ($planIds as $planId)
                                <td style="vertical-align: top; padding-top: 12px"
                                    class="{{ @$feature['col_class'] }}">
                                    @if (count($plans[$planId]->hospitals) > 0)
                                        <div>
                                            <p class="text-left">Key Hospitals:</p>
                                            <ul style="margin-top: 0px">
                                                @foreach ($plans[$planId]->hospitals as $hospital)
                                                    <li style="font-size: 14px">{{ $hospital->text }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    @if (count($plans[$planId]->clinics) > 0)
                                        <div>
                                            <p class="text-left">Key Clinics:</p>
                                            <ul style="margin-top: 0px">
                                                @foreach ($plans[$planId]->clinics as $hospital)
                                                    <li style="font-size: 14px">{{ $hospital->text }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    <p class="text-left" style="text-decoration: underline; font-style: italic;"><a
                                            target="_blank"
                                            href="{{ $websitURL . '/health-insurance/quote/' . $quote->uuid . '/payment/?providerCode=' . $plans[$planId]->providerCode . '&planId=' . $planId . (isset($plans[$planId]->addons['coPayment']['id']) ? '&selectedCopayId=' . $plans[$planId]->addons['coPayment']['id'] : '') }}">See
                                            full list</a></p>
                                </td>
                            @endforeach

                        </tr>
                        @php continue; @endphp
                    @endif

                    {{-- feature rows --}}
                    <tr class="{{ $feature['row_class'] ?? '' }}">
                        <td class="{{ @$feature['heading_class'] }}">
                            <p class="text-left">{{ @$feature['title'] }}</p>
                        </td>
                        @foreach ($planIds as $planId)
                            <td class="{{ @$feature['col_class'] }}">
                                <p>
                                    @if ($feature['type'] == 'info')
                                        {!! isset($plans[$planId]->{$feature['code']}) ? formatAmount($plans[$planId]->{$feature['code']}) : 'N/A' !!}
                                    @elseif($feature['type'] == 'prop')
                                        {!! $plans[$planId]->{$feature['code']} !!}
                                    @elseif($feature['type'] == 'buy')
                                        @if ($plans[$planId]->discountPremium)
                                            <a target="_blank" class="btn-buy"
                                                href="{{ $websitURL . '/car-insurance/quote/' . $quote->uuid . '/payment/?providerCode=' . $plans[$planId]->providerCode . '&planId=' . $planId }}">APPLY
                                                Now</a>
                                        @else
                                            N/A
                                        @endif
                                    @elseif(is_array($feature['type']))
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
                                        @if ($feature['code'] == 'coPayment')
                                            {{ $addons[$planId]['coPayment']['text'] ?? 'N/A' }}
                                        @else
                                            {!! $plans[$planId]->{$feature['type']}->{$feature['code']}->value ?? 'Excluded' !!}
                                        @endif
                                    @endif
                                </p>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                <tr>
                    <td colspan="{{ sizeof($planIds) + 1 }}" class="no-border text-center">
                        <a target="_blank" class="btn-all-quotes"
                            href="{{ $websitURL . '/health-insurance/quote/' . $quote->uuid }}">See all your
                            quotes</a>
                    </td>
                </tr>
            </tbody>
        </table>

        <table class="tbl-dec">
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
                                href="{{ $websitURL . '/health-insurance/quote/' . $quote->uuid }}"><b>quote</b></a>.
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
    </main>

    {{-- Last Page --}}
    <img src="{{ public_path('images/quote_plans_pages/rm-p3-2.png') }}" class="full-page-image" />
</body>

</html>
