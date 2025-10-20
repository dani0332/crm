<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Plans Comparison PDF</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&family=Raleway:wght@300;400;500;600;700&display=swap" rel="stylesheet">

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
            width: 100%;
            max-width: 120px;
            max-height: 100px;
            object-fit: contain;
            display: block;
            margin: 0 auto;
            position: relative;
        }

        @media (max-width: 768px) {
            .provider-logo {
                max-width: 80px;
                max-height: 60px;
            }
        }

        @media (max-width: 480px) {
            .provider-logo {
                max-width: 60px;
                max-height: 40px;
            }
        }


        .no-border {
            border: none;
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
            color: #ffffff
        }

        .text-underline {
            text-decoration: underline
        }

        .footer {
        position: fixed;
        bottom: 0;
        /* top: 50px !important; */
        left: 0;
        right: 0;
        width: 100%;
        background-color: #1d83bc;
        color: #ffffff;
        padding: 2px 2px 2px 2px;
        text-align: left;
        height: 165px !important;
    }

   
 


        .footer-content-1 {
        font-size: 8px !important;
        line-height: 0.7 !important;
        word-break: break-word;
        white-space: normal !important;
        width: 100%;
        box-sizing: border-box;
        align-items: left;
        text-align: left;
    }

    @media (max-width: 600px) {
        .footer-content-1 {
            font-size: 8px !important;
            padding-left: 2px;
            padding-right: 2px;
        }
    }

    .footer-content-2{
        font-size: 8px !important;
        align-items: center;
        text-align: center;
        line-height: 0.8 !important;
        word-break: break-word;
        white-space: normal !important;
        width: 100%;
        box-sizing: border-box;
        align-items: left;
        text-align: left;
    }
    @media (max-width: 600px) {
        .footer-content-2 {
            font-size: 9px !important;
            padding-left: 2px;
            padding-right: 2px;
        }
    }
</style>
</head>

<body>
    {{-- First Page --}}
    <img src="{{ public_path('images/quote_plans_pages/ecom_home/home_pdf_first_page_with_header_and_footer.jpg') }}" class="full-page-image" style="height: 90%;"/>
    @component('pdf.components.pdf_footer_section',['quote' => $quote,'ecomInsuranceLink'=>config('constants.ECOM_HOME_INSURANCE_QUOTE_URL').$quote->uuid])
      
     
@endcomponent
    <div style="page-break-after: always;"></div>
    {{-- Second Page --}}
    <img src="{{ public_path('images/quote_plans_pages/ecom_home/home_pdf_second_page_with_header.jpg') }}" class="full-page-image" style="height: 90%;"/>
    @component('pdf.components.pdf_footer_section',['quote' => $quote,'ecomInsuranceLink'=>config('constants.ECOM_HOME_INSURANCE_QUOTE_URL').$quote->uuid])
      
     
@endcomponent
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
        class="full-page-image" style="height: 90%;"/>
    @component('pdf.components.pdf_footer_section',['quote' => $quote,'ecomInsuranceLink'=>config('constants.ECOM_HOME_INSURANCE_QUOTE_URL').$quote->uuid])
      
     
@endcomponent
    <div style="page-break-after: always;"></div>

    {{-- Last Page --}}
    <img src="{{ public_path('images/quote_plans_pages/ecom_home/home_pdf_last_page_with_header.jpg') }}"
    class="full-page-image" style="height: 90%;"/>
    @component('pdf.components.pdf_footer_section',['quote' => $quote,'ecomInsuranceLink'=>config('constants.ECOM_HOME_INSURANCE_QUOTE_URL').$quote->uuid])
      
     
@endcomponent
</body>

</html>