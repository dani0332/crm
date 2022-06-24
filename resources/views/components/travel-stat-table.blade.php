@php
$count = 1;
@endphp
<div class="x_title">
    <h2>Travel Conversion</h2>
    <div class="clearfix"></div>
</div>
@foreach ($statsArray as $currentStat)

<div class="x_content">
    <div>
        @php
        $statVariableName = $count . 'Week';
        $statHeadingName = $count . 'WeekHeadingDate';
        $totalAssigned = 0;
        $totalPaidEcom = 0;
        $totalPaidEcomAuthorised = 0;
        $totalPaidEcomCaputed = 0;
        $totalPaidEcomCancelled = 0;
        $totalTransactionApprovedEcom = 0;
        $totalTransactionApprovedNonEcom = 0;
        $totalTransactionApproved = 0;
        $totalEcom = 0;
        @endphp
        <h2>{{$headingArray[$statHeadingName]}}</h2>
        <div class="clearfix"></div>
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
                <th>Tran. Aprov. Ecom</th>
                <th>Tran. Aprov. Non-Ecom</th>
                <th>Tran. Aprov. Total</th>
                <th>Ecom Total</th>
                <th>Ecom Conversion</th>
                <th>Non-Ecom Conversion</th>
                <th>Overall Conversion</th>
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
                $totalTransactionApprovedEcom += $currentWeekStat->tran_approved_ecom;
                $totalTransactionApprovedNonEcom += $currentWeekStat->tran_approved_non_ecom;
                $totalTransactionApproved += $currentWeekStat->tran_approved_total;
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
                    {{$currentWeekStat->tran_approved_ecom}}
                </td>
                <td>
                    {{$currentWeekStat->tran_approved_non_ecom}}
                </td>
                <td>
                    {{$currentWeekStat->tran_approved_total}}
                </td>
                <td>
                    {{$currentWeekStat->ecom_total}}
                </td>

                <td>
                    @php
                    $eComConversion = divideNumber($currentWeekStat->tran_approved_ecom, $currentWeekStat->ecom_total) *
                    100;
                    $nonEcomConversion = divideNumber($currentWeekStat->tran_approved_non_ecom,
                    ($currentWeekStat->total_assigned - $currentWeekStat->ecom_total )) * 100;
                    $overallConversion = divideNumber($currentWeekStat->tran_approved_total,
                    $currentWeekStat->total_assigned) * 100;
                    @endphp
                    {{round($eComConversion, 2)}} {{-- TODO : Need to add conversion here --}}
                </td>
                <td>
                    {{ round($nonEcomConversion, 2) }}{{-- TODO : Need to add conversion here --}}
                </td>
                <td>
                    {{round($overallConversion, 2)}}{{-- TODO : Need to add conversion here --}}
                </td>
            </tr>
            @endforeach
            <tr>
                <td></td>
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
                    <b>{{$totalTransactionApprovedEcom}}</b>
                </td>
                <td>
                    <b>{{$totalTransactionApprovedNonEcom}}</b>
                </td>
                <td>
                    <b>{{$totalTransactionApproved}}</b>
                </td>
                <td>
                    <b>{{$totalEcom}}</b>
                </td>
                <td>
                    <b>{{round(divideNumber($totalTransactionApprovedEcom, $totalEcom) * 100, 2)}}%</b>
                </td>
                <td>
                    <b>{{round(divideNumber($totalTransactionApprovedNonEcom, ($totalAssigned - $totalEcom)) * 100, 2)}}%</b>
                </td>
                <td>
                    <b>{{round(divideNumber($totalTransactionApproved, $totalAssigned) * 100, 2)}}%</b>
                </td>
            </tr>
        </tbody>
    </table>
</div>

@php
$count++;
@endphp

@endforeach
