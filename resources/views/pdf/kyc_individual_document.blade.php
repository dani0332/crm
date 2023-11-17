<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC Individual</title>
    <link rel="stylesheet" href="{{ public_path('css/font-family-inter.css') }}">
    <style>
        #header {
            margin-top: -34px;
            text-align: center;
        }

        #footer {
            background-color: rgb(29 131 188);
            color: white;
            width: 660px;
            padding: 10px; /* Add padding to create space */
        }

        #footer>h6 {
            margin: 0;
            font-weight: 400;
            font-size: 7px;
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
            padding-left: 6px;
        }

        .text-center {
            text-align: center;
        }

        table {
            border-spacing: 3px;
        }

        tr td {
            font-size: 9px;
            color: #44475C;
        }

        tr td:first-child {
            padding-left: 10px;
            font-weight: 500;
            padding-right: 50px;
        }

        tr td:nth-child(2) {
            background: #ffffff;
            border-style: solid;
            border-color: #1d83bc;
            border-width: 0.1px;
            color: black;
            font-weight: 500;
            padding-left: 5px;
            width: 320px;
        }

        .no-border {
            border-style: none !important;
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
        <img src="{{ public_path('images/im_logo_21k.png') }}" alt="Insurance Market Logo" width="500">
    </div>
    <hr>

    <div id="content">
        <p class="main-heading">KYC Form</p>
        <p class="sub-heading">Personal Information:</p>

        <table>
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
            <input type="radio" @checked($data['income_source'] == 'employed') /> &nbsp;&nbsp; <strong>Employed</strong>
            <input type="radio" @checked($data['income_source'] == 'business') /> &nbsp;&nbsp; <strong>Business Owner or Partner</strong>
        </span>

        <table>
            <tbody>
                @if($data['income_source'] == 'employed')
                    <tr>
                        <td>Employer:</td>
                        <td>{{ $data['company_name'] }}</td>
                    </tr>
                    <tr>
                        <td>Professional job title:</td>
                        <td>{{ $data['professional_title'] }}</td>
                    </tr>
                    <tr>
                        <td>Employment Sector:</td>
                        <td>{{ $data['employment_sector_text'] }}</td>
                    </tr>
                @endif
                @if($data['income_source'] == 'business')
                    <tr>
                        <td>Company name:</td>
                        <td>{{ $data['company_name'] }}</td>
                    </tr>
                    <tr>
                        <td>Trade License#:</td>
                        <td>{{ $data['trade_license'] }}</td>
                    </tr>
                    <tr>
                        <td>Position in company:</td>
                        <td>{{ $data['company_position_text'] }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        <p class="sub-heading">For compliance use only:</p>
        <table>
            <tbody>
                <tr>
                    <td>Is the customer a PEP?</td>
                    <td class="no-border" style="margin-left: 50px;">
                        <input type="radio" @checked(isset($data['pep']) && $data['pep'] == 'Yes') /> &nbsp;&nbsp; <strong>Yes</strong>
                        <input type="radio" @checked(isset($data['pep']) && $data['pep'] == 'No') /> &nbsp;&nbsp; <strong>No</strong>
                    </td>
                </tr>
                <tr>
                    <td>Is the customer or business subject to <br>
                        financial sanctions/or connected with <br>
                        prescribed terrorist organizations? <br>
                    </td>
                    <td class="no-border" style="margin-top: 18px; margin-left: 50px;">
                        <input type="radio" @checked(isset($data['financial_sanctions']) && $data['financial_sanctions'] == 'Yes') /> &nbsp;&nbsp; <strong>Yes</strong>
                        <input type="radio" @checked(isset($data['financial_sanctions']) && $data['financial_sanctions'] == 'No') /> &nbsp;&nbsp; <strong>No</strong>
                    </td>
                </tr>
                <tr>
                    <td>Does the customer have dual nationality?</td>
                    <td class="no-border" style="margin-left: 50px;">
                        <input type="radio" @checked(isset($data['dual_nationality']) && $data['dual_nationality'] == 'Yes') /> &nbsp;&nbsp; <strong>Yes</strong>
                        <input type="radio" @checked(isset($data['dual_nationality']) && $data['dual_nationality'] == 'No') /> &nbsp;&nbsp; <strong>No</strong>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <p>This is the KYC information we have on record for you as per the Central Bank of the UAE Regulations. If any
        updates are
        required, please contact your insurance advisor.</p>
    <p>
        <i>This KYC was authorized on DD/MM/YY at HH:MM:SS. IP Address: XXX.XXX.XXX.XX</i>
    </p>

    <div id="footer">
        <h5 class="text-center">InsuranceMarket.ae is the registered trademark of AFIA Insurance Brokerage Services LLC
        </h5>
        <h6 class="pl-6">
            <u>UAE Central Bank</u> Registration number 85
        </h6>
        <h6 class="pl-6">
            Registered member of the <u>Emirates Insurance Association</u>
            <span style="float: right; margin-right: 6px;">27th floor, Control Tower, Motor City</span>
        </h6>
        <h6 class="pl-6">
            <u>Department of Economy & Tourism in Dubai</u> Trade License number 238534
            <span style="float: right; margin-right: 6px;">Dubai, United Arab Emirates, PO Box - 26423</span>
        </h6>
        <h6 class="pl-6">
            Holder of Health Insurance Intermediary Permit ID Number BRK-00003 from <u>Dubai Health Authority</u>
            <span style="float: right; margin-right: 6px;">Tel: 800 ALFRED (800 253 733)</span>
        </h6>
        <h6 class="pl-6" style="padding-bottom: 5px;">
            Registered member of <u>Insurance Business Group</u> under the <u>Dubai Chamber of Commerce and Industry</u>
            <span style="float: right; margin-right: 6px;">insurancemarket.ae</span>
        </h6>
    </div>
</body>

</html>