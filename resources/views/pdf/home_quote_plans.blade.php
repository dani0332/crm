<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Plans Comparison PDF</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&family=Raleway:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
    * {
        font-family: 'Prompt', sans-serif !important;
    }

    .raleway-font {
        font-family: 'Raleway', sans-serif !important;
    }

    @page {
        margin: 0;
        padding: 0;
        margin-bottom: 170px;
    }

    html {
        line-height: 1;
        margin: 0;
        padding: 0;
    }

    body {
        line-height: 1;
        margin: 0;
        padding: 0;
        font-size: 12px;
        font-weight: 400;
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
        text-indent: 0;
        border-color: #bfbfbf;
        color: #333333;
        border-spacing: 0;
        border-radius: 10px;
        border-collapse: collapse;
        font-size: 12px;
        table-layout: auto;
    }

    .not-full {
        width: auto;
        margin: 0 auto;
    }

    .is-full {
        width: 100%;
        min-width: 100%;
        max-width: 100%;
    }

    tbody {
        margin-bottom: 170px;
    }

    tbody>tr>td {
        border: 1px solid #bfbfbf;
    }

    thead>tr>th {
        border: 1px solid #bfbfbf;
    }

    td>p,
    th>p {
        padding: 2px;
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
        color: #5B5F60;
    }

    .bg-light-blue p {
        padding: 1px !important;
    }

    .text-black {
        color: #000000;
    }

    .provider {
        border: 1px solid #bfbfbf;
        font-size: 14px;
        line-height: 1;
        font-weight: 400;
        color: #4ea4a8;
        vertical-align: middle;
        max-height: 35px;
        height: 35px;
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
        font-size: 14px;
        font-weight: 400;
        text-align: left;
        padding: 0;
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
        font-size: 14px;
        font-weight: 400;
    }

    .btn-all-quotes {
        background-color: #1d83bc;
        color: #ffffff;
        padding-left: 70px;
        padding-right: 70px;
        padding-bottom: 7px;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        font-size: 16px;
        font-weight: 600;
        border-radius: 5px;
        position: relative;
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

    .btn-buy:hover {
        background-color: #d7fbd0;
    }

    .provider-logo {
        width: 45px;
        height: auto;
    }

    .no-border {
        border: none;
    }

    th.provider-name {
        padding: 0;
        margin: 0;
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

    table td,
    table th {
        max-width: 160px;
        width: 160px;
        height: auto;
        padding: 2px;
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
        font-size: 14px;
        line-height: 1;
        border: none;
    }

    .disclaimer-text {
        page-break-inside: avoid;
        page-break-before: auto;
        margin: 0;
        font-size: 14px;
        line-height: 1;
        text-align: left;
    }

    .content {}

    .disclaimer-td {
        padding: 10px;
        text-align: left;
        vertical-align: top;
        font-size: 14px;
        line-height: 1;
        border: none;
    }

    .header {
        color: #ffffff;
        font-size: 14px;
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



    /* Bottom Header Container */
    .header-bottom {
        width: 100%;
        display: table;
        border-top: 2px solid #D3D3D3;
        border-bottom: 2px solid #D3D3D3;
        font-size: 14px;
        color: #5B5F60;
        padding: 10px 0;
    }

    /* Left Side Text */
    .header-text {
        display: table-cell;
        text-align: left;
        width: 75%;
        vertical-align: middle;
    }

    /* Right Side Quote Number */
    .quote-number {
        display: table-cell;
        text-align: right;
        white-space: nowrap;
        width: 25%;
        padding-right: 20px;
        vertical-align: middle;
    }

    .quote-number strong {
        font-weight: 600; /* Reduce boldness */
    }

    /* Highlighted Text */
    .header-text-highlight {
        font-weight: 600;
    }

    /* Separator Styling */
    .separator {
        color: #D3D3D3; /* Match border color */
        font-weight: normal; /* Ensure it's not bold */
        padding: 0 5px; /* Adjust spacing */
    }

    .footer {
        /* position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        width: 100%;
        background-color: #1d83bc;
        color: #ffffff;
        padding: 10px;
        text-align: center; */
        height: 140px !important;
    }

    
</style>
</head>

<body>
    {{-- First Page --}}
    <img src="{{ public_path('images/quote_plans_pages/ecom_home/home_pdf_first_page_with_header_and_footer.jpg') }}" class="full-page-image" style="height: 100%;"/>
    <div style="page-break-after: always;"></div>
    {{-- Second Page --}}
    <img src="{{ public_path('images/quote_plans_pages/ecom_home/home_pdf_second_page_with_header.jpg') }}" class="full-page-image" />
    <div style="page-break-after: always;"></div>

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

            $quotePlan->jewelleryAndValuable = isset($quotePlan->benefits->jewelleryAndValuable)
                ? json_decode(collect($quotePlan->benefits->jewelleryAndValuable)->keyBy('code')->toJson())
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


            if(count($planIds) == 5){
                $tableClass = 'is-full';
            }
            else{
                $tableClass = 'not-full';
            }
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
                    <strong class="raleway-font" style="font-weight: 600 !important;">Home insurance comparison table</strong>
                    <span class="separator">|</span>
                    Name: <span class="header-text-highlight">{{ $quote->first_name }} {{ $quote->last_name }}</span>
                    <span class="separator">|</span>
                    Property type: <span class="header-text-highlight">{{ $accommodationText }}</span>
                    <span class="separator">|</span>
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
        <table class="main-table {{ $tableClass }}">
            <thead>
                <p style="margin-top:200px"></p>
                <tr>
                    <th class="bg-light-blue" rowspan="2">
                        <p class="quote-info raleway-font" style="font-weight:700;">Insurance company
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
                        <p class="quote-info raleway-font" style="font-weight:700;">Plan name
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

                {{-- rows for building, content and personal belonging value --}}
                @isset($homeQuoteFlags['contents_value_flag'])
                    @if($homeQuoteFlags['contents_value_flag'])
                        <tr>
                            <th class="bg-light-blue">
                                <p class="quote-info raleway-font" style="font-weight:700;">Contents value</p>
                            </th>
                            @foreach ($planIds as $planId)
                                <th>
                                    <p class="text-center" style="font-size: 14px">
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
                                <p class="quote-info raleway-font" style="font-weight:700;">Personal belongings value</p>
                            </th>
                            @foreach ($planIds as $planId)
                                <th>
                                    <p class="text-center" style="font-size: 14px">
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
                                <p class="quote-info raleway-font" style="font-weight:700;">Building value</p>
                            </th>
                            @foreach ($planIds as $planId)
                                <th>
                                    <p class="text-center" style="font-size: 14px">
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
                        <p class="quote-info raleway-font" style="font-weight:700;">Gross price</p>
                    </th>
                    @foreach ($planIds as $planId)
                        <th>
                            <p class="text-center" style="font-size: 14px">
                                AED {{ number_format($plans[$planId]->actualPremium ?? 0, 2) }}
                            </p>
                        </th>
                    @endforeach
                </tr>
                <tr>
                    <th class="bg-light-blue">
                        <p class="quote-info raleway-font" style="font-weight:700;">Vat</p>
                    </th>
                    @foreach ($planIds as $planId)
                        <th>
                            <p class="text-center" style="font-size: 14px">
                                AED {{ number_format($plans[$planId]->vat ?? 0, 2) }}
                            </p>
                        </th>
                    @endforeach
                </tr>
                <tr>
                    <th class="bg-light-blue">
                        <p class="quote-info raleway-font" style="font-size: 14px; font-weight:700;">Total price (with VAT)</p>
                    </th>
                    @foreach ($planIds as $planId)
                        <th>
                            <p class="text-center" style="text-align: center; margin: 0; padding: 2px;">
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
                                                '<small style="font-size: 10px; font-weight: normal;">AED </small>' .
                                                '<strong style="font-size: 14px; font-weight: bold;">' .
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
                        'content',
                        'personalBelonging',
                        'contentAndPersonalBelonging',
                        'fineArtAndCollectible',
                        'jewelleryAndValuable',
                        'building',
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
                        <tr style="page-break-inside: avoid; page-break-before: auto; background-color: #1D83BC;">
                            <td colspan="{{ count($planIds) + 1 }}">
                                <p class="text-left font-bold raleway-font" style="color: #ffffff; padding-left: 2px; font-weight: 700;">
                                    @php
                                        $firstBenefit = reset($benefitItems);
                                        echo $firstBenefit->heading ?? $cover;
                                    @endphp
                                </p>
                            </td>
                        </tr>

                        {{-- Iterate through unique benefit items and display the benefit once with values for each plan --}}
                        @foreach ($benefitItems as $code => $item)
                            <tr style="page-break-inside: avoid;">
                                {{-- First column: Benefit Code --}}
                                <td style="background-color: #DBEEFF; color: #5B5F60">
                                    <p class="text-left font-bold raleway-font" style="padding-left: 2px; font-weight: 700;">
                                        {{ $item->text }}
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
                                        <p class="text-center">{{ $planValue }}</p>
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
                            href="{{ $websiteURL . '/home-insurance/quote/' . $quote->uuid }}">
                            View all quotes
                            <div style="position: absolute; right: 50px; top: 8px;">
                                <img src="{{ public_path('images/quote_plans_pages/ecom_home/open_in_new_icon.png') }}" 
                                    style="width: 16px; height: 16px; vertical-align: baseline; display: block;">
                            </div>
                        </a>
                    </td>
                </tr>

                <tr>
                    <td class="disclaimer-td" colspan="{{ sizeof($planIds) + 1 }}">
                        <p class="disclaimer-text">
                            <strong>Disclaimer: </strong>Quotes are based on the details you provided and may change after the insurer reviews
                            you profile. If there are differences, the insurer's policy terms will apply.
                            Please check your policy once issued to ensure it meets your needs.
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
    </main>

   
    {{-- PDF Page Footer Section --}}
    @component('pdf.components.pdf_footer_section',['quote' => $quote,'ecomInsuranceLink'=>config('constants.ECOM_HOME_INSURANCE_QUOTE_URL').$quote->uuid])
      
    @endcomponent
{{-- End of PDF Page Footer Section --}}
    

    {{-- Second Last Page --}}
    <img src="{{ public_path('images/quote_plans_pages/ecom_home/home_pdf_second_last_page_with_header.jpg') }}"
        class="full-page-image" />
    <div style="page-break-after: always;"></div>

    {{-- Last Page --}}
    <img src="{{ public_path('images/quote_plans_pages/ecom_home/home_pdf_last_page_with_header.jpg') }}"
    class="full-page-image" />
</body>

</html>