<!DOCTYPE html>
<html>
<head>
    <title>KYC Document</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
        }
        .card {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding-left: 20px;
            padding-right: 20px;
            width: 95%;
            text-align: center;
            margin: 0 auto;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            text-align: left;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table tr td {
            border-right-style: solid;
            border-color: #f5f5f5;
            border-width: 18px;
        }
        .data {
            border: 1px solid rgb(230, 164, 41);
            border-radius: 6px;
            padding: 4px;
            text-align: left;
        }
        .value {
            text-decoration: underline;
            font-weight: bold;
        }
        .font-14 {
            font-size: 14px !important;
        }
        .text-center {
            text-align: center;
        }
        .ml-2 {
            margin-left: 20px;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1 style="margin-top: -6px;">KYC Individual</h1>
        <div class="container" style="margin-top: -20px;">
            <table>
                <tr>
                    <td style="width: 25%;">
                        <div class="data">
                            Customer ID:
                            <span class="value">{{ $data['customer_id'] }}</span>
                        </div>
                    </td>
                    <td style="width: 25%;">
                        <div class="data">
                            First Name:
                            <span class="value">{{ $data['first_name'] }}</span>
                        </div>
                    </td>
                    <td style="width: 25%;">
                        <div class="data">
                            Last Name:
                            <span class="value">{{ $data['last_name'] }}</span>
                        </div>
                    </td>
                    <td style="width: 25%;">
                        <div class="data">
                            Date of birth:
                            <span class="value">{{ dateFormat($data['dob']) }}</span>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="width: 33%;">
                        <div class="data">
                            Nationality:
                            <span class="value">{{ $data['nationality_text'] }}</span>
                        </div>
                    </td>
                    <td style="width: 33%;" colspan="2">
                        <div class="data">
                            Country of residence:
                            <span class="value">{{ $data['country_name'] }}</span>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="width: 33%;" colspan="2">
                        <div class="data">
                            Place of birth:
                            <span class="value">{{ $data['birth_place'] }}</span>
                        </div>
                    </td>
                    <td style="width: 33%;" colspan="2">
                        <div class="data">
                            Residence status:
                            <span class="value">{{ $data['resident_status_text'] }}</span>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td colspan="3" style="width: 66%;">
                        <div class="data">
                            Residential address:
                            <span class="value">{{ $data['residential_address'] }}</span>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="width: 33%;">
                        <div class="data">
                            Mobile number:
                            <span class="value font-14">{{ $data['mobile_number'] }}</span>
                        </div>
                    </td>
                    <td style="width: 33%;">
                        <div class="data">
                            Email:
                            <span class="value font-14">{{ $data['email'] }}</span>
                        </div>
                    </td>
                    <td style="width: 33%;">
                        <div class="data">
                            Customer tenure:
                            <span class="value">{{ $data['customer_tenure'] }}</span>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="width: 33%;">
                        <div class="data">
                            ID type:
                            <span class="value">{{ $data['id_type_text'] }}</span>
                        </div>
                    </td>
                    <td style="width: 33%;">
                        <div class="data">
                            ID number:
                            <span class="value">{{ $data['id_number'] }}</span>
                        </div>
                    </td>
                    <td style="width: 33%;">
                        <div class="data">
                            ID issue date:
                            <span class="value">{{ dateFormat($data['id_issue_date']) }}</span>
                        </div>
                    </td>
                    <td style="width: 33%;">
                        <div class="data">
                            ID expiry date:
                            <span class="value">{{ dateFormat($data['id_expiry_date']) }}</span>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="width: 50%;" colspan="2">
                        <div class="data">
                            Mode of contact:
                            <span class="value">{{ $data['mode_of_contact_text'] }}</span>
                        </div>
                    </td>
                    <td style="width: 50%;" colspan="2">
                        <div class="data">
                            Mode of delivery:
                            <span class="value">{{ $data['mode_of_delivery_text'] }}</span>
                        </div>
                    </td>
                </tr>
                <tr style="margin-bottom: -25px;">
                    <td colspan="4">
                        <h2 class="text-center">Source of Income</h2>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div>
                            Employed:
                            <input type="radio" class="value" @checked($data['income_source'] == 'employed') />
                        </div>
                    </td>
                    @if($data['income_source'] == 'employed')
                        <td colspan="3">
                            <div class="data">
                                Employer/Company name:
                                <span class="value">{{ $data['company_name'] }}</span>
                            </div>
                            <br>
                            <div class="data">
                                Professional Job title:
                                <span class="value">{{ $data['professional_title'] }}</span>
                            </div>
                            <br>
                            <div class="data">
                                Employment sector:
                                <span class="value">{{ $data['employment_sector_text'] }}</span>
                            </div>
                        </td>
                    @endif
                </tr>
                <tr>
                    <td>
                        <div>
                            Business owner / Partner:
                            <input type="radio" class="value" @checked($data['income_source'] == 'business') />
                        </div>
                    </td>
                    @if($data['income_source'] == 'business')
                        <td colspan="3">
                            <div class="data">
                                Company name:
                                <span class="value">{{ $data['company_name'] }}</span>
                            </div>
                            <br>
                            <div class="data">
                                Trade License#:
                                <span class="value">{{ $data['trade_license'] }}</span>
                            </div>
                            <br>
                            <div class="data">
                                Position in company:
                                <span class="value">{{ $data['company_position_text'] }}</span>
                            </div>
                        </td>
                    @endif
                </tr>

                <tr style="margin-bottom: -25px;">
                    <td colspan="4">
                        <h2 class="text-center">For compliance use only</h2>
                    </td>
                </tr>

                <tr>
                    <td colspan="3">
                        <div>
                            <span>Is the customer a PEP?</span>
                        </div>
                    </td>
                    <td>
                        <div>
                            <input type="radio" @checked(isset($data['pep']) && $data['pep'] == 'Yes')/>
                            <span>YES</span>
                            <input type="radio" class="ml-2" @checked(isset($data['pep']) && $data['pep'] == 'No')/>
                            <span>No</span>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td colspan="3">
                        <div>
                            <span>Is the customer or business subjected to financial sanctions / or
                            connected with prescribed terrorist organizations?</span>
                        </div>
                    </td>
                    <td>
                        <input type="radio" @checked(isset($data['financial_sanctions']) && $data['financial_sanctions'] == 'Yes')/>
                        <span>YES</span>
                        <input type="radio" class="ml-2" @checked(isset($data['financial_sanctions']) && $data['financial_sanctions'] == 'No')/>
                        <span>No</span>
                    </td>
                </tr>

                <tr>
                    <td colspan="3">
                        <div>
                            <span>Does the customer have dual nationality?</span>
                        </div>
                    </td>
                    <td>
                        <input type="radio" @checked(isset($data['dual_nationality']) && $data['dual_nationality'] == 'Yes')/>
                        <span>YES</span>
                        <input type="radio" class="ml-2" @checked(isset($data['dual_nationality']) && $data['dual_nationality'] == 'No')/>
                        <span>No</span>
                    </td>
                </tr>

            </table>
        </div>
    </div>
</body>
</html>
