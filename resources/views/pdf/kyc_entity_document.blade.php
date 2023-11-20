<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
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
        .text-center {
            text-align: center;
        }
        .ml-2 {
            margin-left: 20px;
        }

        td > div > span {
            font-size: 14px !important;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1 style="margin-top: -6px;">KYC Entity</h1>
        <div class="container" style="margin-top: -20px;">
            <table>
                <tr>
                    <td style="width: 25%;">
                        <div class="data">
                            Customer ID:
                            <span class="value">{{ $data['customer_id'] }}</span>
                        </div>
                    </td>
                    <td style="width: 38%;">
                        <div class="data">
                            First Name:
                            <span class="value">{{ $data['first_name'] }}</span>
                        </div>
                    </td>
                    <td style="width: 38%;">
                        <div class="data">
                            Last Name:
                            <span class="value">{{ $data['last_name'] }}</span>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="width: 33%;" colspan="2">
                        <div class="data">
                            Company Name:
                            <span class="value">{{ $data['company_name'] }}</span>
                        </div>
                    </td>
                    <td style="width: 60%;" colspan="2">
                        <div class="data">
                            Country of corporation:
                            <span class="value">{{ $data['corporation_country'] }}</span>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="width: 50%;" colspan="2">
                        <div class="data">
                            Legal structure:
                            <span class="value">{{ $data['legal_structure_text'] }}</span>
                        </div>
                    </td>
                    <td style="width: 50%;" colspan="2">
                        <div class="data">
                            Industry Type:
                            <span class="value">{{ $data['industry_type_code'] }}</span>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="width: 66%; margin-bottom: 2px;" colspan="3">
                        <div class="data">
                            Registered address:
                            <span class="value">{{ $data['registered_address'] }}</span>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td colspan="3" style="width: 66%;">
                        <div class="data">
                            Communication address:
                            <span class="value">{{ $data['communication_address'] }}</span>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="width: 33%;">
                        <div class="data">
                            Mobile number:
                            <span class="value">{{ $data['mobile_number'] }}</span>
                        </div>
                    </td>
                    <td style="width: 33%;" colspan="2">
                        <div class="data">
                            Email:
                            <span class="value">{{ $data['email'] }}</span>
                        </div>
                    </td>
                    <td style="width: 30%;">
                        <div class="data">
                            Website:
                            <span class="value">{{ $data['website'] }}</span>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="width: 50%; margin-bottom: 2px;" colspan="2">
                        <div class="data">
                            ID / Document Type:
                            <span class="value">{{ $data['document_type_text'] }}</span>
                        </div>
                    </td>
                    <td style="width: 50%;" colspan="2">
                        <div class="data">
                            ID number:
                            <span class="value">{{ $data['id_number'] }}</span>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="width: 50%; margin-bottom: 2px;" colspan="2">
                        <div class="data">
                            ID / Document Issue Date:
                            <span class="value">{{ dateFormat($data['id_issue_date']) }}</span>
                        </div>
                    </td>
                    <td style="width: 50%;" colspan="2">
                        <div class="data">
                            ID / Document Expiry Date:
                            <span class="value">{{ dateFormat($data['id_expiry_date']) }}</span>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="width: 50%; margin-bottom: 2px;" colspan="2">
                        <div class="data">
                            Place of issue:
                            <span class="value">{{ dateFormat($data['issuance_place_text']) }}</span>
                        </div>
                    </td>
                </tr>

                <tr style="margin-bottom: 2px;">
                    <td style="width: 80%;" colspan="3">
                        <div class="data">
                            ID issuing authority:
                            <span class="value">{{ $data['issuing_authority_text'] }}</span>
                        </div>
                    </td>
                </tr>
                <tr style="margin-bottom: -25px; margin-top: -5px;">
                    <td colspan="4">
                        <h2 class="text-center">UBO and Manager details</h2>
                    </td>
                </tr>
                <tr>
                    <td style="width: 25%;">
                        <div>
                            Name:
                            <span class="value">{{ $data['manager_name'] }}</span>
                        </div>
                    </td>
                    <td style="width: 25%;">
                        <div>
                            Nationality:
                            <span class="value">{{ $data['manager_country'] }}</span>
                        </div>
                    </td>
                    <td style="width: 25%;">
                        <div>
                            DOB:
                            <span class="value">{{ dateFormat($data['manager_dob']) }}</span>
                        </div>
                    </td>
                    <td style="width: 25%;">
                        <div>
                            Position:
                            <span class="value">{{ $data['manager_position_text'] }}</span>
                        </div>
                    </td>
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
                            <input type="radio" @checked(isset($data['pep']) && $data['pep'] == 1)/>
                            <span>YES</span>
                            <input type="radio" class="ml-2" @checked(isset($data['pep']) && $data['pep'] == 2)/>
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
                        <input type="radio" @checked(isset($data['financial_sanctions']) && $data['financial_sanctions'] == 1)/>
                        <span>YES</span>
                        <input type="radio" class="ml-2" @checked(isset($data['financial_sanctions']) && $data['financial_sanctions'] == 2)/>
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
                        <input type="radio" @checked(isset($data['dual_nationality']) && $data['dual_nationality'] == 1)/>
                        <span>YES</span>
                        <input type="radio" class="ml-2" @checked(isset($data['dual_nationality']) && $data['dual_nationality'] == 2)/>
                        <span>No</span>
                    </td>
                </tr>

            </table>
        </div>
    </div>
</body>
</html>
