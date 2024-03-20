<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Plans Comparison PDF</title>

    <style>
        @page {
            margin: 200px 0;
            padding: 150px 0 0 0;
        }

        .page-break {
            page-break-after: always;
        }

        html {
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        body {
            line-height: 1;
            font-family: "DejaVu Sans", sans-serif;
            position: relative;
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

        table {
            width: 100%;
            border-spacing: 0;
        }

        table.bordered {
            border-color: #444444;
            border-spacing: 0;
        }

        table.bordered tbody>tr>td {
            border: 1px solid #444444;
            padding: 4px 8px;
        }

        table.bordered thead>tr>th {
            border: 1px solid #444444;
        }

        .text-left {
            text-align: left;
        }

        .text-red {
            color: red;
        }

        .my-4 {
            margin: 16px 0;
        }

        .my-8 {
            margin: 56px 0;
        }

        .mb-1 {
            margin-bottom: 1rem;
        }

        .mb-2 {
            margin-bottom: 2rem;
        }

        .mb-3 {
            margin-bottom: 3rem;
        }

        .italic {
            font-style: italic;
        }

        header {
            padding: 50px;
            max-width: 90%;
            width: 100%;
            left: 3%;
            top: 0
            margin: 0px auto;
            position: fixed;
        }

        main {
            max-width: 75%;
            width: 100%;
            margin: 0 auto;
            font-size: 18px;
        }

        main,
        .mt-p {
            margin-top: 240px;
        }

        footer {
            bottom: 0px;
            left: 0px;
            right: 0px;
            z-index: 10;
            max-width: 90%;
            width: 100%;
            margin: 0px auto;
            position: fixed;
        }

        .text-center {
            text-align: center;
        }

        .title {
            margin-bottom: 2rem;
        }

        .title>h3 {
            text-align: center;
            text-decoration: underline;
            margin: 1.5rem 0;
            font-size: 22px;
        }

        .table-fixed {
            width: 100%;
        }

        table thead th {
            color: white;
            background: #00af00;
            text-align: left;
            font-weight: normal;
            padding: 5px 8px;
        }

        ol, ul {
            margin: 0 20px 0 35px;
            text-align: justify;
        }
    </style>
</head>

<body>

    <header>
        <div>
            <img src="{{public_path('images/ep/logos/salama.png')}}" width="300" height="172" alt="Salama Logo">
        </div>
    </header>

    <footer>
        <table>
            <tr>
                <td>
                    <div style="font-size: 16px; vertical-align: bottom;">SALAMA - Confidential</div>
                </td>
            </tr>
        </table>

        <div style="margin-top: 0.3rem;">
            <span style="background-color: #246b71; width: 500px; height: 20px; display: inline-block;"></span>
            <span style="background-color: #fdcc00; width: 100px; height: 20px;  display: inline-block; margin-left: -5px;"></span>
        </div>
    </footer>

    <main>
        <table class="bordered">
            <thead>
                <tr>
                    <th colspan="2">
                        <p><b>Plan Holders Details</b></p>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td width="30%">
                        <p><b>Full Name</b></p>
                    </td>
                    <td >{{ $viewData['name'] }}</td>
                </tr>
                <tr>
                    <td>
                        <p><b>Date of Birth/Age</b></p>
                    </td>
                    <td>{{ $viewData['dob'] }}</td>
                </tr>
                <tr>
                    <td><b>Emirates ID No</b></td>
                    <td>{{ $viewData['emirates_id'] }}</td>
                </tr>
            </tbody>
        </table>
        <br />

        <table class="bordered">
            <thead>
                <tr>
                    <th colspan="3">
                        <p><b>Plan Holders Details</b></p>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td width="15%">
                        <p><b>S. No</b></p>
                    </td>
                    <td width="60%">
                        <p><b>Benefits</b></p>
                    </td>
                    <td width="25%">
                        <p><b>Aggregate Limit</b></p>
                    </td>
                </tr>
                <tr>
                    <td colspan="3">
                        <p><b>SECTION 1 - Injury</b></p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p>1</p>
                    </td>
                    <td>
                        <p>Accidental Death Benefit (due to accident)</p>
                    </td>
                    <td>
                        <p>AED 10,000/-</p>
                    </td>
                </tr>
                <tr>
                    <td colspan="3">
                        <p><b>SECTION 2 – Medical Expenses & Hospitalization</b></p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p>1</p>
                    </td>
                    <td>
                        <p>Accidental Medical Expenses ‐ Expenses incurred for emergency admission and at a hospital following a covered accident</p>
                    </td>
                    <td>
                        <p>AED 50,000/-</p>
                    </td>
                </tr>
            </tbody>
        </table>
        <br />

        <table class="bordered">
            <tbody>
                <tr>
                    <td width="30%">
                        <p><b>Plan Type</b></p>
                    </td>
                    <td>
                        <p>{{ $viewData['plan_type'] }}</p>
                    </td>
                </tr>
                <tr>
                    <td width="30%">
                        <p><b>Plan No</b></p>
                    </td>
                    <td>
                        <p>{{ $viewData['certificate_number'] }}</p>
                    </td>
                </tr>
                <tr>
                    <td width="30%">
                        <p><b>Plan Currency</b></p>
                    </td>
                    <td>
                        <p>{{ $viewData['plan_currency'] }}</p>
                    </td>
                </tr>
                <tr>
                    <td width="30%">
                        <p><b>Contribution Amount</b></p>
                    </td>
                    <td>
                        <p>{{ $viewData['contribution_amount'] }}</p>
                    </td>
                </tr>
                <tr>
                    <td width="30%">
                        <p><b>Plan Term</b></p>
                    </td>
                    <td>
                        <p>{{ $viewData['plan_term'] }}</p>
                    </td>
                </tr>
                <tr>
                    <td width="30%">
                        <p><b>Plan Commencement Date</b></p>
                    </td>
                    <td>
                        <p>{{ $viewData['date_of_enrollment'] }}</p>
                    </td>
                </tr>
                <tr>
                    <td width="30%">
                        <p><b>Plan Beneficiary</b></p>
                    </td>
                    <td>
                        <p>{{ $viewData['plan_beneficiary'] }}</p>
                    </td>
                </tr>
            </tbody>
        </table>
        <br />

        <table class="bordered page-break">
            <tbody>
                <tr>
                    <td width="30%" valign="top">
                        <p><b>Exclusions</b></p>
                    </td>
                    <td>
                        <ol>
                            <li>
                                Insured Persons engaging in food delivery or other delivery services on motorbikes are specifically excluded from this policy regardless of their visa type (be they direct employees or on outsourced contracts). Insured persons are however covered for any accidents that may fall outside of their login hours into the delivery company’s applications, subject to other terms and conditions for claiming benefits under this policy.
                            </li>
                            <li>
                                Physiotherapy is covered only for fracture of bone and not covered for soft tissue injuries.
                            </li>
                            <li>
                                This policy shall not cover injuries sustained due to Accidents arising because of:
                                <ol type="a">
                                    <li>
                                        use of vehicles for racing (speed, endurance, drag‐racing or other) professional or amateur, dune‐bashing, driving in the desert, quad‐biking, driving outside roads built‐up specifically for driving such vehicles, testing speed, stunt‐ driving, rallies, experimenting with loading capacity or while towing a trailer (unless the vehicle used for the purpose was fitted and licensed for towing;
                                    </li>
                                </ol>
                            </li>
                        </ol>
                    </td>
                </tr>
            </tbody>
        </table>

        <table class="bordered mt-p">
            <tbody>
                <tr>
                    <td width="30%" valign="top"></td>
                    <td>
                        <ol start="3">
                            <li style="list-style: none;">
                                <ol type="a">
                                    <li style="list-style: none;"></li>
                                    <li>
                                        use of bicycles, e‐bikes, e‐scooters, or any vehicle that does not bear a mandatory number plate issued by the Roads and Traffics Authority;
                                    </li>
                                    <li>
                                        the driver’s non‐compliance with the road safety guidelines and traffic rules issued by the Roads and Traffics Authority such as but not limited to exceeding speed limits, driving in the opposite direction of traffic, not wearing seat belts, helmets and other equipment mandated for safety by the local authorities and injuries sustained while parking in a non‐parking area;
                                    </li>
                                    <li>
                                        driving without a valid driving license;
                                    </li>
                                    <li>
                                        driving while under the influence of alcohol or under the influence of drugs that are not prescribed by a physician;
                                    </li>
                                    <li>
                                        any civil liability concerning the indirect damages befalling a third party;
                                    </li>
                                    <li>
                                        driving a vehicle outside the territorial limits of the United Arab Emirates.
                                    </li>
                                </ol>
                            </li>
                            <li>
                                Excludes Nuclear, Chemical, Biological and Mass Destruction Risks.
                            </li>
                        </ol>
                    </td>
                </tr>
            </tbody>
        </table>
        <br />

        <table class="bordered">
            <thead>
                <tr>
                    <th>
                        <p><b>Declaration</b></p>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <ol>
                            <li>
                                I acknowledge that SALAMA- Islamic Arab Insurance Company will manage my contribution under Wakala Principles as defined by the Operator and in accordance with Plan Terms & Conditions.
                            </li>
                            <li>
                                I also understand that SALAMA - Islamic Arab Insurance Company has the right to cancel my application for insurance if the first contribution is not received by the Company within the first 3 days or if any of the requirements asked by the Company is not provided by me within the time frame specified by the Company.
                            </li>
                            <li>
                                I Authorize any person, physician, hospital, clinic, institution, insurance company or any other organizations, having any records, application, or knowledge of my health to provide the operator any and all information needed about me with reference to my health, medical history, hospitalization, advise, diagnosis, treatment, disease or ailment.
                            </li>
                            <li>
                                A photocopy of this authorization shall be suitable evidence of this authority and legally original.
                            </li>
                            <li>
                                I also understand that there will be no refund of Plan Contribution after the date of issuance.
                            </li>
                            <li>
                                I further acknowledge and confirm that I have received and read the Terms and Conditions of the Plan.
                            </li>
                        </ol>
                    </td>
                </tr>
            </tbody>
        </table>
        <br />

        <div>
            <p>
                <b>Signed on behalf of SALAMA Islamic Arab Insurance Co. (P.S.C.)</b>
            </p>
            <p style="margin-top: 5px;">
                <p> <b>Date:</b> {{ $viewData['date_of_enrollment'] }} </p>
            </p>
        </div>
    </main>
</body>

</html>
