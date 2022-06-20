@extends('layouts.app')
@section('title','Dashboard')
@section('content')
<div class="">
    <div class="row">
        <div class="x_panel transparent">
            <div class="x_content">
                <div class="x_title">
                    <h2>{{$firstWeekHeadingDate}}</h2>
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
                        @foreach ($thisWeekStats as $thisWeekStat)
                        <tr>
                            <td>
                                {{$thisWeekStat->email}}
                            </td>
                            <td>
                                {{$thisWeekStat->total_assigned}}
                            </td>
                            <td>
                                {{$thisWeekStat->paid_ecom}}
                            </td>
                            <td>
                                {{$thisWeekStat->paid_ecom_auth}}
                            </td>
                            <td>
                                {{$thisWeekStat->paid_ecom_captured}}
                            </td>
                            <td>
                                {{$thisWeekStat->paid_ecom_cancelled}}
                            </td>
                            <td>
                                {{$thisWeekStat->tran_approved_ecom}}
                            </td>
                            <td>
                                {{$thisWeekStat->tran_approved_non_ecom}}
                            </td>
                            <td>
                                {{$thisWeekStat->tran_approved_total}}
                            </td>
                            <td>
                                {{$thisWeekStat->ecom_total}}
                            </td>

                            <td>
                                {{$thisWeekStat->ecom_conv}}
                            </td>
                            <td>
                                {{$thisWeekStat->non_ecom_conv}}
                            </td>
                            <td>
                                {{$thisWeekStat->overall_conv}}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>

            <div class="x_content">
                <div class="x_title">
                    <h2>{{$secondWeekHeadingDate}}</h2>
                    <div class="clearfix"></div>
                </div>
                <table class="table table-striped jambo_table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Total Assigned</th>
                            <th>Paid Ecom</th>
                            <th>Paid Ecom Authorised</th>
                            <th>Paid Ecom Captured</th>
                            <th>Paid Ecom Cancelled</th>
                            <th>Trans. Approv. Ecom</th>
                            <th>Trans. Approv. Non-Ecom</th>
                            <th>Trans. Approv. Total</th>
                            <th>Ecom Total</th>
                            <th>Email</th>
                            <th>Ecom Conversion</th>
                            <th>Non-Ecom Conversion</th>
                            <th>Overall Conversion</th>
                        </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>

            </div>

            <div class="x_content">
                <div class="x_title">
                    <h2>{{$thirdWeekHeadingDate}}</h2>
                    <div class="clearfix"></div>
                </div>
                <table class="table table-striped jambo_table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Total Assigned</th>
                            <th>Paid Ecom</th>
                            <th>Paid Ecom Authorised</th>
                            <th>Paid Ecom Captured</th>
                            <th>Paid Ecom Cancelled</th>
                            <th>Trans. Approv. Ecom</th>
                            <th>Trans. Approv. Non-Ecom</th>
                            <th>Trans. Approv. Total</th>
                            <th>Ecom Total</th>
                            <th>Email</th>
                            <th>Ecom Conversion</th>
                            <th>Non-Ecom Conversion</th>
                            <th>Overall Conversion</th>
                        </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>

            </div>

            <div class="x_content">
                <div class="x_title">
                    <h2>{{$fourthWeekHeadingDate}}</h2>
                    <div class="clearfix"></div>
                </div>
                <table class="table table-striped jambo_table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Total Assigned</th>
                            <th>Paid Ecom</th>
                            <th>Paid Ecom Authorised</th>
                            <th>Paid Ecom Captured</th>
                            <th>Paid Ecom Cancelled</th>
                            <th>Trans. Approv. Ecom</th>
                            <th>Trans. Approv. Non-Ecom</th>
                            <th>Trans. Approv. Total</th>
                            <th>Ecom Total</th>
                            <th>Email</th>
                            <th>Ecom Conversion</th>
                            <th>Non-Ecom Conversion</th>
                            <th>Overall Conversion</th>
                        </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>

            </div>
        </div>
    </div>
</div>
@endsection
