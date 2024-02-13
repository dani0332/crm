<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Reciept</title>
    <link rel="stylesheet" href="{{ public_path('css/font-family-inter.css') }}">
    <style>
        @font-face {
            font-family: 'DejaVu Sans';
            font-style: normal;
            font-weight: normal;
        }

        body {
            font-family: 'DejaVu Sans', serif !important;
            padding: 0;
        }

        #header {
            margin-top: -34px;
            text-align: center;
        }

        #footer {
            margin: 100px -50px 0 -45px !important;
            background-color: rgb(29 131 188);
            color: white;
            width: 800px !important;
            position: fixed;
            bottom: 0;
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
            border-spacing: 10px;
        }

        tr td {
            font-size: 10px;
            color: #44475C;
        }

        tr td:first-child {
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
    </style>
</head>

<body>
    <div id="header">
        <img src="{{'data:image/png;base64,'.base64_encode(file_get_contents(getIMLogo(true)))}}" alt="Insurance Market Logo" width="300">
    </div>
    <hr>
    <div id="content">
        <p class="main-heading">Payment Reciept</p>
        <p class="sub-heading">Personal Information:</p>

        <table class="custom-table">
            <tbody>
                <tr>
                    <td>Account opening number:</td>
                    <td>{{ $data['order_amount'] }}</td>
                </tr>

                <tr>
                    <td>Date of birth:</td>
                    <td>{{ dateFormat($data['payment_method']) }}</td>
                </tr>
                
                <tr>
                    <td>Date of birth:</td>
                    <td>{{ dateFormat($data['dob']) }}</td>
                </tr>
               
            </tbody>
        </table>
        
    </div>

   
    <p style="font-size: 11px;">
        <i>This KYC was authorized on {{ date('d/m/y') }} at {{ date('H:i:s') }}.</i>
    </p>

    <div id="footer">
        <h5 class="text-center" style="padding-top: 5px;">InsuranceMarket.ae is the registered trademark of AFIA
            Insurance Brokerage Services LLC</h5>
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
