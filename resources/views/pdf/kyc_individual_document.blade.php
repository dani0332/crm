<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC Individual</title>
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
        <p class="main-heading">KYC Form</p>
        <p class="sub-heading">Personal Information:</p>

        <table class="custom-table">
            <tbody>
                <tr>
                    <td>Account opening number:</td>
                    <td>{{ $data['customer_id'] }}</td>
                </tr>
                <tr>
                    <td>Full name:</td>
                    <td>{{ $data['first_name'] . ' ' . $data['last_name'] }}</td>
                </tr>
                <tr>
                    <td>Mobile number:</td>
                    <td>{{ $data['mobile_number'] }}</td>
                </tr>
                <tr>
                    <td>Email address:</td>
                    <td>{{ $data['email'] }}</td>
                </tr>
                <tr>
                    <td>Date of birth:</td>
                    <td>{{ dateFormat($data['dob']) }}</td>
                </tr>
                <tr>
                    <td>Nationality:</td>
                    <td>{{ $data['nationality_text'] }}</td>
                </tr>
                <tr>
                    <td>ID type:</td>
                    <td>{{ $data['id_type_text'] }}</td>
                </tr>
                <tr>
                    <td>ID number:</td>
                    <td>{{ $data['id_number'] }}</td>
                </tr>
                <tr>
                    <td>ID issue date</td>
                    <td>{{ dateFormat($data['id_issue_date']) }}</td>
                </tr>
                <tr>
                    <td>ID expiry date</td>
                    <td>{{ dateFormat($data['id_expiry_date']) }}</td>
                </tr>
                <tr>
                    <td>Country of residence:</td>
                    <td>{{ $data['country_name'] }}</td>
                </tr>
                <tr>
                    <td>Product type:</td>
                    <td>{{ $data['product_type'] }}</td>
                </tr>
                <tr>
                    <td>Premium:</td>
                    <td>{{ $data['premium'] }}</td>
                </tr>
                <tr>
                    <td>Mode of payment:</td>
                    <td>{{ $data['payment_method'] }}</td>
                </tr>
                <tr>
                    <td>Mode of contact:</td>
                    <td>{{ $data['mode_of_contact_text'] }}</td>
                </tr>
            </tbody>
        </table>

        <span>
            <span class="sub-heading" style="margin-right: 90px;">Source of income:</span>
            <input type="radio" style="margin-bottom: -5px !important;" @checked($data['income_source'] == 'employed') /> &nbsp;&nbsp; <strong style="font-size: 10px;">Employed</strong>
            <input type="radio" style="margin-bottom: -5px !important; margin-left: 10px;" @checked($data['income_source'] == 'business') /> &nbsp;&nbsp; <strong style="font-size: 10px;">Business Owner or Partner</strong>
        </span>

        <table class="custom-table">
            <tbody>
                @if ($data['income_source'] == 'employed')
                    <tr>
                        <td style="padding-right: 100px !important;">Employer:</td>
                        <td style="margin-left: 100px;">{{ $data['company_name'] }}</td>
                    </tr>
                    <tr>
                        <td style="padding-right: 100px !important;">Professional job title:</td>
                        <td style="margin-left: 100px;">{{ $data['professional_title_text'] }}</td>
                    </tr>
                    <tr>
                        <td style="padding-right: 100px !important;">Employment Sector:</td>
                        <td style="margin-left: 100px;">{{ $data['employment_sector_text'] }}</td>
                    </tr>
                @endif
                @if ($data['income_source'] == 'business')
                    <tr>
                        <td style="padding-right: 100px !important;">Company name:</td>
                        <td style="margin-left: 100px;">{{ $data['company_name'] }}</td>
                    </tr>
                    <tr>
                        <td style="padding-right: 100px !important;">Trade License#:</td>
                        <td style="margin-left: 100px;">{{ $data['trade_license'] }}</td>
                    </tr>
                    <tr>
                        <td style="padding-right: 100px !important;">Position in company:</td>
                        <td style="margin-left: 100px;">{{ $data['company_position_text'] }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        <p class="sub-heading">For compliance use only:</p>
        <table>
            <tbody>
                <tr>
                    <td>Is the customer a PEP?</td>
                    <td class="no-border">
                        <input type="radio" @checked(isset($data['pep']) && $data['pep'] == 1) />
                    </td>
                    <td>
                        <strong>Yes</strong>
                    </td>
                    <td>
                        <input type="radio" @checked(isset($data['pep']) && $data['pep'] == 2) />
                    </td>
                    <td>
                        <strong>No</strong>
                    </td>
                </tr>
                <tr>
                    <td>Is the customer or business subject to <br>
                        financial sanctions/or connected with <br>
                        prescribed terrorist organizations? <br>
                    </td>
                    <td class="no-border">
                        <input type="radio" @checked(isset($data['financial_sanctions']) && $data['financial_sanctions'] == 1) />
                    </td>
                    <td>
                        <strong>Yes</strong>
                    </td>
                    <td>
                        <input type="radio" @checked(isset($data['financial_sanctions']) && $data['financial_sanctions'] == 2) />
                    </td>
                    <td>
                        <strong>No</strong>
                    </td>
                </tr>
                <tr>
                    <td>Does the customer have dual nationality?</td>
                    <td class="no-border">
                        <input type="radio" @checked(isset($data['dual_nationality']) && $data['dual_nationality'] == 1) />
                    </td>
                    <td>
                        <strong>Yes</strong>
                    </td>
                    <td>
                        <input type="radio" @checked(isset($data['dual_nationality']) && $data['dual_nationality'] == 2) />
                    </td>
                    <td>
                        <strong>No</strong>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <p style="font-size: 11px;">This is the KYC information we have on record for you as per the Central Bank of the UAE
        Regulations. If any
        updates are
        required, please contact your insurance advisor.</p>
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
