<!DOCTYPE html>
<html lang="en">

   <head>
      <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
      <title>Generating PDF</title>
  
      <style>
        html {
          line-height: 1.5;
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
          margin: 64px auto;
          border-spacing: 0;
        }
        .header {
          background: #ddfdfc;
          color: #333393;
          font-size: 26px;
          text-align: center;
          padding: 16px 0;
          width: 100%;
        }
        tbody > tr > td {
          border: 1px solid #bfbfbf;
        }
        td > p {
          padding: 4px;
          font-size: 15px;
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
        .vender {
          border: 1px solid #bfbfbf;
          font-size: 18px;
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
      </style>
    </head>

<body>
    <div class="font">
         <div class="header">
            <h2>Your Tailor Made <br />Car Insurance Comparison Table</h2>
         </div>
        @php
            $count = 0;
            $listQuotePlansCount = count($planIds);
        @endphp
        @foreach ($listQuotePlans as $listQuotePlan)
            @if (in_array($listQuotePlan->id, $planIds))
                @php
                    if (isset($listQuotePlan->benefits->inclusion)) {
                        foreach ($listQuotePlan->benefits->inclusion as $inclusion) {
                            ${'inclusion_' . $inclusion->code}[$count] = $inclusion;
                        }
                    }
                    if (isset($listQuotePlan->benefits->exclusion)) {
                        foreach ($listQuotePlan->benefits->exclusion as $exclusion) {
                            ${'exclusion_' . $exclusion->code}[$count] = $exclusion;
                        }
                    }
                    if (isset($listQuotePlan->benefits->feature)) {
                        foreach ($listQuotePlan->benefits->feature as $feature) {
                            ${'feature_' . $feature->code}[$count] = $feature;
                        }
                    }
                    if (isset($listQuotePlan->benefits->roadSideAssistance)) {
                        foreach ($listQuotePlan->benefits->roadSideAssistance as $roadSideAssistance) {
                            ${'roadSideAssistance_' . $roadSideAssistance->code}[$count] = $roadSideAssistance;
                        }
                    }
                    
                    $totalSelectedAddonsPriceWithVat = 0;
                    foreach ($listQuotePlan->addons as $listQuotePlanAddon) {
                        foreach ($listQuotePlanAddon->carAddonOption as $carAddonOption) {
                            ${'addons_' . $listQuotePlanAddon->code}[$count] = $carAddonOption;
                        }
                    }
                    $providerName[$count] = ucwords($listQuotePlan->providerName);
                    $providerLogo[$count] = App\Models\InsuranceProvider::where('code', $listQuotePlan->providerCode)->pluck('logo_url')->first();

                    $discountPremium[$count] = isset($listQuotePlan->discountPremium) ? $listQuotePlan->discountPremium : 0;
                    $vat[$count] = isset($listQuotePlan->vat) ? $listQuotePlan->vat : 0;
                    ++$count;
                @endphp
            @endif
        @endforeach
        <div class="container mx-auto px-12 pb-24">
            <table class="table-fixed text-center">
                <thead>
                    <tr>
                        <th class="quote-info">
                            <div>
                                <p>Comparison of Car Insurance Quote For: {{ auth()->user()->name }}</p>
                            </div>
                        </th>
                        @if ($listQuotePlansCount >= 1)
                            <th class="vender">
                                <div class="mx-auto my-2 h-20 w-20 rounded-full bg-blue-50">
                                    <p class="relative top-[40%] m-auto text-xs">LOGO</p>
                                </div>
                                <div class="mb-2">{{ isset($providerName[0]) ? $providerName[0] : "" }}</div>
                            </th>
                        @endif
                        @if ($listQuotePlansCount >= 2)
                            <th class="vender">
                                <div class="mx-auto my-2 h-20 w-20 rounded-full bg-blue-50">
                                    <p class="relative top-[40%] m-auto text-xs">LOGO</p>
                                </div>
                                <div class="mb-2">{{ isset($providerName[1]) ? $providerName[1] : "" }}</div>
                            </th>
                        @endif
                        @if ($listQuotePlansCount >= 3)
                            <th class="vender">
                                <div class="mx-auto my-2 h-20 w-20 rounded-full bg-blue-50">
                                    <p class="relative top-[40%] m-auto text-xs">LOGO</p>
                                </div>
                                <div class="mb-2">{{ isset($providerName[2]) ? $providerName[2] : "" }}</div>
                            </th>
                        @endif
                        @if ($listQuotePlansCount >= 4)
                            <th class="vender">
                                <div class="mx-auto my-2 h-20 w-20 rounded-full bg-blue-50">
                                    <p class="relative top-[40%] m-auto text-xs">LOGO</p>
                                </div>
                                <div class="mb-2">{{ isset($providerName[3]) ? $providerName[3] : "" }}</div>
                            </th>
                        @endif
                        @if ($listQuotePlansCount >= 5)
                              <th class="vender">
                                 <div class="mx-auto my-2 h-20 w-20 rounded-full bg-blue-50">
                                    <p class="relative top-[40%] m-auto text-xs">LOGO</p>
                                 </div>
                                 <div class="mb-2">{{ isset($providerName[4]) ? $providerName[4] : "" }}</div>
                              </th>
                        @endif
                        @if ($listQuotePlansCount >= 6)
                           <th class="vender">
                                 <div class="mx-auto my-2 h-20 w-20 rounded-full bg-blue-50">
                                    <p class="relative top-[40%] m-auto text-xs">LOGO</p>
                                 </div>
                                 <div class="mb-2">{{ isset($providerName[5]) ? $providerName[5] : "" }}</div>
                           </th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    <tr class="green-box">
                        <td>
                            <p class="text-left">EXACT VEHICLE AND VALUE (INSURER SPECIFIC)</p>
                        </td>
                        @if ($listQuotePlansCount >= 1)
                        <td>
                           <p>{!!  isset($exclusion_perils[0]) ? $exclusion_perils[0]->value : '&cross;'  !!}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 2)
                        <td>
                           <p>{!!  isset($exclusion_perils[1]) ? $exclusion_perils[1]->value : '&cross;'  !!}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 3)
                        <td>
                           <p>{!!  isset($exclusion_perils[2]) ? $exclusion_perils[2]->value : '&cross;'  !!}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 4)
                        <td>
                           <p>{!!  isset($exclusion_perils[3]) ? $exclusion_perils[3]->value : '&cross;'  !!}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 5)
                        <td>
                           <p>{!!  isset($exclusion_perils[4]) ? $exclusion_perils[4]->value : '&cross;'  !!}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 6)
                        <td>
                           <p>{!!  isset($exclusion_perils[5]) ? $exclusion_perils[5]->value : '&cross;'  !!}</p>
                        </td>
                        @endif
                    </tr>
                    <tr>
                        <td colspan="1">
                            <p class="text-left font-medium text-indigo-800">BENEFITS</p>
                        </td>
                        <td colspan="{{ $listQuotePlansCount }}" class="bg-indigo-100"></td>
                    </tr>

                    <tr>
                        <td>
                            <p class="text-left">Loss or Damage to the Insured Vehicle</p>
                        </td>
                        @if ($listQuotePlansCount >= 1)
                           <td>
                              {!! isset($exclusion_damage[0]) ? '<p class="text-2xl">&check;</p>' : '<p class="text-2xl">&cross;</p>' !!}                            
                           </td>
                        @endif
                        @if ($listQuotePlansCount >= 2)
                           <td>
                              {!! isset($exclusion_damage[1]) ? '<p class="text-2xl">&check;</p>' : '<p class="text-2xl">&cross;</p>' !!}                            
                           </td>
                        @endif
                        @if ($listQuotePlansCount >= 3)
                           <td>
                              {!! isset($exclusion_damage[2]) ? '<p class="text-2xl">&check;</p>' : '<p class="text-2xl">&cross;</p>' !!}                            
                           </td>
                        @endif
                        @if ($listQuotePlansCount >= 4)
                           <td>
                              {!! isset($exclusion_damage[3]) ? '<p class="text-2xl">&check;</p>' : '<p class="text-2xl">&cross;</p>' !!}                            
                           </td>
                        @endif
                        @if ($listQuotePlansCount >= 5)
                           <td>
                              {!! isset($exclusion_damage[4]) ? '<p class="text-2xl">&check;</p>' : '<p class="text-2xl">&cross;</p>' !!}                            
                           </td>
                        @endif
                        @if ($listQuotePlansCount >= 6)
                           <td>
                              {!! isset($exclusion_damage[5]) ? '<p class="text-2xl">&check;</p>' : '<p class="text-2xl">&cross;</p>' !!}                            
                           </td>
                        @endif
                    </tr>

                    <tr>
                        <td>
                            <p class="text-left">Third Party Property Liability</p>
                        </td>
                        @if ($listQuotePlansCount >= 1)
                        <td>
                            <p>{!!  isset($feature_liability[0]) ? $feature_liability[0]->value : '&cross;'  !!}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 2)
                        <td>
                            <p>{!!  isset($feature_liability[1]) ? $feature_liability[1]->value : '&cross;'  !!}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 3)
                        <td>
                            <p>{!!  isset($feature_liability[2]) ? $feature_liability[2]->value : '&cross;'  !!}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 4)
                        <td>
                            <p>{!!  isset($feature_liability[3]) ? $feature_liability[3]->value : '&cross;'  !!}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 5)
                        <td>
                            <p>{!!  isset($feature_liability[4]) ? $feature_liability[4]->value : '&cross;'  !!}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 6)
                        <td>
                            <p>{!!  isset($feature_liability[5]) ? $feature_liability[5]->value : '&cross;'  !!}</p>
                        </td>
                        @endif
                    </tr>

                    <tr>
                        <td>
                            <p class="text-left">Blood Money</p>
                        </td>
                        @if ($listQuotePlansCount >= 1)
                        <td>
                            <p>{!!  isset($inclusion_bloodMoney[0]) ? $inclusion_bloodMoney[0]->value : '&cross;'  !!}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 2)
                        <td>
                            <p>{!!  isset($inclusion_bloodMoney[1]) ? $inclusion_bloodMoney[1]->value : '&cross;'  !!}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 3)
                        <td>
                            <p>{!!  isset($inclusion_bloodMoney[2]) ? $inclusion_bloodMoney[2]->value : '&cross;'  !!}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 4)
                        <td>
                            <p>{!!  isset($inclusion_bloodMoney[3]) ? $inclusion_bloodMoney[3]->value : '&cross;'  !!}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 5)
                        <td>
                            <p>{!!  isset($inclusion_bloodMoney[4]) ? $inclusion_bloodMoney[4]->value : '&cross;'  !!}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 6)
                        <td>
                            <p>{!!  isset($inclusion_bloodMoney[5]) ? $inclusion_bloodMoney[5]->value : '&cross;'  !!}</p>
                        </td>
                        @endif
                    </tr>
                    <tr>
                        <td>
                           <p class="text-left">Fire and Theft Cover </p>
                        </td>
                        @if ($listQuotePlansCount >= 1)
                        <td>
                           <p>{!!  isset($exclusion_fireAndTheft[0]) ? $exclusion_fireAndTheft[0]->value : '&cross;'  !!}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 2)
                        <td>
                           <p>{!!  isset($exclusion_fireAndTheft[1]) ? $exclusion_fireAndTheft[1]->value : '&cross;'  !!}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 3)
                        <td>
                           <p>{!!  isset($exclusion_fireAndTheft[2]) ? $exclusion_fireAndTheft[2]->value : '&cross;'  !!}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 4)
                        <td>
                           <p>{!!  isset($exclusion_fireAndTheft[3]) ? $exclusion_fireAndTheft[3]->value : '&cross;'  !!}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 5)
                        <td>
                           <p>{!!  isset($exclusion_fireAndTheft[4]) ? $exclusion_fireAndTheft[4]->value : '&cross;'  !!}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 6)
                        <td>
                           <p>{!!  isset($exclusion_fireAndTheft[5]) ? $exclusion_fireAndTheft[5]->value : '&cross;'  !!}</p>
                        </td>
                        @endif
                  </tr>
                  <tr>
                     <td>
                        <p class="text-left">Storm, Flood </p>
                     </td>
                     @if ($listQuotePlansCount >= 1)
                     <td>
                        <p>{!!  isset($exclusion_stormAndFlood[0]) ? $exclusion_stormAndFlood[0]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 2)
                     <td>
                        <p>{!!  isset($exclusion_stormAndFlood[1]) ? $exclusion_stormAndFlood[1]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 3)
                     <td>
                        <p>{!!  isset($exclusion_stormAndFlood[2]) ? $exclusion_stormAndFlood[2]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 4)
                     <td>
                        <p>{!!  isset($exclusion_stormAndFlood[3]) ? $exclusion_stormAndFlood[3]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 5)
                     <td>
                        <p>{!!  isset($exclusion_stormAndFlood[4]) ? $exclusion_stormAndFlood[4]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 6)
                     <td>
                        <p>{!!  isset($exclusion_stormAndFlood[5]) ? $exclusion_stormAndFlood[5]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                  </tr>
                  <tr>
                     <td>
                        <p class="text-left">Natural Perils </p>
                     </td>
                     @if ($listQuotePlansCount >= 1)
                     <td>
                        <p>{!!  isset($exclusion_perils[0]) ? $exclusion_perils[0]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 2)
                     <td>
                        <p>{!!  isset($exclusion_perils[1]) ? $exclusion_perils[1]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 3)
                     <td>
                        <p>{!!  isset($exclusion_perils[2]) ? $exclusion_perils[2]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 4)
                     <td>
                        <p>{!!  isset($exclusion_perils[3]) ? $exclusion_perils[3]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 5)
                     <td>
                        <p>{!!  isset($exclusion_perils[4]) ? $exclusion_perils[4]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 6)
                     <td>
                        <p>{!!  isset($exclusion_perils[5]) ? $exclusion_perils[5]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                  </tr>
                  <tr>
                     <td>
                        <p class="text-left">Natural Perils Riot and Strike  </p>
                     </td>
                     @if ($listQuotePlansCount >= 1)
                     <td>
                        <p>{!!  isset($exclusion_riotAndStrike[0]) ? $exclusion_riotAndStrike[0]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 2)
                     <td>
                        <p>{!!  isset($exclusion_riotAndStrike[1]) ? $exclusion_riotAndStrike[1]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 3)
                     <td>
                        <p>{!!  isset($exclusion_riotAndStrike[2]) ? $exclusion_riotAndStrike[2]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 4)
                     <td>
                        <p>{!!  isset($exclusion_riotAndStrike[3]) ? $exclusion_riotAndStrike[3]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 5)
                     <td>
                        <p>{!!  isset($exclusion_riotAndStrike[4]) ? $exclusion_riotAndStrike[4]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 6)
                     <td>
                        <p>{!!  isset($exclusion_riotAndStrike[6]) ? $exclusion_riotAndStrike[6]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                  </tr>
                  <tr>
                     <td>
                        <p class="text-left">Repairs </p>
                     </td>
                     @if ($listQuotePlansCount >= 1)
                     <td>
                        <p>{!!  isset($exclusion_repairs[0]) ? $exclusion_repairs[0]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 2)
                     <td>
                        <p>{!!  isset($exclusion_repairs[1]) ? $exclusion_repairs[1]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 3)
                     <td>
                        <p>{!!  isset($exclusion_repairs[2]) ? $exclusion_repairs[2]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 4)
                     <td>
                        <p>{!!  isset($exclusion_repairs[3]) ? $exclusion_repairs[3]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 5)
                     <td>
                        <p>{!!  isset($exclusion_repairs[4]) ? $exclusion_repairs[4]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 6)
                     <td>
                        <p>{!!  isset($exclusion_repairs[5]) ? $exclusion_repairs[5]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                  </tr>
                  <tr>
                     <td>
                        <p class="text-left">Emergency Medical Expenses </p>
                     </td>
                     @if ($listQuotePlansCount >= 1)
                     <td>
                        <p>{!!  isset($exclusion_emergencyMedicalExpenses[0]) ? $exclusion_emergencyMedicalExpenses[0]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 2)
                     <td>
                        <p>{!!  isset($exclusion_emergencyMedicalExpenses[1]) ? $exclusion_emergencyMedicalExpenses[1]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 3)
                     <td>
                        <p>{!!  isset($exclusion_emergencyMedicalExpenses[2]) ? $exclusion_emergencyMedicalExpenses[2]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 4)
                     <td>
                        <p>{!!  isset($exclusion_emergencyMedicalExpenses[3]) ? $exclusion_emergencyMedicalExpenses[3]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 5)
                     <td>
                        <p>{!!  isset($exclusion_emergencyMedicalExpenses[4]) ? $exclusion_emergencyMedicalExpenses[4]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 6)
                     <td>
                        <p>{!!  isset($exclusion_emergencyMedicalExpenses[5]) ? $exclusion_emergencyMedicalExpenses[5]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                  </tr>
                  <tr>
                     <td>
                        <p class="text-left">Personal belongings </p>
                     </td>
                     @if ($listQuotePlansCount >= 1)
                     <td>
                        <p>{!!  isset($exclusion_personalBelongings[0]) ? $exclusion_personalBelongings[0]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 2)
                     <td>
                        <p>{!!  isset($exclusion_personalBelongings[1]) ? $exclusion_personalBelongings[1]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 3)
                     <td>
                        <p>{!!  isset($exclusion_personalBelongings[2]) ? $exclusion_personalBelongings[2]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 4)
                     <td>
                        <p>{!!  isset($exclusion_personalBelongings[3]) ? $exclusion_personalBelongings[3]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 5)
                     <td>
                        <p>{!!  isset($exclusion_personalBelongings[4]) ? $exclusion_personalBelongings[4]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 6)
                     <td>
                        <p>{!!  isset($exclusion_personalBelongings[5]) ? $exclusion_personalBelongings[5]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                  </tr>
                  <tr>
                     <td>
                        <p class="text-left">Oman Cover (Orange card not Included) </p>
                     </td>
                     @if ($listQuotePlansCount >= 1)
                     <td>
                        <p>{!!  isset($addons_omanCover[0]) ? $addons_omanCover[0]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 2)
                     <td>
                        <p>{!!  isset($addons_omanCover[1]) ? $addons_omanCover[1]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 3)
                     <td>
                        <p>{!!  isset($addons_omanCover[2]) ? $addons_omanCover[2]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 4)
                     <td>
                        <p>{!!  isset($addons_omanCover[3]) ? $addons_omanCover[3]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 5)
                     <td>
                        <p>{!!  isset($addons_omanCover[4]) ? $addons_omanCover[4]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 6)
                     <td>
                        <p>{!!  isset($addons_omanCover[5]) ? $addons_omanCover[5]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                  </tr>
                  <tr>
                     <td>
                        <p class="text-left">Off-road Cover </p>
                     </td>
                     @if ($listQuotePlansCount >= 1)
                     <td>
                        <p>{!!  isset($roadSideAssistance_offRoadCover[0]) ? $roadSideAssistance_offRoadCover[0]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 2)
                     <td>
                        <p>{!!  isset($roadSideAssistance_offRoadCover[1]) ? $roadSideAssistance_offRoadCover[1]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 3)
                     <td>
                        <p>{!!  isset($roadSideAssistance_offRoadCover[2]) ? $roadSideAssistance_offRoadCover[2]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 4)
                     <td>
                        <p>{!!  isset($roadSideAssistance_offRoadCover[3]) ? $roadSideAssistance_offRoadCover[3]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 5)
                     <td>
                        <p>{!!  isset($roadSideAssistance_offRoadCover[4]) ? $roadSideAssistance_offRoadCover[4]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 6)
                     <td>
                        <p>{!!  isset($roadSideAssistance_offRoadCover[5]) ? $roadSideAssistance_offRoadCover[5]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                  </tr>
                  <tr>
                     <td>
                        <p class="text-left">Guaranteed Repairs </p>
                     </td>
                     @if ($listQuotePlansCount >= 1)
                     <td>
                        <p>{!!  isset($exclusion_guaranteedRepairs[0]) ? $exclusion_guaranteedRepairs[0]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 2)
                     <td>
                        <p>{!!  isset($exclusion_guaranteedRepairs[1]) ? $exclusion_guaranteedRepairs[1]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 3)
                     <td>
                        <p>{!!  isset($exclusion_guaranteedRepairs[2]) ? $exclusion_guaranteedRepairs[2]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 4)
                     <td>
                        <p>{!!  isset($exclusion_guaranteedRepairs[3]) ? $exclusion_guaranteedRepairs[3]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 5)
                     <td>
                        <p>{!!  isset($exclusion_guaranteedRepairs[4]) ? $exclusion_guaranteedRepairs[4]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 6)
                     <td>
                        <p>{!!  isset($exclusion_guaranteedRepairs[5]) ? $exclusion_guaranteedRepairs[5]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                  </tr>
                  <tr>
                     <td>
                        <p class="text-left">24 Hour Accident and Breakdown Recovery </p>
                     </td>
                     @if ($listQuotePlansCount >= 1)
                     <td>
                        <p>{!!  isset($inclusion_breakdownRecovery[0]) ? $inclusion_breakdownRecovery[0]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 2)
                     <td>
                        <p>{!!  isset($inclusion_breakdownRecovery[1]) ? $inclusion_breakdownRecovery[1]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 3)
                     <td>
                        <p>{!!  isset($inclusion_breakdownRecovery[2]) ? $inclusion_breakdownRecovery[2]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 4)
                     <td>
                        <p>{!!  isset($inclusion_breakdownRecovery[3]) ? $inclusion_breakdownRecovery[3]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 5)
                     <td>
                        <p>{!!  isset($inclusion_breakdownRecovery[4]) ? $inclusion_breakdownRecovery[4]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 6)
                     <td>
                        <p>{!!  isset($inclusion_breakdownRecovery[5]) ? $inclusion_breakdownRecovery[5]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                  </tr>
                  <tr>
                     <td>
                        <p class="text-left">Ambulance Cover </p>
                     </td>
                     @if ($listQuotePlansCount >= 1)
                     <td>
                        <p>{!!  isset($inclusion_ambulanceCover[0]) ? $inclusion_ambulanceCover[0]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 2)
                     <td>
                        <p>{!!  isset($inclusion_ambulanceCover[1]) ? $inclusion_ambulanceCover[1]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 3)
                     <td>
                        <p>{!!  isset($inclusion_ambulanceCover[2]) ? $inclusion_ambulanceCover[2]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 4)
                     <td>
                        <p>{!!  isset($inclusion_ambulanceCover[3]) ? $inclusion_ambulanceCover[3]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 5)
                     <td>
                        <p>{!!  isset($inclusion_ambulanceCover[4]) ? $inclusion_ambulanceCover[4]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 6)
                     <td>
                        <p>{!!  isset($inclusion_ambulanceCover[5]) ? $inclusion_ambulanceCover[5]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                  </tr>
                  <tr>
                     <td>
                        <p class="text-left">Excess for Windscreen Damage </p>
                     </td>
                     @if ($listQuotePlansCount >= 1)
                     <td>
                        <p>{!!  isset($exclusion_excessForWindscreenDamage[0]) ? $exclusion_excessForWindscreenDamage[0]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 2)
                     <td>
                        <p>{!!  isset($exclusion_excessForWindscreenDamage[1]) ? $exclusion_excessForWindscreenDamage[1]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 3)
                     <td>
                        <p>{!!  isset($exclusion_excessForWindscreenDamage[2]) ? $exclusion_excessForWindscreenDamage[2]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 4)
                     <td>
                        <p>{!!  isset($exclusion_excessForWindscreenDamage[3]) ? $exclusion_excessForWindscreenDamage[3]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 5)
                     <td>
                        <p>{!!  isset($exclusion_excessForWindscreenDamage[4]) ? $exclusion_excessForWindscreenDamage[4]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 6)
                     <td>
                        <p>{!!  isset($exclusion_excessForWindscreenDamage[5]) ? $exclusion_excessForWindscreenDamage[5]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                  </tr>
                    <tr>
                        <td colspan="1">
                            <p class="text-left font-medium text-indigo-800">Optional Covers</p>
                        </td>
                        <td colspan="{{ $listQuotePlansCount }}" class="bg-indigo-100"></td>
                    </tr>

                    <tr>
                     <td>
                        <p class="text-left">Driver Cover </p>
                     </td>
                     @if ($listQuotePlansCount >= 1)
                     <td>
                        <p>{!!  isset($addons_driverCover[0]) ? $addons_driverCover[0]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 2)
                     <td>
                        <p>{!!  isset($addons_driverCover[1]) ? $addons_driverCover[1]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 3)
                     <td>
                        <p>{!!  isset($addons_driverCover[2]) ? $addons_driverCover[2]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 4)
                     <td>
                        <p>{!!  isset($addons_driverCover[3]) ? $addons_driverCover[3]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 3)
                     <td>
                        <p>{!!  isset($addons_driverCover[2]) ? $addons_driverCover[2]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 4)
                     <td>
                        <p>{!!  isset($addons_driverCover[3]) ? $addons_driverCover[3]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                  </tr>

                  <tr>
                     <td>
                        <p class="text-left">Passengers Cover </p>
                     </td>
                     @if ($listQuotePlansCount >= 1)
                     <td>
                        <p>{!!  isset($addons_passengerCover[0]) ? $addons_passengerCover[0]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 2)
                     <td>
                        <p>{!!  isset($addons_passengerCover[1]) ? $addons_passengerCover[1]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 3)
                     <td>
                        <p>{!!  isset($addons_passengerCover[2]) ? $addons_passengerCover[2]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 4)
                     <td>
                        <p>{!!  isset($addons_passengerCover[3]) ? $addons_passengerCover[3]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 5)
                     <td>
                        <p>{!!  isset($addons_passengerCover[4]) ? $addons_passengerCover[4]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 6)
                     <td>
                        <p>{!!  isset($addons_passengerCover[5]) ? $addons_passengerCover[5]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                  </tr>


                  <tr>
                     <td>
                        <p class="text-left">Hire car Benefit </p>
                     </td>
                     @if ($listQuotePlansCount >= 1)
                     <td>
                        <p>{!!  isset($exclusion_carHire[0]) ? $exclusion_carHire[0]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 2)
                     <td>
                        <p>{!!  isset($exclusion_carHire[1]) ? $exclusion_carHire[1]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 3)
                     <td>
                        <p>{!!  isset($exclusion_carHire[2]) ? $exclusion_carHire[2]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 4)
                     <td>
                        <p>{!!  isset($exclusion_carHire[3]) ? $exclusion_carHire[3]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 5)
                     <td>
                        <p>{!!  isset($exclusion_carHire[4]) ? $exclusion_carHire[4]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                     @if ($listQuotePlansCount >= 6)
                     <td>
                        <p>{!!  isset($exclusion_carHire[5]) ? $exclusion_carHire[5]->value : '&cross;'  !!}</p>
                     </td>
                     @endif
                  </tr>

                    {{--  use this for spacing between rows  --}}
                    <tr>
                        <td colspan="{{ $listQuotePlansCount + 1 }}"><div class="spacer"></div></td>
                    </tr>

                    <tr>
                        <td>
                            <p class="text-left font-medium text-indigo-800">Premium</p>
                        </td>
                        @if ($listQuotePlansCount >= 1)
                           <td class="bg-indigo-100">
                              <p class="font-semibold">{{ isset($discountPremium[0]) ? $discountPremium[0] : 'AED 0.0' }}</p>
                           </td>
                        @endif
                        @if ($listQuotePlansCount >= 2)
                        <td class="bg-indigo-100">
                            <p class="font-semibold">{{ isset($discountPremium[1]) ? $discountPremium[1] : 'AED 0.0' }}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 3)
                        <td class="bg-indigo-100">
                            <p class="font-semibold">{{ isset($discountPremium[2]) ? $discountPremium[2] : 'AED 0.0' }}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 4)
                        <td class="bg-indigo-100">
                            <p class="font-semibold">{{ isset($discountPremium[3]) ? $discountPremium[3] : 'AED 0.0' }}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 5)
                        <td class="bg-indigo-100">
                            <p class="font-semibold">{{ isset($discountPremium[4]) ? $discountPremium[4] : 'AED 0.0' }}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 6)
                        <td class="bg-indigo-100">
                            <p class="font-semibold">{{ isset($discountPremium[5]) ? $discountPremium[5] : 'AED 0.0' }}</p>
                        </td>
                        @endif
                    </tr>

                    <tr>
                        <td>
                            <p class="text-left font-medium text-indigo-800">VAT Amount</p>
                        </td>
                        @if ($listQuotePlansCount >= 1)
                           <td class="bg-indigo-100">
                              <p class="font-semibold">{{ isset($vat[0]) ? $vat[0] : 'AED 0.0' }}</p>
                           </td>
                        @endif
                        @if ($listQuotePlansCount >= 2)
                        <td class="bg-indigo-100">
                            <p class="font-semibold">{{ isset($vat[1]) ? $vat[1] : 'AED 0.0' }}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 3)
                        <td class="bg-indigo-100">
                            <p class="font-semibold">{{ isset($vat[2]) ? $vat[2] : 'AED 0.0' }}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 4)
                        <td class="bg-indigo-100">
                            <p class="font-semibold">{{ isset($vat[3]) ? $vat[3] : 'AED 0.0' }}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 5)
                        <td class="bg-indigo-100">
                            <p class="font-semibold">{{ isset($vat[4]) ? $vat[4] : 'AED 0.0' }}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 6)
                        <td class="bg-indigo-100">
                            <p class="font-semibold">{{ isset($vat[5]) ? $vat[5] : 'AED 0.0' }}</p>
                        </td>
                        @endif
                    </tr>

                    <tr>
                        <td>
                            <p class="text-left font-medium text-indigo-800">Total</p>
                        </td>
                        @if ($listQuotePlansCount >= 1)
                           <td class="bg-indigo-100">
                              <p class="font-semibold">{{ (isset($discountPremium[0]) && isset($vat[0])) ? $discountPremium[0] + $vat[0] : 'AED 0.0' }}</p>
                           </td>
                        @endif
                        @if ($listQuotePlansCount >= 2)
                        <td class="bg-indigo-100">
                            <p class="font-semibold">{{ (isset($discountPremium[1]) && isset($vat[1])) ? $discountPremium[1] + $vat[1] : 'AED 0.0' }}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 3)
                        <td class="bg-indigo-100">
                            <p class="font-semibold">{{ (isset($discountPremium[2]) && isset($vat[2])) ? $discountPremium[2] + $vat[2] : 'AED 0.0' }}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 4)
                        <td class="bg-indigo-100">
                            <p class="font-semibold">{{ (isset($discountPremium[3]) && isset($vat[3])) ? $discountPremium[3] + $vat[3] : 'AED 0.0' }}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 5)
                        <td class="bg-indigo-100">
                            <p class="font-semibold">{{ (isset($discountPremium[4]) && isset($vat[4])) ? $discountPremium[2] + $vat[2] : 'AED 0.0' }}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 6)
                        <td class="bg-indigo-100">
                            <p class="font-semibold">{{ (isset($discountPremium[5]) && isset($vat[5])) ? $discountPremium[3] + $vat[3] : 'AED 0.0' }}</p>
                        </td>
                        @endif
                    </tr>

                    <tr>
                        <td>
                            <p class="text-left font-medium text-indigo-800">Excess</p>
                        </td>
                        @if ($listQuotePlansCount >= 1)
                        <td>
                            <p class="text-cyan-600">{!! isset($listQuotePlans[0]->excess) ? $listQuotePlans[0]->excess : '&cross;' !!}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 2)
                        <td>
                            <p class="text-cyan-600">{!! isset($listQuotePlans[1]->excess) ? $listQuotePlans[1]->excess : '&cross;' !!}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 3)
                        <td>
                            <p class="text-cyan-600">{!! isset($listQuotePlans[2]->excess) ? $listQuotePlans[2]->excess : '&cross;' !!}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 4)
                        <td>
                            <p class="text-cyan-600">{!! isset($listQuotePlans[3]->excess) ? $listQuotePlans[3]->excess : '&cross;' !!}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 5)
                        <td>
                            <p class="text-cyan-600">{!! isset($listQuotePlans[4]->excess) ? $listQuotePlans[4]->excess : '&cross;' !!}</p>
                        </td>
                        @endif
                        @if ($listQuotePlansCount >= 6)
                        <td>
                            <p class="text-cyan-600">{!! isset($listQuotePlans[5]->excess) ? $listQuotePlans[5]->excess : '&cross;' !!}</p>
                        </td>
                        @endif
                    </tr>
                </tbody>
            </table>

            <div class="info">
                <h5>MATERIAL INFORMATION DECLARATION</h5>
                <p>All quotes we provide are indicative and based on the information that you, as a
                    proposer, have provided to us. It is important that this information accurately reflects your
                    position and needs and before you purchase your policy, you are advised to check all the details
                    relevant to the risk to be insured have been supplied. Failure to provide all material information
                    may result in the insurer declining future claims on the ground of misrepresentation.</p>
            </div>
        </div>
    </div>

</body>

</html>
