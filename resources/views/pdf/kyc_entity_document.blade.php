<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <title>KYC Document</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
            margin-top: 260px;
        }

        .card {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            width: 95%;
            text-align: center;
        }

        .text-center {
            text-align: center;
        }

        .label {
            display: inline-block;
            width: auto; /* Adjust the width as needed */
        }

        .value {
            text-decoration: underline;
            font-weight: bold;
            display: inline-block;
        }

        .ml-2 {
            margin-left: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            text-align: left;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin: -10px; /* Negative margin to counteract padding on columns */
        }

        .col-md-3,
        .col-md-4,
        .col-md-6,
        .col-md-8 {
            box-sizing: border-box;
            padding: 10px; /* Padding for gutters */
        }

        .col-md-3 {
            flex: 0 0 25%; /* 3 columns */
        }

        .col-md-4 {
            flex: 0 0 33.33%; /* 4 columns */
        }

        .col-md-6 {
            flex: 0 0 50%; /* 2 columns */
        }

        .col-md-8 {
            flex: 0 0 66.66%; /* 2 columns */
        }

        .row > div > div {
            border: solid rgb(230, 164, 41) 1px;
            border-radius: 6px;
            padding-left: 2px;
        }

        .font-14 {
            font-size: 14px;
        }

        @media print {
            @page {
                size: landscape;
            }
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>KYC Entity</h1>
        <div class="container">
            <div class="row">
                <div class="col-md-3">
                    <div>
                        <span class="label">Customer ID:</span>
                        <span class="value">{{ $data['customer_id'] }}</span>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="div">
                        <span class="label">First Name:</span>
                        <span class="value">{{ $data['first_name'] }}</span>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="div">
                        <span class="label">Last Name:</span>
                        <span class="value">{{ $data['last_name'] }}</span>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="div">
                        <span class="label">Company Name:</span>
                        <span class="value">{{ $data['company_name'] }}</span>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="div">
                        <span class="label">Legal structure:</span>
                        <span class="value">{{ $data['legal_structure'] }}</span>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="div">
                        <span class="label">Industry Type:</span>
                        <span class="value">{{ $data['industry_type_code'] }}</span>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="div">
                        <span class="label">Country of corporation:</span>
                        <span class="value">{{ $data['corporation_country'] }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="div">
                        <span class="label">Registered address:</span>
                        <span class="value">{{ $data['registered_address'] }}</span>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="div">
                        <span class="label">Communication address:</span>
                        <span class="value font-14">{{ $data['communication_address'] }}</span>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="div">
                        <span class="label">Mobile number:</span>
                        <span class="value">{{ $data['mobile_number'] }}</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="div">
                        <span class="label">Email:</span>
                        <span class="value font-14">{{ $data['email'] }}</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="div">
                        <span class="label">Website:</span>
                        <span class="value">{{ $data['website'] }}</span>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="div">
                        <span class="label">ID / Document Type:</span>
                        <span class="value">{{ $data['id_document_type'] }}</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="div">
                        <span class="label">ID number:</span>
                        <span class="value">{{ $data['id_number'] }}</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="div">
                        <span class="label">ID / Document Issue Date:</span>
                        <span class="value">{{ dateFormat($data['id_issue_date']) }}</span>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="div">
                        <span class="label">ID / Document Expiry Date:</span>
                        <span class="value">{{ dateFormat($data['id_expiry_date']) }}</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="div">
                        <span class="label">Place of issue:</span>
                        <span class="value">{{ $data['place_of_issue'] }}</span>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="div">
                        <span class="label">ID issuing authority:</span>
                        <span class="value">{{ $data['issuing_authority'] }}</span>
                    </div>
                </div>
            </div>

            <h2 class="text-center">UBO and Manager details</h2>

            <div class="row">
                <div class="col-md-4">
                    <div class="div">
                        <span class="label">Name:</span>
                        <span class="value">{{ $data['manager_name'] }}</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="div">
                        <span class="label">Nationality:</span>
                        <span class="value">{{ $data['manager_country'] }}</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="div">
                        <span class="label">DOB:</span>
                        <span class="value">{{ dateFormat($data['manager_dob']) }}</span>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="div">
                        <span class="label">Position:</span>
                        <span class="value">{{ $data['manager_position'] }}</span>
                    </div>
                </div>
            </div>

            <h2 class="text-center">For compliance use only</h2>

            <div class="row">
                <div class="col-md-6">
                    <strong class="label">Is the customer a PEP?</strong>
                </div>
                <div class="col-md-4">
                    <input type="radio" @checked(isset($data['pep']) && $data['pep'] == 'Yes')/>
                    <span>YES</span>
                    <input type="radio" class="ml-2" @checked(isset($data['pep']) && $data['pep'] == 'No')/>
                    <span>No</span>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <strong class="label"
                    >Is the customer or business subjected to financial sanctions / or
                        connected with prescribed terrorist organizations?</strong
                    >
                </div>
                <div class="col-md-4">
                    <input type="radio" @checked(isset($data['financial_sanctions']) && $data['financial_sanctions'] == 'Yes')/>
                    <span>YES</span>
                    <input type="radio" class="ml-2" @checked(isset($data['financial_sanctions']) && $data['financial_sanctions'] == 'No')/>
                    <span>No</span>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <strong class="label">Is the customer a PEP?</strong>
                </div>
                <div class="col-md-4">
                    <input type="radio" @checked(isset($data['dual_nationality']) && $data['dual_nationality'] == 'Yes')/>
                    <span>YES</span>
                    <input type="radio" class="ml-2" @checked(isset($data['dual_nationality']) && $data['dual_nationality'] == 'No')/>
                    <span>No</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
