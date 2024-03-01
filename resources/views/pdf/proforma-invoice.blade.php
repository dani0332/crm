<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Proforma Payment Request</title>

    <style>
        html {
            line-height: 1.5;
            margin: 0px;
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

        table.tbl-dec {
            border: none;
        }

        table.tbl-dec tr td, table.tbl-dec tr td a {
            border: none;
        }

        table {
            min-width: 1150px;
            width: 1150px;
            text-indent: 0;
            border-color: #bfbfbf;
            max-width: 1150px;
            margin: 7px 12px auto;
            border-spacing: 0;
        }

        .header {
            background: white;
            font-size: 20px;
            text-align: center;
            padding: 8px 10px;
            width: 100%;
            height: 68px;
            max-height: 68px;
            border-bottom: 1px solid #5D697B;
        }

        .header .logo {
            float: left;
            background-color: white;
            border-radius: 5px;
            padding: 5px 10px 5px 0px;
            height: 60px;
            max-height: 60px;
        }

        .header .logo img {
            max-height: 50px;
            height: 50px;
        }

        .header h3 {
            color: #5D697B;
            float: right;
            vertical-align: middle;
            padding: 18px 40px;
            font-weight: 700;
            font-size: 20px;
        }

        table tr td, table tr th {
            font-size: 14px;
            text-align: left;
        }

        td > p {
            padding: 4px;
            font-size: 14px;
            text-align: center;
        }

        .text-left {
            text-align: left !important;
        }

        .text-xxs {
            font-size: 11px !important;
        }

        .text-xs {
            font-size: 13px;
        }

        .text-sm {
            font-size: 14px;
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

        .section {
            color: #333393;
            text-align: left;
        }

        .text-black {
            color: #000000;
        }


        .spacer {
            padding: 3px;
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


        .text-heading {
            color: #ffffff;
            background-color: #1d83bc;
        }


        @page {
            margin-bottom: 0px;
            margin-top: 20px;
        }

        .container {
            padding: 20px 0px 50px 50px;
        }

        .no-border {
            border: none;
        }

        footer {
            position: fixed;
            bottom: 0px;
            left: 0px;
            right: 0px;
            padding: 0px;
            margin: 0 !important;
            color: black;
            text-align: center;
        }

        table.tbl-footer {
            background-color: #1d83bc;
            padding: 7px 12px;
            margin: 0;
            width: 100%;
            border: none;
        }

        table.tbl-footer tr td, table.tbl-footer tr td a {
            color: #ffffff;
            border: none;
            font-size: 14px;
        }

        table.tbl-disclaimer {
            text-align: left;
            width: 100%;
            border: none;
            padding: 10px 50px;
            font-size: 14px;
            margin: 0;
        }
        .payment-invoice-section {
            text-align: left;
            width: 90%;
            max-width: 1150px;
            margin: 0 auto;
        }

        table.tbl-bank-details {
            text-align: left;
            width: 100%;
            padding-left: 50px;
            font-size: 14px;
            line-height: normal;
            margin: 0;
        }

        table.tbl-bank-details tr th , table.tbl-bank-details tr td   {
            padding: 10px 10px ;
        }

        table.tbl-bank-details .heading {
           color: #1d83bc;
            font-size: 16px;
            font-weight: 700;
        }

        table.tbl-bank-details p {
            text-align: left;
            font-size: 14px;
            margin: 0;
            padding: 2px 0;
            line-height: normal;
        }

        table.tbl-payment-invoice {
            text-align: left;
            min-width: 100%;
            width: 100%;
            /*padding-left: 50px;
            padding-right: 50px;*/
            font-size: 14px;
            margin: 0 auto;
        }
        table.tbl-payment-invoice tr th , table.tbl-payment-invoice tr td   {
            padding: 5px 10px ;
        }
        table.tbl-payment-invoice tfoot tr th {
            border-top: 1px solid black;
        }
        .border-top {
            border-top: 1px solid black;
        }
        table.tbl-payment-invoice .remarks {
            width: 60%;
            font-size: 14px;
            text-align: left;
        }
        table.tbl-payment-invoice .payment-heading {
            width: 20%;
            font-size: 14px;
            text-align: left;
        }
        table.tbl-payment-invoice .amount {
            width: 20%;
            font-size: 14px;
            text-align: right;
        }

        .text-left {
            text-align: left !important;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .badge-success {
            color: #fff;
            background-color: #1d83bc;
        }

        .badge {
            display: inline-block;
            padding: 0.25em 0.4em;
            font-size: 50%;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
        }
        .tbl-customer{

        }
        .tbl-payment-details{
            margin: 0;
            margin-top: 30px;

        }
        table.tbl-payment-details tr th , table.tbl-payment-details tr td   {
            padding: 5px 10px ;
        }
        .customer {
            width: 50%;
            font-size: 14px;
            text-align: left;
        }

        .date {
            width: 20%;
            font-size: 14px;
            text-align: left;
        }
        .advisor-image img{
            width: 75px;
        }
    </style>
</head>

<body>

@php
    $websitURL = config('constants.AFIA_WEBSITE_DOMAIN');
@endphp


<footer>
    <div class="payment-invoice-section">
        <table class="table-fixed no-border tbl-payment-invoice border-top">

            <tbody>
            <tr class="">
                <th class="remarks">
                    Remarks:
                </th>
                <td class="payment-heading">
                    Sub Total
                </td>
                <td class="amount">
                    1000
                </td>
            </tr>
            <tr>
                <th>

                </th>
                <td  class="payment-heading">
                    Vat
                </td>
                <td class="amount">
                    50
                </td>
            </tr>


            </tbody>
            <tfoot>
            <tr class="border-top">
                <td></td>
                <th  class="payment-heading  text-medium">
                    Total Due:
                </th>
                <th class="amount text-medium">
                    1050
                </th>
            </tr>
            </tfoot>
        </table>
    </div>

    <table class="table-fixed no-border tbl-bank-details">

        <tbody>
        <tr class="border-top">
            <td>
                 <p class="heading">Payment Details:</p>
                 <p>Bank Name: EMIRATES NBD </p>
                 <p>Account Holder: AFIA INSURANCE BROKERAGE SERVICES LLC</p>
                 <p>Account Number: 1011170518302 </p>
                 <p>IBAN: AE650260001011170518302  </p>
                 <p> SWIFT Code: EBILAEAD </p>
            </td>

        </tr>


        </tbody>

    </table>

    <table class="tbl-disclaimer">
        <tr>
            <td class="text-left">
                This document is issued for the sole purpose of collection of premium on behalf of Oriental Insurance Company LTD, and should not be construed as an Official Tax Invoice compliant with FTA regulations. To receive Tax Invoice and avail Input Vat credit,  please reach out to your contact in AFIA who will obtain the document from the Insurer and send it across to you.
            </td>
        </tr>
    </table>
    <table class="tbl-footer">
        <tr>
            <td colspan="2" class="text-center"><h4>InsuranceMarket.ae is the registered trademark of AFIA Insurance Brokerage Services LLC</h4></td>
        </tr>
        <tr>
            <td colspan="2" class="text-center"><h6>27th Floor, Control Tower, Motor City,Dubai, United Arab Emirates, P.O Box 26423 | Tel: <a class="text-xxs" href="tel:+800253733">800 ALFRED (800-253-733)</a> | <a class="text-xxs" href="https://insurancemarket.ae">www.insurancemarket.ae</a></h6></td>
        </tr>
        <tr>
            <td class="text-left">UAE Central Bank Registration number 85</td>
            <td class="text-right">Insurance Advisor: Navya Shetty </td>
            <td class="text-right advisor-image" rowspan="4">
                <img class="im-logo" src="{{'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/proforma-payment/advisor.png')))}}" />
            </td>
        </tr>
        <tr>
            <td class="text-left">Registered member of the Emirates Insurance Association</td>
            <td class="text-right">Email: <a href="mailto:navya.shetty@insurancemarket.ae ">navya.shetty@insurancemarket.ae </a></td>
        </tr>
        <tr>
            <td class="text-left">Department of Economy & Tourism in Dubai Trade License number 238534</td>
            <td class="text-right">Mobile Number: <a href="tel:03008332905">03008332905</a> </td>
        </tr>
        <tr>
            <td class="text-left">Holder of Health Insurance Intermediary Permit ID Number BRK-00003 from Dubai Health Authority </td>
            <td class="text-right">Direct Line: <a href="tel:048185663">048185663</a> </td>
        </tr>
        <tr>
            <td class="text-left">Registered member of the Insurance Business Group under the Dubai Chamber of Commerce and Industry </td>

        </tr>
    </table>
</footer>

<div class="font">

    <div class="header">
        <div class="logo">
            <img class="im-logo" src="{{'data:image/png;base64,'.base64_encode(file_get_contents(getIMLogo(true)))}}" />
        </div>
        <h3>Proforma Payment Request</h3>
    </div>

    <div class="container">
        <table class="table-fixed no-border text-left tbl-customer">
            <tbody class="no-border">
            <tr class="text-left">

                <th class="customer">
                    Customer:
                </th>
                <th class="date">
                    Data:
                </th>

                <td class="date">
                    2024-03-01
                </td>


            </tr>

            <tr>

                <td class="customer">
                    Muhammad Ali
                </td>

                <th class="date">
                    Reference Number:
                </th>

                <td class="date">
                    1321321231312313123
                </td>

            </tr>
            <tr>

                <td class="customer">
                    Flat 201, Building 4c, lane 3
                </td>
            </tr>
            <tr>
                <td class="customer">
                    ittehad road, Dubai
                </td>
            </tr>

            </tbody>
        </table>

        <table class="table-fixed no-border tbl-payment-details">
            <thead class="blue-box">
            <tr>
                <th>
                    Payment
                </th>
                <th>
                    Due Data
                </th>
                <th>
                    Description
                </th>
                <th>
                    Amount
                </th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    1
                </td>
                <td>
                    01-03-2024
                </td>
                <td>
                    Property Insurance <br />
                    alliance Insurance
                </td>
                <td>
                    1000
                </td>
            </tr>

            </tbody>
        </table>

    </div>
</div>

</body>
</html>
