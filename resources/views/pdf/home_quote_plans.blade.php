<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Plans Comparison PDF</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&family=Raleway:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
    /* * {
        font-family: "DejaVu Sans", sans-serif !important;
    } */
    * {
        font-family: 'Prompt', sans-serif !important;
    }

    .raleway-font {
        font-family: 'Raleway', sans-serif !important;
    }

    @page {
        margin: 0;
        padding: 0;
        margin-bottom: 160px;
    }

    html {
        line-height: 1.5;
        margin: 0;
        padding: 0;
    }

    body {
        /* line-height: 1.6; */
        line-height: 1;
        margin: 0;
        padding: 0;
        font-size: 12px;
        font-weight: 400;
        font-family: "DejaVu Sans", sans-serif !important;
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
        font-size: 12px;
        font-weight: 400;
    }

    a {
        text-decoration: inherit;
    }

    b,
    strong {
        font-weight: 700;
    }

    table.tbl-dec {
        margin: auto;
        padding: auto;
        border: none;
        font-size: 12px;
    }

    table.tbl-dec tr td,
    table.tbl-dec tr td a {
        border: none;
    }

    table {
        min-width: 100%;
        text-indent: 0;
        border-color: #bfbfbf;
        max-width: 100%;
        border-spacing: 0;
        border-radius: 10px;
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
        table-layout: fixed;
    }

    tbody {
        margin-bottom: 130px;
    }

    tbody>tr>td {
        border: 1px solid #bfbfbf;
    }

    thead>tr>th {
        border: 1px solid #bfbfbf;
    }

    td>p,
    th>p {
        padding: 4px;
        font-size: 12px !important;
        text-align: center;
        font-weight: 400;
    }

    .text-left {
        text-align: left;
    }

    .text-xs {
        font-size: 10px;
        font-weight: 400;
    }

    .text-sm {
        font-size: 12px;
        font-weight: 400;
    }

    .text-xl {
        font-size: 16px;
        font-weight: 600;
    }

    .blue-box {
        background: #ddfdfc;
    }

    .bg-light-blue {
        border: 1px solid #bfbfbf;
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
        font-size: 15px;
        line-height: 20px;
        font-weight: 400;
        color: #4ea4a8;
        vertical-align: middle;
        max-height: 40px;
        height: 40px;
    }

    .spacer {
        padding: 3px;
    }

    .alfred {
        text-align: right;
        padding: 0;
        width: 100px;
        min-width: 100px;
        height: 100px;
    }

    .quote-info {
        text-align: right;
        vertical-align: bottom;
        margin-top: -1px;
        font-size: 12px;
        font-weight: 400;
        text-align: left;
        padding: 8px;
        max-width: 100%;
    }

    .info h5 {
        background: #1d83bc;
        color: #ffffff;
        padding: 3px;
        font-weight: 600;
        margin: 0 0 10px 0;
    }

    .info p {
        font-size: 12px;
        font-weight: 400;
    }

    .btn-all-quotes {
        background-color: #1d83bc;
        color: #ffffff;
        padding: 8px;
        margin-top: 10px;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        font-size: 20px;
        font-weight: 600;
        border-radius: 5px;
        margin-bottom: 0px;
    }

    .btn-buy {
        background-color: #FE7333;
        color: #ffffff;
        padding: 4px 12px;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        font-size: 14px;
        font-weight: bold;
        border-radius: 5px;
        margin: 5px 0;
    }

    .btn-buy:hover {
        background-color: #d7fbd0;
    }

    .text-heading {
        color: #ffffff;
        height: 40px;
        display: flex;
        align-items: center;
        font-size: 20px;
        font-weight: 700;
    }

    .text-heading p {
        margin: 0;
        padding-left: 12px;
    }

    .heading-desc {
        font-size: 12px;
        font-weight: 400;
    }

    .provider-logo {
        width: 80px;
    }

    .no-border {
        border: none;
    }

    th.provider-name {
        padding: 0;
        margin: 0;
    }

    footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        height: 130px;
        padding: 0;
        background-color: #1d83bc;
        color: #fff;
        text-align: center;
        z-index: 1000;
    }

    table.tbl-footer {
        padding: 7px 30px;
        margin: 0;
        width: 100%;
        border: none;
    }

    table.tbl-footer tr td,
    table.tbl-footer tr td a {
        color: #ffffff;
        border: none;
        font-size: 12px;
        font-weight: 400;
    }

    table.tbl-footer tr td {
        padding: 0;
        margin: 0;
        width: auto;
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
        color: #ffffff;
    }

    .text-underline {
        text-decoration: underline;
    }

    .hidden {
        display: none;
    }

    tr:has(td) {
        display: none;
    }
    .text-blue {
        color: #1d83bc;
    }

    .page-image {
        width: 100%;
        height: 100%;
        z-index: 9999;
    }

    table td,
    table th {
        max-width: 160px;
        width: 160px;
        height: auto;
        padding: 4px 8px;
        text-align: center;
        vertical-align: middle;
        word-wrap: break-word;
        white-space: normal;
    }

    main {
        padding: 10px 20px;
    }

    .head-caption {
        padding: 10px;
        right: 5px !important;
        top: 150px !important;
        font-size: 14px;
        font-weight: 600;
        text-align: right;
    }

    td,
    th {
        width: auto;
    }
    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }

    table.disclaimer-table {
        margin: auto;
        padding: auto;
        table-layout: fixed;
        page-break-inside: avoid;
        page-break-before: auto;
        width: 100%;
        border-collapse: separate;
        border: none !important;
    }

    table.disclaimer-table td {
        page-break-inside: avoid;
        page-break-before: auto;
        padding: 10px;
        text-align: left;
        vertical-align: top;
        font-size: 16px;
        line-height: 1.5;
        border: none;
    }

    .disclaimer-title {
        page-break-inside: avoid;
        page-break-before: auto;
        font-weight: bold;
        font-size: 20px;
        margin-bottom: 10px;
        display: block;
        text-align: left;
    }

    .disclaimer-text {
        page-break-inside: avoid;
        page-break-before: auto;
        margin: 0;
        font-size: 16px;
        line-height: 1.5;
        text-align: left;
    }

    .content {}

    .disclaimer-td {
        padding: 10px;
        text-align: left;
        vertical-align: top;
        font-size: 16px;
        line-height: 1.5;
        border: none;
    }

    .header {
        color: #ffffff;
        font-size: 18px;
        font-weight: 600;
        text-align: center;
        padding: 8px 10px;
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

    header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 150px;
    }



    .header-bottom {
        width: 100%;
        display: table;
        border-top: 2px solid #D3D3D3;
        border-bottom: 2px solid #D3D3D3;
        font-size: 14px;
        color: #333;
        padding: 10px 0;
    }

    .header-text {
        display: table-cell;
        text-align: left;
        width: 75%;
        vertical-align: middle;
    }

    .quote-number {
        display: table-cell;
        text-align: right;
        white-space: nowrap;
        width: 25%;
        padding-right: 20px;
        vertical-align: middle;
    }

    .header-text-highlight{
        font-weight: 700;
    }


</style>
</head>

<body>
    {{-- First Page --}}
    <img src="{{ public_path('images/quote_plans_pages/imcrm_plans_home_first_page.jpg') }}" class="page-image" />

    @php
        $websiteURL = config('constants.AFIA_WEBSITE_DOMAIN');
        $plans = [];
        $benefits = ['exclusion', 'inclusion', 'content', 'personalBelonging', 'building', 'additionalCover'];
        $vatPercentage =
            \App\Models\ApplicationStorage::where('key_name', \App\Enums\ApplicationStorageEnums::VAT_VALUE)->first()
                ->value ?? 0;

        $buyNow = 'Buy Now';
        $buyNowLink = $websiteURL . '/home-insurance/quote/' . $quote->uuid . '/payment/';
        if (isset($selectedPlanIds)) {
        } else {
            $selectedPlanIds = [];
        }

        foreach ($quotePlans->quotes->plans as &$quotePlan) {
            if (!isset($quotePlan->id) || !in_array($quotePlan->id, $planIds)) {
                continue;
            }

            $plans[$quotePlan->id] = $quotePlan;
            $quotePlan->total = 0;
            if (!isset($quotePlan->vat)) {
                $quotePlan->vat = 0;
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

            $quotePlan->exclusion = isset($quotePlan->benefits->exclusion)
                ? json_decode(collect($quotePlan->benefits->exclusion)->keyBy('code')->toJson())
                : [];

            $quotePlan->inclusion = isset($quotePlan->benefits->inclusion)
                ? json_decode(collect($quotePlan->benefits->inclusion)->keyBy('code')->toJson())
                : [];

            $quotePlan->content = isset($quotePlan->benefits->content)
                ? json_decode(collect($quotePlan->benefits->content)->keyBy('code')->toJson())
                : [];

            $quotePlan->personalBelonging = isset($quotePlan->benefits->personalBelonging)
                ? json_decode(collect($quotePlan->benefits->personalBelonging)->keyBy('code')->toJson())
                : [];

            $quotePlan->additionalCover = isset($quotePlan->benefits->additionalCover)
                ? json_decode(collect($quotePlan->benefits->additionalCover)->keyBy('code')->toJson())
                : [];

            $quotePlan->building = isset($quotePlan->benefits->building)
                ? json_decode(collect($quotePlan->benefits->building)->keyBy('code')->toJson())
                : [];

            $quotePlan->contentAndPersonalBelonging = isset($quotePlan->benefits->contentAndPersonalBelonging)
                ? json_decode(collect($quotePlan->benefits->contentAndPersonalBelonging)->keyBy('code')->toJson())
                : [];

            $quotePlan->fineArtAndCollectible = isset($quotePlan->benefits->fineArtAndCollectible)
                ? json_decode(collect($quotePlan->benefits->fineArtAndCollectible)->keyBy('code')->toJson())
                : [];

            $quotePlan->jewlleryAndValuable = isset($quotePlan->benefits->jewlleryAndValuable)
                ? json_decode(collect($quotePlan->benefits->jewlleryAndValuable)->keyBy('code')->toJson())
                : [];

            $discountPremium = $vat = [];

            $quotePlan->total = $quotePlan->discountPremium + $quotePlan->vat;

            foreach ($quotePlan->benefits as &$benefit) {
                $benefit = (object) $benefit;
                $benefit->value = 'Excluded';
                $benefit->price = 0;
                $benefit->vat = 0;
            }
        }

        $plans = array_filter(
            $plans,
            function ($planId) use ($planIds) {
                return in_array($planId, $planIds);
            },
            ARRAY_FILTER_USE_KEY,
        );

        $planIds = collect($plans)
            ->filter(function ($plan) {
                return !$plan->isDisabled && $plan->isRatingAvailable;
            })
            ->sortByDesc('isRenewal')
            ->pluck('id')
            ->toArray();
    @endphp

    {{-- PDF Page Header --}}
    <header>
        <div class="header">
            <div class="logo">
                <img class="im-logo" src="{{ getIMLogo(true, true) }}" alt="logo">
            </div>
            <div class="header-bottom">
                <!-- Left Side Text -->
                <div class="header-text">
                    <strong>Home insurance comparison table</strong>
                    &nbsp; | &nbsp;
                    Name: <span class="header-text-highlight">{{ $quote->first_name }} {{ $quote->last_name }}</span>
                    &nbsp; | &nbsp;
                    Property type: <span class="header-text-highlight">{{ $accommodationText }}</span>
                    &nbsp; | &nbsp;
                    Coverage type: <span class="header-text-highlight">{{ $coverageText }}</span>
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
        <table class="main-table" style="width: 100%; table-layout: auto;">
            <thead>
                <p style="margin-top:200px"></p>
                <tr>
                    <th class="bg-light-blue" rowspan="2">
                        <p class="quote-info raleway-font" style="font-size: 16px; font-weight:700;">Insurance company
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
                <tr>
                    <th class="bg-light-blue">
                        <p class="quote-info raleway-font" style="font-size: 16px; font-weight:700;">Plan name
                        </p>
                    </th>
                    @foreach ($planIds as $planId)
                        <th>
                            <p class="text-center" style="font-size: 16px">
                                {{ $plans[$planId]->name ?? '' }}
                            </p>
                        </th>
                    @endforeach
                </tr>

                {{-- rows for building, content and personal belonging value --}}
                @isset($homeQuoteFlags['contents_value_flag'])
                    @if($homeQuoteFlags['contents_value_flag'])
                        <tr>
                            <th class="bg-light-blue">
                                <p class="quote-info raleway-font" style="font-weight:700;"><b>Contents value</b></p>
                            </th>
                            @foreach ($planIds as $planId)
                                <th>
                                    <p class="text-center" style="font-size: 16px">
                                        {{ $flagValues['contents_value'] }}
                                    </p>
                                </th>
                            @endforeach
                        </tr>
                    @endif
                @endisset
                @isset($homeQuoteFlags['personal_belongings_flag'])
                    @if($homeQuoteFlags['personal_belongings_flag'])
                        <tr>
                            <th class="bg-light-blue">
                                <p class="quote-info raleway-font" style="font-weight:700;"><b>Personal belongings value</b></p>
                            </th>
                            @foreach ($planIds as $planId)
                                <th>
                                    <p class="text-center" style="font-size: 16px">
                                        {{ $flagValues['personal_belongings_value'] }}
                                    </p>
                                </th>
                            @endforeach
                        </tr>
                    @endif
                @endisset
                @isset($homeQuoteFlags['building_value_flag'])
                    @if($homeQuoteFlags['building_value_flag'])
                        <tr>
                            <th class="bg-light-blue">
                                <p class="quote-info raleway-font" style="font-weight:700;"><b>Building value</b></p>
                            </th>
                            @foreach ($planIds as $planId)
                                <th>
                                    <p class="text-center" style="font-size: 16px">
                                        {{ $flagValues['building_value'] }}
                                    </p>
                                </th>
                            @endforeach
                        </tr>
                    @endif
                @endisset
                {{-- rows for building, content and personal belonging value --}}
                <tr>
                    <th class="bg-light-blue">
                        <p class="quote-info raleway-font" style="font-weight:700;"><b>Gross Price</b></p>
                    </th>
                    @foreach ($planIds as $planId)
                        <th>
                            <p class="text-center" style="font-size: 16px">
                                AED {{ number_format($plans[$planId]->actualPremium ?? 0, 2) }}
                            </p>
                        </th>
                    @endforeach
                </tr>
                <tr>
                    <th class="bg-light-blue">
                        <p class="quote-info raleway-font" style="font-weight:700;"><b>Vat</b></p>
                    </th>
                    @foreach ($planIds as $planId)
                        <th>
                            <p class="text-center" style="font-size: 16px">
                                AED {{ number_format($plans[$planId]->vat ?? 0, 2) }}
                            </p>
                        </th>
                    @endforeach
                </tr>
                <tr>
                    <th class="bg-light-blue">
                        <p class="quote-info raleway-font" style="font-size: 16px; font-weight:700;"><b>Total Price (with VAT)</b></p>
                    </th>
                    @foreach ($planIds as $planId)
                        <th>
                            <p class="text-center" style="text-align: center; margin: 0; padding: 0;">
                                @php
                                    if (isset($plans[$planId])) {
                                        if (isset($selectedPlanIds) && in_array($planId, $selectedPlanIds)) {
                                            $buyNowFullLink = '#';
                                            $buyNowText = 'Selected';
                                        } else {
                                            $buyNowFullLink =
                                                $buyNowLink .
                                                '?providerCode=' .
                                                $plans[$planId]->providerCode .
                                                '&planId=' .
                                                $planId;
                                            $buyNowText = $buyNow;
                                        }

                                        $totalPrice =
                                            ($plans[$planId]->actualPremium ?? 0) + ($plans[$planId]->vat ?? 0);
                                        $totalPriceFormatted = number_format($totalPrice, 2);

                                        if ($plans[$planId]->actualPremium) {
                                            echo '<a target="_blank" class="btn-buy" href="' .
                                                $buyNowFullLink .
                                                '">' .
                                                $buyNowText .
                                                '<br>' .
                                                '<small style="font-size: 10px; font-weight: normal; line-height: 1.2;">AED</small>' .
                                                '<strong style="font-size: 16px; font-weight: bold; line-height: 1.2;">' .
                                                $totalPriceFormatted .
                                                '</strong>' .
                                                '</a>';
                                        } else {
                                            echo 'N/A';
                                        }
                                    } else {
                                        // Handle the case where $plans[$planId] does not exist
                                        echo 'N/A';
                                    }
                                @endphp
                            </p>
                        </th>
                    @endforeach
                </tr>
                <tr style="page-break-inside: avoid;">
                    <td class="no-border" colspan="{{ sizeof($planIds) + 1 }}">
                        <div class="spacer"></div>
                    </td>
                </tr>
            </thead>
            <tbody>
                {{-- Loop through the plan benefits only once --}}
                {{-- Sort benefits based on the order in $planCovers --}}
                @php
                    // plan covers for sorting order as they are not sorted in API as per Excel Sheet
                    $planCovers = [
                        'building',
                        'content',
                        'personalBelonging',
                        'contentAndPersonalBelonging',
                        'fineArtAndCollectible',
                        'jewlleryAndValuable',
                        'additionalCover',
                    ];
                @endphp
                @foreach ($planCovers as $cover)
                    {{-- Collect all benefit items for the current cover across all plans --}}
                    @php
                        $benefitItems = [];
                        foreach ($plans as $planId => $plan) {
                            if (isset($plan->benefits->$cover)) {
                                foreach ($plan->benefits->$cover as $benefit) {
                                    if (is_object($benefit) && property_exists($benefit, 'code')) {
                                        $benefitItems[$benefit->code] = $benefit;
                                    }
                                }
                            }
                        }
                    @endphp

                    {{-- Only display the heading and benefit items if there are any benefit items --}}
                    @if (!empty($benefitItems))
                        {{-- Benefit Title Row (appears only once for each benefit) --}}
                        <tr style="page-break-inside: avoid; page-break-before: auto; background-color: #2f8ec4;">
                            <td colspan="{{ count($planIds) + 1 }}">
                                <p class="text-left font-bold raleway-font" style="color: #ffffff; padding-left: 12px; font-weight: 700;">
                                    {{ ucwords(preg_replace('/([a-z0-9])([A-Z])/', '$1 $2', str_replace(['-', '_'], ' ', $cover))) }}
                                </p>
                            </td>
                        </tr>

                        {{-- Iterate through unique benefit items and display the benefit once with values for each plan --}}
                        @foreach ($benefitItems as $code => $item)
                            <tr style="page-break-inside: avoid;">
                                {{-- First column: Benefit Code --}}
                                <td style="background-color: #DBEEFF">
                                    <p class="text-left font-bold raleway-font" style="font-weight: 700;">
                                        {{ ucwords(preg_replace('/([a-z0-9])([A-Z])/', '$1 $2', str_replace(['-', '_'], ' ', $code))) }}
                                    </p>
                                </td>

                                {{-- Display values for each plan in subsequent columns --}}
                                @foreach ($planIds as $planId)
                                    @php
                                        $planBenefitCollection = collect($plans[$planId]->benefits->$cover ?? []);
                                        $matchingBenefit = $planBenefitCollection->firstWhere('code', $code);
                                        $planValue = $matchingBenefit->value ?? 'Excluded';
                                    @endphp
                                    <td>
                                        <p class="text-left">{{ $planValue }}</p>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach

                        <tr style="page-break-inside: avoid;">
                            <td class="no-border" colspan="{{ sizeof($planIds) + 1 }}">
                                <div class="spacer"></div>
                            </td>
                        </tr>
                    @endif
                @endforeach


                <tr>
                    <td colspan="{{ sizeof($planIds) + 1 }}" class="no-border text-center">
                        <a target="_blank" class="btn-all-quotes"
                            href="{{ $websiteURL . '/home-insurance/quote/' . $quote->uuid }}">View all quotes</a>
                    </td>
                </tr>

                <tr>
                    <td class="disclaimer-td" colspan="{{ sizeof($planIds) + 1 }}">
                        <span class="disclaimer-title">
                            Disclaimer and Material Information
                        </span>
                        <p class="disclaimer-text">
                            All quotes provided are indicative and based on the information you have supplied. While we
                            strive for accuracy in our comparison tables, discrepancies may occur. In such instances,
                            the
                            terms detailed in the insurer's policy wordings and schedules will take precedence over the
                            details provided by us. For the full text of the disclaimer and material information, please
                            refer to the quote. Policy wordings and schedules will prevail. Additionally, your final
                            price
                            may be adjusted following the insurer's review of your risk profile after payment. We
                            recommend
                            reviewing the policy wording carefully once issued to ensure it meets your coverage needs.
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
    </main>

    {{-- PDF Page Footer --}}
    <footer style="background-color: #1d83bc; color: #fff; text-align: center; padding: 5px;">
        <table class="tbl-footer" style="padding: 0 10px; margin: 0; width: 100%; border: none;">
            <tr>
                <td colspan="2" class="text-center">
                    <h4 style="font-size: 16px; margin: 0;">InsuranceMarket.ae is the registered trademark of AFIA
                        Insurance Brokerage Services LLC</h4>
                </td>
            </tr>
            <tr>
                <td class="text-left" style="font-size: 10px;">UAE Central Bank Registration number 85</td>
                <td class="text-right" style="font-size: 10px;">27th Floor, Control Tower, Motor City</td>
            </tr>
            <tr>
                <td class="text-left" style="font-size: 10px;">Registered member of the Emirates Insurance Association
                </td>
                <td class="text-right" style="font-size: 10px;">Dubai, United Arab Emirates, P.O Box 26423</td>
            </tr>
            <tr>
                <td class="text-left" style="font-size: 10px;">Department of Economy & Tourism in Dubai Trade License
                    number 238534</td>
                <td class="text-right" style="font-size: 10px;">Tel: <a href="tel:+800253733"
                        style="color: #fff; text-decoration: none;">800 ALFRED (800-253-733)</a></td>
            </tr>
            <tr>
                <td class="text-left" style="font-size: 10px;">Holder of Health Insurance Intermediary Permit ID
                    Number BRK-00003 from Dubai Health Authority</td>
                <td class="text-right" style="font-size: 10px;"><a href="https://insurancemarket.ae"
                        style="color: #fff; text-decoration: none;">www.insurancemarket.ae</a></td>
            </tr>
            <tr>
                <td class="text-left" style="font-size: 10px;">Registered member of Insurance Business Group under the
                    Dubai Chamber of Commerce and Industry</td>
            </tr>
        </table>
    </footer>

    {{-- Last Page --}}
    <img src="{{ public_path('images/quote_plans_pages/imcrm_plans_bike_last_page.jpg') }}"
        class="full-page-image" />
</body>

</html>
