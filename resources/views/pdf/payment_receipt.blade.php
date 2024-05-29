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

        .pl-6 {
            padding-left: 8px;
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
    </style>
</head>
<body>    
    <table class="header" style="border: none;">           
        <tbody>
            <tr style="border: none;">
                <td style="width: 70%; border: none; vertical-align:top;">
                <img src="{{'data:image/png;base64,'.base64_encode(file_get_contents(getIMLogo(true)))}}" alt="Insurance Market Logo">
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
        <table style="height:60%">
            <thead>
                <tr>
                    <th style="width: 70%;">ORDER DETAILS</th>
                    <th style="text-align:right;">AMOUNT</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="vertical-align:top; height:60%">
                        Order number: {{ $data['order_number'] }}<br>
                        Order date And time: {{ $data['order_at'] }}<br>
                        Insurance company: {{ $data['insurance_company'] }}<br>
                        Type of insurance: {{ $data['type_of_insurance'] }}<br>
                    </td>
                    <td style="vertical-align:top; text-align:right; height:60%">{{ $data['order_amount'] }} AED</td>
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
        <i>***This is system generated receipt.Manual signature is not required***</i>
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