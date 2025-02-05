<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Plans Comparison PDF</title>

    <style>
        * {
            font-family: "DejaVu Sans", sans-serif !important;
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

        .header {
            background: #1d83bc;
            color: #ffffff;
            font-size: 18px;
            font-weight: 600;
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
            margin: 0;
            padding-top: 20px;
            font-size: 18px;
            font-weight: 600;
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
            border-left: none;
            border-top: none;
        }

        .alfred img {
            width: 100%;
            max-width: 100px;
            height: 100px;
            display: block;
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

        .header {
            background: #1d83bc;
            color: #ffffff;
            font-size: 18px;
            font-weight: 600;
            text-align: center;
            padding: 8px 10px;
            width: 100%;
            height: 60px;
            max-height: 60px;
            background-image: url('{{ public_path('images/new-header-bg-image.png') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
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

        .header {
            color: #ffffff;
            font-size: 18px;
            font-weight: 600;
            text-align: center;
            padding: 8px 10px;
            width: 100%;
            height: 60px;
            max-height: 60px;
            background-image: url('{{ public_path('images/new-header-bg-image.png') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .content {}
    </style>
</head>

<body>
    {{-- First Page --}}
    <img src="{{ public_path('images/quote_plans_pages/imcrm_plans_home_first_page.jpg') }}" class="page-image" />

    @php
        $websitURL = config('constants.AFIA_WEBSITE_DOMAIN');
        $plans = [];
        $benefits = ['exclusion', 'inclusion', 'content', 'personalBelonging', 'building', 'additionalCover'];
        $vatPercentage =
            \App\Models\ApplicationStorage::where('key_name', \App\Enums\ApplicationStorageEnums::VAT_VALUE)->first()
                ->value ?? 0;

        $buyNow = 'Buy Now';
        $buyNowLink = $websitURL . '/home-insurance/quote/' . $quote->uuid . '/payment/=';
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
                return !$plan->isDisabled;
            })
            ->sortByDesc('isRenewal')
            ->pluck('id')
            ->toArray();
    @endphp

    {{-- PDF Page Header --}}
    <header>
        <div class="header"
            style="position: fixed; top: 0; left: 0; right: 0; z-index: 1000; color: #ffffff; padding: 8px 10px; height: 60px; background-image: url('{{ public_path('images/new-header-bg-image.png') }}'); background-size: cover; background-position: center; background-repeat: no-repeat;">
            <div class="logo"
                style="float: left; background-color: white; border-radius: 5px; padding: 5px 10px 5px 0px; height: 50px;">
                <img class="im-logo" src="{{ getIMLogo(true, true) }}" alt="logo"
                    style="max-height: 50px; height: 50px;" />
            </div>
            <h3
                style="margin: 0; padding-top: 5px; font-size: 18px; float: right; text-align: right; padding-right: 18px;">
                Your tailor made home insurance <br />comparison table
            </h3>
        </div>
    </header>

    {{-- PDF Page Inner Content --}}
    <main>
        <table class="main-table" style="width: 100%; table-layout: auto;">
            <thead>
                <p style="margin-top:95px"></p>
                <tr>
                    <th class="alfred" id="alfred-th" rowspan="2">
                        <img src="{{ public_path('images/home-alfred.png') }}" />
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
                    <th class="bg-light-blue" style="background-color: #DBEEFF !important">
                        <p class="quote-info" style="font-size: 16px">Home insurance comparison for:
                            <b>{{ $quote->first_name }}
                                {{ $quote->last_name }}</b>
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
                <tr>
                    <th class="bg-light-blue">
                        <p class="quote-info"><b>Gross Price</b></p>
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
                        <p class="quote-info"><b>Vat</b></p>
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
                        <p class="quote-info" style="font-size: 16px"><b>Total Price (with VAT)</b></p>
                    </th>
                    @foreach ($planIds as $planId)
                        <th>
                            <p class="text-center" style="text-align: center; margin: 0; padding: 0;">
                                @php
                                    if (isset($plans[$planId])) {
                                        if (isset($selectedPlanIds) && in_array($planId, $selectedPlanIds)) {
                                            $buyNowfullLink = '#';
                                            $buyNowText = 'Selected';
                                        } else {
                                            $buyNowfullLink =
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
                                                $buyNowfullLink .
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
                    @foreach ($plans[$planIds[0]]->benefits as $planBenefit => $benefit)
                        {{-- Only continue if the current planBenefit is in the $planCovers list --}}
                        @continue($cover !== $planBenefit)

                        {{-- Normalize the benefit data --}}
                        @php
                            // Ensure $benefit is an array
                            if (is_object($benefit)) {
                                $benefit = (array) $benefit;
                            }

                            // Convert stdClass benefits to an array if they exist
                            if (isset($benefit['stdClass']) && is_object($benefit['stdClass'])) {
                                $benefit = (array) $benefit['stdClass'];
                            }

                            // Filter out non-benefit keys
                            $benefitItems = array_filter(
                                $benefit,
                                fn($item) => is_object($item) && property_exists($item, 'code'),
                            );
                        @endphp

                        {{-- Skip if no valid benefits exist --}}
                        @if (empty($benefitItems))
                            @continue
                        @endif

                        {{-- Benefit Title Row --}}
                        <tr style="page-break-inside: avoid; page-break-before: auto; background-color: #2f8ec4;">
                            <td colspan="{{ count($planIds) + 1 }}">
                                <p class="text-left font-bold" style="color: #ffffff; padding-left: 12px;">
                                    {{ $planBenefit }}
                                </p>
                            </td>
                        </tr>

                        {{-- Iterate through valid benefit items --}}
                        @foreach ($benefitItems as $item)
                            <tr style="page-break-inside: avoid;">
                                {{-- First column: Benefit Code --}}
                                <td style="background-color: #DBEEFF">
                                    <p class="text-left font-bold">{{ $item->code ?? 'N/A' }}</p>
                                </td>

                                {{-- Subsequent columns: Benefit Value for each plan --}}
                                @foreach ($planIds as $planId)
                                    @php
                                        $planBenefitCollection = collect($plans[$planId]->benefits->$planBenefit ?? []);
                                        $matchingBenefit = $planBenefitCollection->firstWhere(
                                            'code',
                                            $item->code ?? '',
                                        );
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
                    @endforeach
                @endforeach

                <tr>
                    <td colspan="{{ sizeof($planIds) + 1 }}" class="no-border text-center">
                        <a target="_blank" class="btn-all-quotes"
                            href="{{ $websitURL . '/home-insurance/quote/' . $quote->uuid }}">View all quotes</a>
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="disclaimer-container"
            style="width: 100%; padding: 10px; box-sizing: border-box; margin-bottom: 20px;">
            <span class="section-title"
                style="font-weight: bold; font-size: 20px; display: block; margin-bottom: 10px;">Disclaimer and
                material information</span>
            <p class="section-text" style="font-size: 16px; line-height: 1.5; width: 100%; margin: 0;">
                All quotes provided are indicative and based on the information you have supplied. While we
                strive for accuracy in our comparison tables, discrepancies may occur. In such instances, the
                terms detailed in the insurer's policy wordings and schedules will take precedence over the
                details provided by us. For the full text of the disclaimer and material information, please
                refer to the quote. Policy wordings and schedules will prevail. Additionally, your final price
                may be adjusted following the insurer's review of your risk profile after payment. We recommend
                reviewing the policy wording carefully once issued to ensure it meets your coverage needs.
            </p>
        </div>
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
