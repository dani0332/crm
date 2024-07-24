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

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #1d83bc;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #1d83bc;
            color: white;
        }

        #footer {
            margin: 300px -50px 0 -50px !important;
            background-color: rgb(29 131 188);
            color: white;
            width: 800px !important;
            position: fixed;
            bottom: -33;
        }

        #footer > h6 {
            margin: 0;
            font-weight: 400;
            font-size: 9px;
        }

        .pl-6 {
            padding-left: 5px;
        }

        .text-center {
            text-align: center;
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
        .table-height{
            height:55%;
        }
    </style>
</head>
<body>
    <table class="header" style="border: none;">
        <tbody>
            <tr style="border: none;">
                <td style="width: 70%; border: none; vertical-align:top;">
                    <img src="{{'data:image/png;base64,'.base64_encode(file_get_contents(getIMLogo(true)))}}" alt="Insurance Market Logo" width="300">
                </td>
                <td style="vertical-align:middle; text-align:right; border: none; font-size:20px;">
                <strong>Payment Receipt</strong>
                </td>
            </tr>
        </tbody>
    </table>
    <hr>
    <div id="content">
        
        <table style="border: none;">
            <tbody>
                <tr style="border: none;">
                    <td style="margin: 0; border: none; text-align: left;"><strong>CUSTOMER:</strong></td>
                    <td style="margin: 0; border: none; text-align: left;">{{ ucfirst($data['customer_name']) }}</td>
                
                    <td style="margin: 0; border: none; text-align: left;"><strong>RECEIVED DATE:</strong></td>
                    <td style="margin: 0; border: none; text-align: left;">{{ $data['captured_at'] }}</td>
                </tr>
                <tr style="border: none;">
                    <td style="margin: 0; border: none; text-align: left;"><strong>RECEIPT NUMBER:</strong></td>
                    <td style="margin: 0; border: none; text-align: left;">{{ $data['receipt_number'] }}</td>
            
                    <td style="margin: 0; border: none; text-align: left;"><strong>PAID BY:</strong></td>
                    <td style="margin: 0; border: none; text-align: left;">{{ $data['payment_method'] }}</td>
                </tr>
            </tbody>
        </table>
        <br>
        <table class="table-height">
            <thead>
                <tr>
                    <th style="width: 70%;">ORDER DETAILS</th>
                    <th style="text-align:right;">AMOUNT</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="table-height" style="vertical-align:top;">
                        Order number: {{ $data['order_number'] }}<br>
                        Order date and time: {{ $data['order_at'] }}<br>
                        Insurance company: {{ $data['insurance_company'] }}<br>
                        Type of insurance: {{ $data['type_of_insurance'] }}<br>
                    </td>
                    <td class="table-height" style="vertical-align:top; text-align:right;">{{ $data['order_amount'] }} AED</td>
                </tr>
            </tbody>
        </table>

        <table style="border: none;">
            <tbody>
                <tr style="border: none;">
                    <td style="width: 50%; border: none; vertical-align:top;">
                    <strong>Remarks:</strong> {{ $data['remarks'] }}
                    </td>
                    <td style="vertical-align:top; text-align:right; border: none;">
                        <strong>Total Amount(AED): {{ $data['order_amount'] }}</strong>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <p style="font-size: 11px; text-align: center;">
        <i>***This is a system generated receipt, manual signature is not required***</i>
    </p>
    <div id="footer">
        <h5 class="text-center">InsuranceMarket.ae is the registered trademark of AFIA
            Insurance Brokerage Services LLC
            <h6 class="text-center">
                27th floor, Control Tower, Motor City, Dubai, United Arab Emirates, PO Box - 26423 | Tel: 800 ALFRED (800 253 733) | insurancemarket.ae
            </h6>
        </h5>
        <table style="width: 100%; font-size: 8px; padding:2px;">
        <tr>
            <td style="width: 62%; vertical-align: top;">
                
                UAE Central Bank Registration number 85<br>
                Registered member of the Emirates Insurance Association<br>
                Department of Economy & Tourism in Dubai Trade License number 238534<br>
                Holder of Health Insurance Intermediary Permit ID Number BRK-00003 from Dubai Health Authority <br>
                Registered member of the Insurance Business Group under the Dubai Chamber of Commerce and Industry
            </td>
            @if (!empty($data['advisor_name']))
                <td style="width: 30%; vertical-align:top; text-align:right;">
                    Insurance Advisor: {{ $data['advisor_name'] }}<br>
                    Email: {{ $data['advisor_email'] }}<br>
                    Mobile Number: {{ $data['advisor_mobile_no'] }}<br>
                    Direct Line: {{ $data['advisor_landline_no'] }}<br>
                    <br>                
                </td>

                <td style="width: 8%; vertical-align:top">            
                    @if (!empty($data['profile_photo_path']))
                        <img style="border-radius: 50%; width: 60px; height: 60px;" src="{{'data:image/png;base64,'.base64_encode(file_get_contents($data['profile_photo_path']))}}" alt="Insurance Market Logo">
                    @endif
                </td>
            @endif
        </tr>
      </table>
    </div>  
</body>
</html>
