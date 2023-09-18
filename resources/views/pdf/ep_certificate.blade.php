<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Plans Comparison PDF</title>

    <style>
        @page {
            margin: 0;
            padding: 0;
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
            padding-top: 32px;
            max-width: 90%;
            width: 100%;
            margin: 0px auto;
        }

        main {
            max-width: 80%;
            width: 100%;
            margin: 0px auto;
            font-size: 17px;
        }

        footer {
            position: fixed;
            bottom: 0px;
            left: 0px;
            right: 0px;
            z-index: 10;
            max-width: 90%;
            width: 100%;
            margin: 0px auto;
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
    </style>
</head>

<body>

    <header>
        <div>
            <img src="{{public_path('images/ep/logos/salama.png')}}" width="300" height="172" alt="Salama Logo">
        </div>
    </header>

    <main>
        <div class="title">
            <h3>CERTIFICATE</h3>
            <p>This is to certify that the below member is an eligible customer under the Personal Accident and Medical Expenses cover for holders of an Individual Motor Policy sold via Insurancemarket.ae.</p>
        </div>

        <h4 style="margin-bottom: 5px;">Details</h4>

        <table class="bordered">
            <tbody>
                <tr>
                    <td width="150">
                        <p>Policy Number</p>
                        <p class="text-red">(Master Policy issued by Salama)</p>
                    </td>
                    <td  colspan="2">{{$viewData['master_policy_number'] ?? ""}}</td>
                </tr>
                <tr>
                    <td>
                        <p>Certificate Number</p>
                        <p class="text-red">(Issued by Insurancemarket.ae)
                        </p>
                    </td>
                    <td  colspan="2">{{$viewData['certificate_number'] ?? ""}}</td>

                </tr>
                <tr>
                    <td>Full Name of Covered Member</td>
                    <td  colspan="2">{{$viewData['name'] ?? ""}}</td>
                </tr>
                <tr>
                    <td>Date of Birth</td>
                    <td  colspan="2">{{$viewData['dob'] ?? ""}}</td>

                </tr>
                <tr>
                    <td>Emirates ID</td>
                    <td colspan="2"></td>

                </tr>
                <tr>
                    <td>Date of Enrollment</td>
                    <td colspan="2">{{$viewData['date_of_enrollment'] ?? ""}}</td>

                </tr>
                <tr>
                    <td>Covered Benefits</td>
                    <td>
                        <ul>
                            <li>Accidental Death Benefit</li>
                            <li>Medical Expense (as an RTA extension)</li>
                        </ul>
                    </td>
                    <td>
                        <ul>
                            <li>AED 10,000</li>
                            <li>AED 50,000</li>
                        </ul>
                    </td>

                </tr>
                <tr>
                    <td>Age Limit</td>
                    <td  colspan="2">
                        <ul>
                            <li>Minimum Entry Age:  18 years</li>
                            <li>Maximum Entry Age:  59 years</li>
                            <li>Maximum Expiry Age:  60 years</li>
                        </ul>
                    </td>

                </tr>
                <tr>
                    <td>Type of Vehicle</td>
                    <td  colspan="2" >{{$viewData['type'] ?? ""}}</td>

                </tr>
                <tr>
                    <td>Annual Contribution Amount*</td>
                    <td  colspan="2">{{$viewData['premium'] ?? ""}}</td>

                </tr>
            </tbody>
        </table>

        <p class="my-4 italic">* Kindly note that no refunds apply for mid-term cancellations</p>

        <div style="margin: 2rem 0 3rem;">
            <strong>Signed on behalf of SALAMA Islamic Arab Insurance Co. (P.S.C.)</strong>
        </div>

        <div class="mb-3">
            <p>__________________________</p>
        Signature Authorized
        </div>
        <div class="italic mb-2">Subject to the terms and conditions and exclusions as laid out in the Master Plan No. _________________________ issued by SALAMA which is considered renewed every year unless advised otherwise.</div>
    </main>

    <footer>
        <table>
            <tr>
                <td>
                    <div style="font-size: 14px;">
                        SALAMA - Islamic Arab Insurance Co. (PJC) <br />
                        Family Takaful Division <br />
                        P.O. Box 10214, Dubai, UAE
                    </div>
                </td>
                <td>
                    <div style="text-align: center; font-size: 16px; vertical-align: bottom;">SALAMA - Internal</div>
                </td>
                <td>
                    <div style="font-size: 14px; text-align: right;">
                        Call Center No.: 800-SALAMA (725262) <br />
                        Customer Service: cs.ft@salamalife.ae <br />
                        Claims Department: claims@salamalife.ae</div>
                </td>
            </tr>
        </table>

        <div style="margin-top: 2rem;">
            <span style="background-color: #246b71; width: 500px; height: 20px; display: inline-block;"></span>
            <span style="background-color: #fdcc00; width: 100px; height: 20px;  display: inline-block; margin-left: -5px;"></span>
        </div>
    </footer>
</body>

</html>