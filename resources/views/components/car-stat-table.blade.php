@php
$count = 1;
@endphp
<div class="x_title">
    <h2>Car Conversion</h2>
    <div class="clearfix"></div>
</div>
@foreach ($statsArray as $currentStat)

<div class="x_content">

    <div >
        @php
        $statVariableName = $count . 'Week';
        $statHeadingName = $count . 'WeekHeadingDate';
        $totalAssigned = 0;
        $totalPaidEcom = 0;
        $totalPaidEcomAuthorised = 0;
        $totalPaidEcomCaputed = 0;
        $totalPaidEcomCancelled = 0;
        $totalEcom = 0;
        @endphp
        <h2>{{$headingArray[$statHeadingName]}}</h2>
    </div>
    <table class="table table-striped jambo_table conversion_table" style="width:100%">
        <thead>
            <tr>
                <th>Advisor Email</th>
                <th>Total Assigned</th>
                <th>Paid Ecom</th>
                <th>Paid Ecom Authorised</th>
                <th>Paid Ecom Captured</th>
                <th>Paid Ecom Cancelled</th>
                <th>Ecom Total</th>
                <th>Ecom Conversion</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($statsArray[$statVariableName] as $currentWeekStat)
            @php
                $totalAssigned += $currentWeekStat->total_assigned;
                $totalPaidEcom += $currentWeekStat->paid_ecom;
                $totalPaidEcomAuthorised += $currentWeekStat->paid_ecom_auth;
                $totalPaidEcomCaputed += $currentWeekStat->paid_ecom_captured;
                $totalPaidEcomCancelled += $currentWeekStat->paid_ecom_cancelled;
                $totalEcom += $currentWeekStat->ecom_total;
            @endphp
            <tr>
                <td>
                    {{ $currentWeekStat->email == ''|| $currentWeekStat->email == null ? 'UnAssigned' : $currentWeekStat->email }}
                </td>
                <td>
                    {{$currentWeekStat->total_assigned}}
                </td>
                <td>
                    {{$currentWeekStat->paid_ecom}}
                </td>
                <td>
                    {{$currentWeekStat->paid_ecom_auth}}
                </td>
                <td>
                    {{$currentWeekStat->paid_ecom_captured}}
                </td>
                <td>
                    {{$currentWeekStat->paid_ecom_cancelled}}
                </td>
                <td>
                    {{$currentWeekStat->ecom_total}}
                </td>

                <td>
                    @php
                    $eComConversion = divideNumber($currentWeekStat->paid_ecom_captured, $currentWeekStat->ecom_total) *
                    100;
                    @endphp
                    {{round($eComConversion, 2)}} {{-- TODO : Need to add conversion here --}}
                </td>
            </tr>
            @endforeach
            <tr>
                <td>

                </td>
                <td>
                   <b>{{$totalAssigned}}</b>
                </td>
                <td>
                    <b>{{$totalPaidEcom}}</b>
                </td>
                <td>
                    <b>{{$totalPaidEcomAuthorised}}</b>
                </td>
                <td>
                    <b> {{$totalPaidEcomCaputed}}</b>
                </td>
                <td>
                    <b>{{$totalPaidEcomCancelled}}</b>
                </td>
                <td>
                    <b>{{$totalEcom}}</b>
                </td>
                <td colspan="3" >
                    <b>{{round(divideNumber($totalPaidEcomCaputed, $totalEcom) * 100, 2)}}%</b>
                </td>
            </tr>
        </tbody>
    </table>
</div>

@php
$count++;
@endphp

@endforeach
