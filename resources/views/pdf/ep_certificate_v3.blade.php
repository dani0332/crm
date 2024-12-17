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
            width: 95%;
            left: 5%;
            top: 75px;
            margin: 0px;
            position: fixed;
            z-index: -10;
        }

        main {
            max-width: 75%;
            width: 100%;
            margin: 0 auto;
        }

        main,
        .mt-p {
            margin-top: 340px;
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

        .text-danger {
            color: red;
        }

        * {
            font-size: 18px !important;
        }
    </style>
</head>

<body>

    <header>
        <div>
            <img src="{{public_path('images/ep/logos/bg.png')}}" width="100%" height="100%" alt="Salama Logo">
        </div>
    </header>

    <main style="position: relative; z-index: 10">
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
                    <td >
                        {{ $viewData['name'] }}
                    </td>
                </tr>
                <tr>
                    <td>
                        <p><b>Date of Birth/Age</b></p>
                    </td>
                    <td>{{ $viewData['dob'] }}</td>
                </tr>
                <tr>
                    <td><b>Passport No/Emirates ID No</b></td>
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
                        <p><b>SECTION 2 – Medical Treatment Expenses & Hospitalization</b></p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p>1</p>
                    </td>
                    <td>
                        <p>Accidental Medical Treatment Expenses ‐ Expenses incurred for emergency admission and treatment at a hospital following a covered accident</p>
                    </td>
                    <td>
                        <p>AED 50,000/-</p>
                    </td>
                </tr>
                <tr>
                    <td colspan="3">
                        <p>The Liability of the Company for all damages in respect of any one claim or series of claims arising from one occurrence shall not exceed the Limit of Indemnity specified in the certificate/ schedule</p>
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

        <table class="bordered page-break text-danger">
            <tbody>
                <tr>
                    <td width="30%" valign="top">
                        <p><b>Exclusions</b></p>
                    </td>
                    <td>
                        <ol>
                            <li>
                                Claims arising from War or warlike operations (whether war is declared or not, conventional, biological, chemical or nuclear), invasion, acts of foreign enemies, hostilities, acts of terrorism, terrorist sabotage, rebellion, mutiny, civil commotion, civil war, revolution, insurrection, military or usurped power, martial law, embargo or any act committed by any person or persons for the purpose of overthrowing a government by violent force or to influence political decision making. Terrorism (suspected or proven)
                            </li>
                        </ol>
                    </td>
                </tr>
            </tbody>
        </table>

        <table class="bordered mt-p page-break text-danger">
            <tbody>
                <tr>
                    <td width="30%" valign="top"></td>
                    <td>
                        <ol>
                            <li style="list-style: none;">
                                 shall be understood to include the consequences of hostage taking, drive-by shooting, planting of bombs and any other form of physical violence.
                            </li>
                            <li>
                                 In the event of loss, damage, cost or expense directly or indirectly caused by, contributed to by, resulting from or arising out of or in connection with biological, chemical or nuclear explosion, pollution, contamination and/or fire following thereon.
                            </li>
                            <li>
                                Aviation, gliding or any other form of aerial flight other than as a fare paying passenger of a recognized airline or charter service.
                            </li>
                            <li>
                                The misuse of drugs or alcohol.
                            </li>
                            <li>
                                Ingestion of poison or drugs, or inhalation of fumes, voluntarily, except in the case of an Accident admitted by any occupational health and safety board or failure to seek medical advice.
                            </li>
                            <li>
                                The exercise of dangerous sports including but not limited to: -
                            </li>
                            <li>
                                polo, boxing, climbing/mountaineering requiring ropes or guide or free climbing, gliding, ballooning, racing of any kind other than on foot (including but not limited to horse or motor racing), participation in speed or endurance tests or record breaking feats, any underwater activity involving breathing apparatus, such as deep sea diving, skydiving or parachuting, bungee jumping, show jumping, steeple chasing, evening or fiat racing with horse, potholing, sailing outside territorial waters.
                            </li>
                            <li>
                                Participation in any sports in a professional capacity.
                            </li>
                            <li>
                                Any breach of law by the life assured or any assault provoked by him.
                            </li>
                            <li>
                                Infection from any Human Immuno-deficiency Virus (HIV), Acquired lmmuno deficiency Syndrome (AIDS) or any AIDS related condition.
                            </li>
                            <li>
                                Mental illness or mental disease or nervous conditions.
                            </li>
                            <li>
                                Pregnancy, childbirth or abortion or any complications arising there from.
                            </li>
                            <li>
                                Injury caused by nuclear fission, nuclear fusion or radioactive contamination.
                            </li>
                            <li>
                                Insured engaging in or taking part in any naval, military or air force operation.
                            </li>
                            <li>
                                Pre-Existing Conditions
                            </li>
                            <li>
                                Insured Persons engaging in food delivery or other delivery services on motorbikes are specifically excluded from this policy regardless of their visa type (be they direct employees or on outsourced contracts).
                            </li>
                        </ol>
                    </td>
                </tr>
            </tbody>
        </table>

        <table class="bordered mt-p page-break text-danger">
            <tbody>
                <tr>
                    <td width="30%" valign="top"></td>
                    <td>
                        <ol start="16">
                            <li style="list-style: none;">
                                Insured persons are however covered for any accidents that may fall outside of their login hours into the delivery company’s applications, subject to other terms and conditions for claiming benefits under this policy.
                            </li>
                            <li>
                                Physiotherapy is covered only for fracture of bone and not covered for soft tissue injuries.
                            </li>
                            <li>
                                This policy shall not cover injuries sustained due to Accidents arising because of:
                                <ol type="a">
                                    <li>
                                        use of vehicles for racing (speed, endurance, drag‐racing or other) professional or amateur, dune‐bashing, driving in the desert, quad‐ biking, driving outside roads built‐up specifically for driving such vehicles, testing speed, stunt‐ driving, rallies, experimenting with loading capacity or while towing a trailer (unless the vehicle used for the purpose was fitted and licensed for towing;
                                    </li>
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
                            <li>
                                Sanction Exclusion Clause
                                <br>
                                SALAMA shall not be deemed to provide cover and SALAMA shall not be liable to pay any claim or pay any benefit hereunder to the extent that the provision of such cover, payment of such claim or provision of such benefit would expose SALAMA any sanction, prohibition or restriction under United Nations resolutions or the trade or economic sanctions, laws or regulations of the European Union,
                            </li>
                        </ol>
                    </td>
                </tr>
            </tbody>
        </table>

        <table class="bordered mt-p text-danger">
            <tbody>
                <tr>
                    <td width="30%" valign="top"></td>
                    <td>
                        <ol start="20">
                            <li style="list-style: none">
                                United Kingdom or United States of America, or any of its states.
                            </li>
                            <li>
                                Excluding Fines and Penalties
                            </li>
                            <li>
                                Deliberate Acts: caused by or arising from any deliberate act or omission by or on behalf of the Insured and which could reasonably have been expected by the insured having regard to the nature and circumstances of such act or omission
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
                                <br /> <br />
                            </li>
                            <li>
                                I also understand that SALAMA - Islamic Arab Insurance Company has the right to cancel my application for insurance if the first contribution is not received by the Company within the first 3 days or if any of the requirements asked by the Company is not provided by me within the time frame specified by the Company.
                                <br /> <br />
                            </li>
                            <li>
                                I Authorize any person, physician, hospital, clinic, institution, insurance company or any other organizations, having any records, application, or knowledge of my health to provide the operator any and all information needed about me with reference to my health, medical history, hospitalization, advise, diagnosis, treatment, disease or ailment.
                                <br /> <br />
                            </li>
                            <li>
                                A photocopy of this authorization shall be suitable evidence of this authority and legally original.
                                <br /> <br />
                            </li>
                            <li>
                                I also understand that there will be no refund of Plan Contribution after the date of issuance.
                                <br /> <br />
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

        <table class="bordered page-break">
            <thead>
                <tr>
                    <th>
                        <p><b>Claim Procedure</b></p>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <p>
                            Write to the Operator, call the Operator or send a Email
                        </p>
                        <br />
                        <ul>
                            <li>
                                Address: Islamic Arab Insurance Co – SALAMA, Spectrum building, A Block, Oud Metha, Dubai, UAE
                            </li>
                            <li>
                                Tel No: 800 SALAMA (800 725262)
                            </li>
                            <li>
                                Email: general.claims@salama.ae
                            </li>
                        </ul>
                        <br />
                        <p>
                            The Operator will issue a Statement of Claim Form.
                        </p>
                        <br />
                        <p>
                            The Claimant should complete the Statement of Claim Form issued by the Operator and produce such evidence to substantiate the claim and the title of the claimant as the Operator might require.
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        <br />

        <div class="mt-p" style="position: relative">
            <p>
                Terms & conditions mentioned on this certificate shall hold precedence over terms and conditions mentioned elsewhere on the policy document.
                <br> <br> <br>

                <b>For and on behalf of SALAMA – ISLAMIC ARAB INSURANCE CO PSC</b>
                <img style="position: absolute; z-index:0; left: 42%; top: 140px;" width="140" height="auto" src="{{public_path('images/ep/logos/sign_v3.png')}}" alt="signature">
            </p>
            <p style="margin-top: 5px;">
                <p> <b>Date:</b> {{ $viewData['date_of_enrollment'] }} </p>
            </p>
        </div>
    </main>
</body>

</html>
