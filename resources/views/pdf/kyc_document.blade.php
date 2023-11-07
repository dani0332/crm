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

        .custom-row {
            display: flex;
            flex-wrap: wrap;
            margin: -10px; /* Negative margin to counteract padding on columns */
        }

        .custom-md-3,
        .custom-md-4,
        .custom-md-4,
        .custom-md-6,
        .custom-md-8 {
            box-sizing: border-box;
            padding: 10px; /* Padding for gutters */
        }

        .custom-md-3 {
            flex: 0 0 25%; /* 3 columns */
        }

        .custom-md-4 {
            flex: 0 0 33.33%; /* 4 columns */
        }

        .custom-md-6 {
            flex: 0 0 50%; /* 2 columns */
        }

        .custom-md-8 {
            flex: 0 0 66.66%; /* 2 columns */
        }

        .custom-row > div > div {
            border: solid rgb(230, 164, 41) 1px;
            border-radius: 6px;
            padding-left: 2px;
        }

        .font-14 {
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>KYC Individual</h1>
        <div class="container">
            <div class="custom-row">
                <div class="custom-md-3">
                    <div>
                        <span class="label">Customer ID:</span>
                        <span class="value">{{ $data['customer_id'] }}</span>
                    </div>
                </div>

                <div class="custom-md-3">
                    <div class="div">
                        <span class="label">First Name:</span>
                        <span class="value">{{ $data['first_name'] }}</span>
                    </div>
                </div>

                <div class="custom-md-3">
                    <div class="div">
                        <span class="label">Last Name:</span>
                        <span class="value">{{ $data['last_name'] }}</span>
                    </div>
                </div>

                <div class="custom-md-3">
                    <div class="div">
                        <span class="label">Date of birth:</span>
                        <span class="value">{{ dateFormat($data['dob']) }}</span>
                    </div>
                </div>
            </div>
            <div class="custom-row">
                <div class="custom-md-3">
                    <div class="div">
                        <span class="label">Nationality:</span>
                        <span class="value">{{ $data['nationality_text'] }}</span>
                    </div>
                </div>

                <div class="custom-md-4">
                    <div class="div">
                        <span class="label">Country of residence:</span>
                        <span class="value">{{ $data['country_name'] }}</span>
                    </div>
                </div>

                <div class="custom-md-3">
                    <div class="div">
                        <span class="label">Place of birth:</span>
                        <span class="value">{{ $data['birth_place'] }}</span>
                    </div>
                </div>
            </div>
            <div class="custom-row">
                <div class="custom-md-3">
                    <div class="div">
                        <span class="label">Residence status:</span>
                        <span class="value">{{ $data['resident_status'] }}</span>
                    </div>
                </div>
                <div class="custom-md-8">
                    <div class="div">
                        <span class="label">Residential address:</span>
                        <span class="value font-14">{{ $data['residential_address'] }}</span>
                    </div>
                </div>
            </div>
            <div class="custom-row">
                <div class="custom-md-4">
                    <div class="div">
                        <span class="label">Mobile number:</span>
                        <span class="value">{{ $data['mobile_number'] }}</span>
                    </div>
                </div>
                <div class="custom-md-4">
                    <div class="div">
                        <span class="label">Email:</span>
                        <span class="value font-14">{{ $data['email'] }}</span>
                    </div>
                </div>
                <div class="custom-md-3">
                    <div class="div">
                        <span class="label">Customer tenure:</span>
                        <span class="value">{{ $data['customer_tenure'] }}</span>
                    </div>
                </div>
            </div>
            <div class="custom-row">
                <div class="custom-md-3">
                    <div class="div">
                        <span class="label">ID type:</span>
                        <span class="value">{{ $data['id_type'] }}</span>
                    </div>
                </div>
                <div class="custom-md-4">
                    <div class="div">
                        <span class="label">ID number:</span>
                        <span class="value">{{ $data['id_number'] }}</span>
                    </div>
                </div>
                <div class="custom-md-4">
                    <div class="div">
                        <span class="label">ID issue date:</span>
                        <span class="value">{{ dateFormat($data['id_issue_date']) }}</span>
                    </div>
                </div>
            </div>
            <div class="custom-row">
                <div class="custom-md-4">
                    <div class="div">
                        <span class="label">ID expiry date:</span>
                        <span class="value">{{ dateFormat($data['id_expiry_date']) }}</span>
                    </div>
                </div>
                <div class="custom-md-3">
                    <div class="div">
                        <span class="label">Mode of contact:</span>
                        <span class="value">{{ $data['mode_of_contact'] }}</span>
                    </div>
                </div>
                <div class="custom-md-3">
                    <div class="div">
                        <span class="label">Mode of delivery:</span>
                        <span class="value">{{ $data['mode_of_delivery'] }}</span>
                    </div>
                </div>
            </div>

            <h2 class="text-center">Source of Income</h2>

            <div class="custom-row">
                <div class="custom-md-4">
                    <span class="label">Employed:</span>
                    <input type="radio" class="value" @checked($data['income_source'] == 'employed') />
                    <br/><br/>
                    <span class="label">Business owner / Partner:</span>
                    <input type="radio" class="value" @checked($data['income_source'] == 'business') />
                </div>
                <div class="custom-md-6">
                    @if($data['income_source'] == 'employed')
                        <div>
                            <span class="label">Employer/Company name:</span>
                            <span class="value">{{ $data['company_name'] }}</span>
                        </div>
                        <br/>
                        <div>
                            <span class="label">Professional Job title:</span>
                            <span class="value">{{ $data['professional_title'] }}</span>
                        </div>
                        <br/>
                        <div>
                            <span class="label">Employment sector:</span>
                            <span class="value">{{ $data['employment_sector'] }}</span>
                        </div>
                    @elseif($data['income_source'] == 'business')
                        <div>
                            <span class="label">Company name:</span>
                            <span class="value">{{ $data['company_name'] }}</span>
                        </div>
                        <br/>
                        <div>
                            <span class="label">Trade License#:</span>
                            <span class="value">{{ $data['trade_license'] }}</span>
                        </div>
                        <br/>
                        <div>
                            <span class="label">Position in company:</span>
                            <span class="value">{{ $data['company_position'] }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <h2 class="text-center">For compliance use only</h2>

            <div class="custom-row">
                <div class="custom-md-6">
                    <strong class="label">Is the customer a PEP?</strong>
                </div>
                <div class="custom-md-4">
                    <input type="radio" @checked($data['pep'] == 'yes')/>
                    <span>YES</span>
                    <input type="radio" class="ml-2" @checked($data['pep'] == 'no')/>
                    <span>No</span>
                </div>
            </div>

            <div class="custom-row">
                <div class="custom-md-6">
                    <strong class="label"
                    >Is the customer or business subjected to financial sanctions / or
                        connected with prescribed terrorist organizations?</strong
                    >
                </div>
                <div class="custom-md-4">
                    <input type="radio" @checked($data['financial_sanctions'] == 'yes')/>
                    <span>YES</span>
                    <input type="radio" class="ml-2" @checked($data['financial_sanctions'] == 'no')/>
                    <span>No</span>
                </div>
            </div>

            <div class="custom-row">
                <div class="custom-md-6">
                    <strong class="label">Does the customer have dual nationality?</strong>
                </div>
                <div class="custom-md-4">
                    <input type="radio" @checked($data['dual_nationality'] == 'yes')/>
                    <span>YES</span>
                    <input type="radio" class="ml-2" @checked($data['dual_nationality'] == 'no')/>
                    <span>No</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
