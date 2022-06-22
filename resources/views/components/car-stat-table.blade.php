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
    <table class="table table-striped jambo_table" style="width:100%">
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
                $totalPaidEcom = $currentWeekStat->paid_ecom;
                $totalPaidEcomAuthorised = $currentWeekStat->paid_ecom_auth;
                $totalPaidEcomCaputed = $currentWeekStat->paid_ecom_captured;
                $totalPaidEcomCancelled = $currentWeekStat->paid_ecom_cancelled;
                $totalEcom = $currentWeekStat->ecom_total;
            @endphp
            <tr>
                <td>
                    {{$currentWeekStat->email}}
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
                    $eComConversion = divideNumber($currentWeekStat->tran_approved_ecom, $currentWeekStat->ecom_total) *
                    100;
                    @endphp
                    {{round($eComConversion, 2)}} {{-- TODO : Need to add conversion here --}}
                </td>
            </tr>
            @endforeach
            <tr>
                <td colspan="2" style="text-align: center;border: 1px solid black;">
                   <b>{{$totalAssigned}}</b>
                </td>
                <td style="text-align: center;border: 1px solid black;">
                    <b>{{$totalPaidEcom}}</b>
                </td>
                <td style="text-align: center;border: 1px solid black;">
                    <b>{{$totalPaidEcomAuthorised}}</b>
                </td>
                <td style="text-align: center;;border: 1px solid black;">
                    <b> {{$totalPaidEcomCaputed}}</b>
                </td>
                <td style="text-align: center;;border: 1px solid black;">
                    <b>{{$totalPaidEcomCancelled}}</b>
                </td>
                <td colspan="4" style="text-align: center;border: 1px solid black;">
                    <b>{{$totalEcom}}</b>
                </td>
            </tr>
        </tbody>
    </table>
</div>

@php
$count++;
@endphp

@endforeach
