<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{$quoteType == 'business' ? 'Riskscore Entity' : 'Riskscore Individual'}}</title>
    <style>
        @font-face {
            font-family: 'DejaVu Sans';
            font-style: normal;
            font-weight: normal;
        }
        body {
            font-family: 'DejaVu Sans', serif !important;
        }
        p{
            padding: 0;
            margin: 0;
        }
        #header {
            margin-top: -34px;
            text-align: center;
        }
        .float-left{
            float: left;
        }
        .float-right{
            float: right;
        }
        #footer {
            margin: 100px -50px 0 -45px !important;
            background-color: rgb(29 131 188);
            color: white;
            width: 800px !important;
            position: fixed !important;
            bottom: -48px;
        }
        #footer > h6 {
            margin: 0;
            font-weight: 400;
            font-size: 9px;
        }
        .main-heading {
            text-align: center !important;
            color: #44475C;
            font-size: 20px;
            text-decoration: underline;
            font-weight: 800;
            margin: 0;
        }
        .sub-heading {
            color: #44475C;
            font-size: 14px;
            text-decoration: underline;
            font-weight: 700;
            margin: 0;
        }
        .pl-6 {
            padding-left: 8px;
        }
        .text-center {
            text-align: center;
        }
        table {
            border: 1px solid black;
            width: 100%;
            border-spacing: 0;
        }
        tr {
            border-spacing: 0;
        }
        tr td {
            font-size: 10px;
            color: #44475C;
            /* border-bottom: 1px solid; */
            /* border-left: 1px solid; */
            border: 1px solid;
            padding: 8px;
        }
        .custom-table tr td:first-child {
            padding-left: 10px;
            font-weight: 500;
            padding-right: 80px;
        }
        .custom-table tr td:nth-child(2) {
            background: #ffffff;
            border-style: solid;
            border-color: #1d83bc;
            border-width: 0.1px;
            color: black;
            font-weight: 500;
            padding-left: 5px;
            width: 420px;
        }
        .value {
            background: #ffffff;
            border-style: solid;
            border-color: #1d83bc;
            border-width: 0.1px;
            color: black;
            font-weight: 500;
            padding: 3px 20px 3px 5px;
            width: 160px;
            height: 20px;
        }
        .no-border {
            border-style: none !important;
        }
        .float-right {
            float: right !important;
        }
        @media print {
            @page {
                size: A4 portrait;
            }
            #footer {
                -webkit-print-color-adjust: exact;
            }
        }
        .bg-slate-50.p-4{
            overflow: auto !important;
        }
        .color-white{
            color:#fff
        }
        th{
            font-size: 13px;
            border: 1px solid;
            padding: 8px;
            border-bottom: none;
        }
        .wi-25{
            width: 25%;
        }
        .wi-50{
            width: 50%;
        }
        .wi-30{
            width: 30%;
        }
        .wi-75{
            width: 75%;
        }
        .wi-70{
            width: 70%;
        }
        .wi-100{
            width: 100%;
        }
        .clear{
            clear: both;
        }
        .customer_color{
            background-color: #FEF2CB;
        }
        .geographic_color{
            background-color: #DEEAF6;
        }
        .product_color{
            background-color: #ECECEC;
        }
        .transaction_color{
            background-color: #F7CAAC;
        }
        .delivery_color{
            background-color: #E2EFD9;
        }
        .risk_color{
            background-color: yellow;
        }
        .override_color{
            background-color: #FF0000;
            color: white;
        }
    </style>
</head>

<body>
<div id="header">
    <img src="{{'data:image/png;base64,'.base64_encode(file_get_contents(getIMLogo(true)))}}" alt="Insurance Market Logo" width="300">
</div>
<hr>

<div id="content">

    <div class="grid sm:grid-cols-2">

        <h2 class="text-center" style="color: #C30E0E">ML-TF Risk Assessment Form</h2>


        <div>

            <div class="wi-100">
                <strong class="wi-25 float-left">Customer Name</strong>

                <p class="wi-75 float-left">{{$quoteModel->first_name}} {{$quoteModel->last_name}}</p>
            </div>
            <div class="clear"></div>

            <div class="wi-100">

                <strong class="wi-25 float-left">UID Number</strong>

                <p class="wi-25 float-left">{{$quoteModel->id}}</p>

                <strong class="wi-25 float-left">Date</strong>

                <p class="wi-25 float-left">
                    {{  date('d-m-Y', strtotime('now')) }}
                </p>
                <div class="clear"></div>
            </div>






            <div class=" space-x-2" >
                <table class="" >
                    <tbody class="vue3-easy-data-table__body border-inner">
                    <tr style="background-color: #FFC000">
                        <th class=" w-50 z-10 text-left p-0">Risk Category</th>
                        <th class=" w-50 z-10 text-left p-0">Risk Type</th>
                        <th class="text-left w-30 p-0 capitalize" >Risk Items</th>
                        <th class="text-left w-20 p-0">Risk Score</th>
                    </tr>



                    @foreach($data['score_list'] as $index => $score)
                            <?php
                            $color = $index >= 0 && $index <= 7? 'customer_color' : ($index >= 8 && $index <= 12 ? 'geographic_color' : ($index <= 13 ? 'product_color':($index >= 14 && $index <= 17 ? 'transaction_color':($index <= 17 ? 'delivery_color': ($index <= 18 ? 'delivery_color': 'delivery_color')))));
                            $cc = $index >= 0 && $index <= 7? 'customer_color' : ($index >= 8 && $index <= 11 ? 'geographic_color' : ($index <= 12 ? 'product_color':($index >= 13 && $index < 16 ? 'transaction_color':($index >= 17 ? 'delivery_color':'delivery_color'))))
                            ?>
                        @if($quoteType != 'business')
                            <tr class={{$cc}}>
                                @if($index == 0)
                                    <th  class=""  rowspan="8">Customer Risk</th>
                                @endif

                                @if($index == 8)
                                    <th  class="" rowspan="4">Geographic Risk</th>
                                @endif

                                @if($index == 12)
                                    <th class="" >Product Risk</th>
                                @endif

                                @if($index == 13)

                                    <th class="" rowspan="3">Transaction Risk</th>
                                @endif

                                @if($index == 16)
                                    <th  rowspan="3">Delivery Channel & Payment Risk</th>
                                @endif
                                <td class="w-50 z-10 text-left p-0">{{ $score['text'] }}</td>
                                <td class="text-left w-30 p-0 capitalize">{{ $score['value'] }}</td>
                                <td class="text-left w-20 p-0">{{ $score['score'] }}</td>
                            </tr>


                        @else


                            <tr class={{$color}}>
                                @if($index == 0)
                                    <th  class=""  rowspan="8">Customer Risk</th>
                                @endif


                                @if($index == 8)
                                    <th  class="" rowspan="5">Geographic Risk</th>
                                @endif






                                @if($index == 13)
                                    <th class=""  >Product Risk</th>
                                @endif


                                @if($index == 14)
                                    <th class="" rowspan="2">Transaction Risk</th>

                                @endif
                                @if($index == 16 || $index == 17)
                                    <th style="border-top: none;"></th>

                                @endif


                                @if($index == 18)
                                    <th  rowspan="3">Delivery Channel & Payment Risk</th>
                                @endif

                                <td class="w-50 z-10 text-left p-0">{{ $score['text'] }}</td>
                                <td class="text-left w-30 p-0 capitalize">{{ $score['value'] }}</td>
                                <td class="text-left w-20 p-0">{{ $score['score'] }}</td>
                            </tr>
                        @endif
                    @endforeach


                    {{--                            @if($index == 14)--}}
                    {{--                                <th class="" rowspan="4">Transaction Risk</th>--}}
                    {{--                            @endif--}}

                    {{--                            @if($index == 18)--}}
                    {{--                                <th  rowspan="3">Delivery Channel & Payment Risk</th>--}}
                    {{--                            @endif--}}






                    <tr>
                        <th>Comments</th>
                        <td>
                            @foreach($kycLogs as $log)
                                @php
                                    $notes = isset($log->notes) ? $log->notes : 'N/A';
                                @endphp

                                <p>Notes: {{ $notes }}</p>
                            @endforeach

                        </td>
                        <td colspan="2" >
                            @foreach($kycLogs as $log)

                                <p>ID: {{ $log->customer_code }}, Full Name: {{ $log->input }}</p>
                            @endforeach
                        </td>
                    </tr>
                    <tr>
                        <th>Total Risk Score</th>
                        <td colspan="3">{{$quoteModel->risk_score}}</td>
                    </tr>
                    <tr>
                        <th>Inherent Risk Level</th>

                        <td class="risk_color">
                            {{
           ($quoteModel->risk_score <= 25 ? 'Low Risk' :
               ($quoteModel->risk_score <= 34 ? 'Medium Risk' :
                   ($quoteModel->risk_score >= 35 ? 'High Risk' : 'N/A')))
       }}
                        </td>
                        <th>Risk Override</th>
                        <td class="override_color">{{ $detail && $detail->risk_score_override ? $detail->risk_score_override : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Next Assessment</th>
                        <td colspan="3">

                            {{ isset($quoteModel->policy_expiry_date) ? date('d-m-Y', strtotime($quoteModel->policy_expiry_date . ' -30 days')) : 'N/A' }}
                        </td>
                    </tr>
                    <tr>
                        <th>Compliance Officer Comments</th>
                        <td colspan="3">{{$quoteModel->compliance_comments ? $quoteModel->compliance_comments : 'N/A'}}</td>
                    </tr>
                    <tr>
                        <th>Prepared By</th>
                        <td> System Generated at {{ now()->format('d-m-Y H:i:s') }}</td>
                        <th>Approved By</th>
                        <td></td>
                    </tr>
                    <tr><td colspan="4" style="padding: 20px"></td></tr>

                    <tr>
                        <th>Name:</th>
                        <td></td>
                        <th>Name:</th>
                        <td></td>
                    </tr>
                    <tr>

                        <th>Compliance Officer</th>
                        <td></td>
                        <th>Manager</th>
                        <td></td>
                    </tr>
                    </tbody>
                </table>
            </div>


        </div>







    </div>



    <div id="footer">
        <h5 class="text-center" style="padding-top: 5px;">InsuranceMarket.ae is the registered trademark of AFIA Insurance Brokerage Services LLC</h5>
        <h6 class="pl-6">
            <u>UAE Central Bank</u> Registration number 85
        </h6>
        <h6 class="pl-6">
            Registered member of the <u>Emirates Insurance Association</u>
            <span class="float-right" style="margin-right: 20px;">27th floor, Control Tower, Motor City</span>
        </h6>
        <h6 class="pl-6">
            <u>Department of Economy & Tourism in Dubai</u> Trade License number 238534
            <span class="float-right" style="margin-right: -165px;">Dubai, United Arab Emirates, PO Box - 26423</span>
        </h6>
        <h6 class="pl-6">
            Holder of Health Insurance Intermediary Permit ID Number BRK-00003 from <u>Dubai Health Authority</u>
            <span class="float-right" style="margin-right: -20px;">Tel: 800 ALFRED (800 253 733)</span>
        </h6>
        <h6 class="pl-6" style="padding-bottom: 5px;">
            Registered member of <u>Insurance Business Group</u> under the <u>Dubai Chamber of Commerce and Industry</u>
            <span class="float-right" style="margin-right: -102px;">insurancemarket.ae</span>
        </h6>
    </div>


</body>

</html>
