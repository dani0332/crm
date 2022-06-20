@php
$count = 1;
@endphp
@foreach ($statsArray as $currentStat)

<div class="x_content">
    <div class="x_title">
        @php
        $statVariableName = $count . 'Week';
        $statHeadingName = $count . 'WeekHeadingDate';
        @endphp
        <h2>{{$headingArray[$statHeadingName]}}</h2>
        <div class="clearfix"></div>
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
                    {{$currentWeekStat->ecom_conv}}
                </td>
                <td>
                    {{$currentWeekStat->non_ecom_conv}}
                </td>
                <td>
                    {{$currentWeekStat->overall_conv}}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@php
$count++;
@endphp

@endforeach
