<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Plans Comparison PDF</title>

    <style>
        @font-face {
            font-family: 'Prompt';
            src: url('{{ public_path('fonts/Prompt-Regular.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'Prompt';
            src: url('{{ public_path('fonts/Prompt-Bold.ttf') }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }

        * {
            font-family: 'Prompt', sans-serif;
        }

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
            line-height: 1.6;
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

        /* .text-heading {
            color: #ffffff;
            background-image: url('{{ public_path('images/new-heading-title-bg-image.png') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 50px;
            width: 100%;
            display: flex;
            align-items: center;
            padding: 0;
            margin: 0;
            font-size: 20px;
            font-weight: 900;
        } */

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
            bottom: 0px;
            left: 0px;
            right: 0px;
            padding: 0px;
            margin: 80px 0 0 0;
            background-color: #1d83bc;
            color: black;
            text-align: center;
            height: 160px;
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

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        table.tbl-footer tr td {
            padding: 0;
            margin: 0;
            width: auto;
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
            padding: 20px;
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
        $buyNowLink = $websitURL . '/home-insurance/quote/' . $quote->uuid . '/payment/?planId=';
        if (isset($hasAdultAndSeniorMember)) {
            if ($hasAdultAndSeniorMember == true) {
                $buyNow = 'Add to Cart';
                $buyNowLink = $websitURL . '/home-insurance/quote/' . $quote->uuid . '/?planAddToCart=';
            }
        }
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
                ? json_decode(
                    collect($quotePlan->benefits->exclusion)
                        ->keyBy('code')
                        ->toJson(),
                )
                : [];

            $quotePlan->inclusion = isset($quotePlan->benefits->inclusion)
                ? json_decode(
                    collect($quotePlan->benefits->inclusion)
                        ->keyBy('code')
                        ->toJson(),
                )
                : [];

            $quotePlan->content = isset($quotePlan->benefits->content)
                ? json_decode(
                    collect($quotePlan->benefits->content)
                        ->keyBy('code')
                        ->toJson(),
                )
                : [];

            $quotePlan->personalBelonging = isset($quotePlan->benefits->personalBelonging)
                ? json_decode(
                    collect($quotePlan->benefits->personalBelonging)
                        ->keyBy('code')
                        ->toJson(),
                )
                : [];

            $quotePlan->additionalCover = isset($quotePlan->benefits->additionalCover)
                ? json_decode(
                    collect($quotePlan->benefits->additionalCover)
                        ->keyBy('code')
                        ->toJson(),
                )
                : [];

            $quotePlan->building = isset($quotePlan->benefits->building)
                ? json_decode(
                    collect($quotePlan->benefits->building)
                        ->keyBy('code')
                        ->toJson(),
                )
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

        $features = [
            // Content
            ['code' => 'heading', 'title' => 'Contents'],
            [
                'code' => 'claimExcessDeductiblesForEachEveryLoss',
                'title' => 'Claim Excess / Deductibles for each & every loss',
                'type' => 'content',
            ],
            [
                'code' => 'accidentalDamageWhileInYourHome',
                'title' => 'Accidental Damage while in your home',
                'type' => 'content',
            ],
            [
                'code' => 'TheftVisibleViolentForcibleEntryOrExit',
                'title' => 'Theft (visible, violent, forcible entry or exit)',
                'type' => 'content',
            ],
            [
                'code' => 'fireExplosionLightningOrEarthquake',
                'title' => 'Fire, explosion, lightning or earthquake',
                'type' => 'content',
            ],
            [
                'code' => 'stormAndFlood',
                'title' => 'Storm and flood',
                'type' => 'content',
            ],
            [
                'code' => 'spoilageOfFoodInFreezer',
                'title' => 'Spoilage of food in freezer',
                'type' => 'content',
            ],
            [
                'code' => 'contentsTemporarilyRemoved',
                'title' => 'Contents temporarily removed',
                'type' => 'content',
            ],
            [
                'code' => 'singleArticleLimitForContentsSal',
                'title' => 'Single Article Limit for contents (SAL)',
                'type' => 'content',
            ],
            [
                'code' => 'keysAndLocks',
                'title' => 'Keys and Locks',
                'type' => 'content',
            ],
            [
                'code' => 'contentsInTheOpen',
                'title' => 'Contents in the Open',
                'type' => 'content',
            ],

            ['code' => 'spacer', 'title' => ''],

            // Personal Belongings
            ['code' => 'heading', 'title' => 'Personal Belongings'],
            [
                'code' => 'claimExcessDeductiblesForEachEveryLoss',
                'title' => 'Claim Excess / Deductibles for each & every loss',
                'type' => 'personalBelonging',
            ],
            [
                'code' => 'lossOfDocuments',
                'title' => 'Loss of documents',
                'type' => 'personalBelonging',
            ],
            [
                'code' => 'valuablesAndPortableEquipment',
                'title' => 'Valuables and portable equipment',
                'type' => 'personalBelonging',
            ],
            [
                'code' => 'jewellery',
                'title' => 'Jewellery',
                'type' => 'personalBelonging',
            ],
            [
                'code' => 'singleArticleLimitForPersonalBelongingsSal',
                'title' => 'Single article limit for personal belongings (SAL)',
                'type' => 'personalBelonging',
            ],
            [
                'code' => 'personalMoneyAndCreditCards',
                'title' => 'Personal money and credit cards',
                'type' => 'personalBelonging',
            ],

            ['code' => 'spacer', 'title' => ''],

            // Building
            ['code' => 'heading', 'title' => 'Building'],
            [
                'code' => 'claimExcessDeductibles',
                'title' => 'Claim Excess / Deductibles',
                'type' => 'building',
            ],
            [
                'code' => 'ownersLiabilityToThePublic',
                'title' => 'Owner\'s liability to the public',
                'type' => 'building',
            ],
            [
                'code' => 'breakageOfFixedGlassAndSanitaryFixtures',
                'title' => 'Breakage of fixed glass and sanitary fixtures',
                'type' => 'building',
            ],
            [
                'code' => ' damageToServicesPipesAndCables',
                'title' => ' Damage to Services (Pipes and Cables)',
                'type' => 'building',
            ],

            ['code' => 'spacer', 'title' => ''],

            // Additional Cover
            ['code' => 'heading', 'title' => 'Additional Cover'],
            [
                'code' => 'legalAssistance',
                'title' => 'Legal assistance',
                'type' => 'additionalCover',
            ],
            [
                'code' => 'tenantsLiability',
                'title' => 'Tenant\'s liability',
                'type' => 'additionalCover',
            ],
            [
                'code' => 'occupiersPersonalAndEmployersLiability',
                'title' => 'Occupiers personal and employers liability',
                'type' => 'additionalCover',
            ],
            [
                'code' => 'fatalInjuryBenefit',
                'title' => 'Fatal injury benefit',
                'type' => 'additionalCover',
            ],
            [
                'code' => 'lossOfRentOrCostOfAlternativeAccommodationForContents',
                'title' => 'Loss of rent or cost of alternative accommodation for Contents',
                'type' => 'additionalCover',
            ],
            [
                'code' => 'lossOfRentOrCostOfAlternativeAccommodationForBuilding',
                'title' => 'Loss of rent or cost of alternative accommodation for Building',
                'type' => 'additionalCover',
            ],
            [
                'code' => 'homeAssistance',
                'title' => 'Home assistance',
                'type' => 'additionalCover',
            ],
            [
                'code' => 'visitorsPersonalEffects',
                'title' => 'Visitor\'s personal effects',
                'type' => 'additionalCover',
            ],

            ['code' => 'spacer', 'title' => ''],

            // For High Value Plans
            // Content And Personal Belonging
            ['code' => 'heading', 'title' => 'Content And Personal Belonging'],
            [
                'code' => 'claimExcessDeductibles',
                'title' => 'Claim Excess / Deductibles',
                'type' => 'contentAndPersonalBelonging',
            ],
            [
                'code' => 'fursAndSportingEquipmentMarquees',
                'title' => 'Furs & Sporting equipment, Marquees',
                'type' => 'contentAndPersonalBelonging',
            ],
            [
                'code' => 'newAcquisitionsCover',
                'title' => 'New Acquisitions cover',
                'type' => 'contentAndPersonalBelonging',
            ],
            [
                'code' => 'presentsAndGift',
                'title' => 'Presents & Gift',
                'type' => 'contentAndPersonalBelonging',
            ],
            [
                'code' => 'valuablesCoinsStampsMedalsGoldSilverAndPlatedArticles',
                'title' => 'Valuables - Coins stamps, medals, gold, silver and plated articles',
                'type' => 'contentAndPersonalBelonging',
            ],
            [
                'code' => 'TheftForcibleEntryOrExit',
                'title' => 'Theft (Forcible entry or exit)',
                'type' => 'contentAndPersonalBelonging',
            ],
            [
                'code' => 'stormFloodFireExplosionLightningOrEarthquake',
                'title' => 'Storm, flood, fire, explosion, lightning or earthquake',
                'type' => 'contentAndPersonalBelonging',
            ],
            [
                'code' => 'spoilageOfFoodInFreezer',
                'title' => 'Spoilage of food in freezer',
                'type' => 'contentAndPersonalBelonging',
            ],
            [
                'code' => 'replacementOfKeysAndLocks',
                'title' => 'Replacement of keys and locks',
                'type' => 'contentAndPersonalBelonging',
            ],
            [
                'code' => 'contentsInTheOpen',
                'title' => 'Contents in the Open',
                'type' => 'contentAndPersonalBelonging',
            ],

            ['code' => 'spacer', 'title' => ''],

            // Fine Art And Collectible
            ['code' => 'heading', 'title' => 'Fine Art And Collectible'],
            [
                'code' => 'claimExcessDeductibles',
                'title' => 'Claim Excess / Deductibles',
                'type' => 'fineArtAndCollectible',
            ],
            [
                'code' => 'emergencyEvacuation',
                'title' => 'Emergency evacuation',
                'type' => 'fineArtAndCollectible',
            ],
            [
                'code' => 'defectiveTitle',
                'title' => 'Defective title',
                'type' => 'fineArtAndCollectible',
            ],
            [
                'code' => 'nonSpecifiedPairsAndSets',
                'title' => 'Non specified pairs and sets',
                'type' => 'fineArtAndCollectible',
            ],
            [
                'code' => 'newAcquisitionsCover',
                'title' => 'New Acquisitions cover',
                'type' => 'fineArtAndCollectible',
            ],
            [
                'code' => 'singleArticleLimitForUnspecifiedItems',
                'title' => 'Single article limit for unspecified items',
                'type' => 'fineArtAndCollectible',
            ],
            [
                'code' => 'sumInsured',
                'title' => 'Sum Insured',
                'type' => 'fineArtAndCollectible',
            ],

            ['code' => 'spacer', 'title' => ''],

            // Jewllery And Valuable
            ['code' => 'heading', 'title' => 'Jewllery And Valuable'],
            [
                'code' => 'claimExcessDeductibles',
                'title' => 'Claim Excess / Deductibles',
                'type' => 'jewlleryAndValuable',
            ],
            [
                'code' => 'nonSpecifiedPairsAndSets',
                'title' => 'Non specified pairs and sets',
                'type' => 'jewlleryAndValuable',
            ],
            [
                'code' => 'newAcquisitionsCover',
                'title' => 'New Acquisitions cover',
                'type' => 'jewlleryAndValuable',
            ],
            [
                'code' => 'singleArticleLimitForUnspecifiedItems',
                'title' => 'Single article limit for unspecified items',
                'type' => 'jewlleryAndValuable',
            ],
            [
                'code' => 'sumInsured',
                'title' => 'Sum Insured',
                'type' => 'jewlleryAndValuable',
            ],

            ['code' => 'spacer', 'title' => ''],
        ];

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
                style="margin: 0; padding-top: 10px; font-size: 18px; float: right; text-align: right; padding-right: 18px;">
                Your tailor made home insurance <br />comparison table
            </h3>
            {{-- <h3
                style="margin: 0; padding-top: 10px; font-size: 18px; float: right; text-align: right; padding-right: 18px;">
                Your tailor made home insurance comparison table <br /> with isurance reference ID: {{ $quote->code }}
            </h3> --}}
        </div>
        {{-- <p class="head-caption"
            style="position: fixed; top: 60px; right: 5px; font-size: 14px; font-weight: bold; text-align: right;">
            Your home insurance reference ID is <span class="text-blue">{{ $quote->code }}</span>
        </p> --}}
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
                                            $buyNowfullLink = $buyNowLink . $planId;
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
                @php $featCount = 0 @endphp
                @foreach ($features as $index => $feature)
                    @if (@$feature['code'] == 'heading')
                        @php
                            $hasDataBelow = false;
                            for ($i = $index + 1; $i < count($features); $i++) {
                                if ($features[$i]['code'] == 'heading' || $features[$i]['code'] == 'spacer') {
                                    break;
                                }

                                foreach ($planIds as $planId) {
                                    if (
                                        isset($plans[$planId]->{$features[$i]['type']}->{$features[$i]['code']}->value)
                                    ) {
                                        $hasDataBelow = true;
                                        break 2;
                                    }
                                }
                            }
                        @endphp

                        @if ($hasDataBelow)
                            <tr style="page-break-inside: avoid; page-break-before: auto; background-color: #2f8ec4;">
                                <td colspan="{{ sizeof($planIds) + 1 }}">
                                    <p class="text-left !font-bold" style="color: #ffffff; padding-left: 12px;">
                                        {{ $feature['title'] }}</p>
                                </td>
                            </tr>
                        @endif
                        @php continue; @endphp
                    @endif

                    @if (@$feature['code'] == 'spacer')
                        <tr style="page-break-inside: avoid;">
                            <td class="no-border" colspan="{{ sizeof($planIds) + 1 }}">
                                <div class="spacer"></div>
                            </td>
                        </tr>
                        @php continue; @endphp
                    @endif

                    <?php $planIterate = 0; ?>
                    <tr class="<?php echo 'row_' . $featCount; ?> {{ $feature['row_class'] ?? '' }}"
                        style="page-break-inside: avoid;">
                        <td style="background-color: #DBEEFF">
                            <p class="text-left font-bold">{{ @$feature['title'] }}</p>
                        </td>
                        @foreach ($planIds as $planId)
                            <?php $return_value = ''; ?>
                            @if (isset($feature['type']))
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
    <footer style="background-color: #1d83bc; color: #fff; text-align: center; padding: 10px;">
        <table class="tbl-footer" style="padding: 5px 10px; margin: 0; width: 100%; border: none;">
            <tr>
                <td colspan="2" class="text-center">
                    <h4 style="font-size: 20px; margin: 0;">InsuranceMarket.ae is the registered trademark of AFIA
                        Insurance Brokerage Services LLC</h4>
                </td>
            </tr>
            <tr>
                <td class="text-left" style="font-size: 16px;">UAE Central Bank Registration number 85</td>
                <td class="text-right" style="font-size: 16px;">27th Floor, Control Tower, Motor City</td>
            </tr>
            <tr>
                <td class="text-left" style="font-size: 16px;">Registered member of the Emirates Insurance Association
                </td>
                <td class="text-right" style="font-size: 16px;">Dubai, United Arab Emirates, P.O Box 26423</td>
            </tr>
            <tr>
                <td class="text-left" style="font-size: 16px;">Department of Economy & Tourism in Dubai Trade License
                    number 238534</td>
                <td class="text-right" style="font-size: 16px;">Tel: <a href="tel:+800253733"
                        style="color: #fff; text-decoration: none;">800 ALFRED (800-253-733)</a></td>
            </tr>
            <tr>
                <td class="text-left" style="font-size: 16px;">Holder of Health Insurance Intermediary Permit ID
                    Number BRK-00003 from Dubai Health Authority</td>
                <td class="text-right" style="font-size: 16px;"><a href="https://insurancemarket.ae"
                        style="color: #fff; text-decoration: none;">www.insurancemarket.ae</a></td>
            </tr>
            <tr>
                <td class="text-left" style="font-size: 16px;">Registered member of Insurance Business Group under the
                    Dubai Chamber of Commerce and Industry</td>
            </tr>
        </table>
    </footer>

    {{-- Last Page --}}
    <img src="{{ public_path('images/quote_plans_pages/imcrm_plans_bike_last_page.jpg') }}"
        class="full-page-image" />
</body>

</html>
