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
            max-width: 80%;
            width: 100%;
            margin: 0px auto;
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
            border-color: #bfbfbf;
            border-spacing: 0;
        }

        tbody>tr>td {
            border: 1px solid #bfbfbf;
            padding: 4px 8px;
        }

        thead>tr>th {
            border: 1px solid #bfbfbf;
        }

        .text-left {
            text-align: left;
        }

        .text-xs {
            font-size: 13px;
        }

        .text-sm {
            font-size: 14px;
        }

        .text-xl {
            font-size: 16px;
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

        .mb-2 {
            margin-bottom: 8px;
        }

        .italic {
            font-style: italic;
        }

        header {
            padding-top: 32px;
        }

        footer {
            position: fixed;
            bottom: 0px;
            left: 0px;
            right: 0px;
            padding: 0px;
            margin: 80px 0 0 0;
            background-color: #1d83bc;
            color: black;
            text-align: center;
            position: fixed;
            bottom: 0px;
            height: 145px;
            z-index: 1500;
        }

        .text-center {
            text-align: center;
        }

        .title {
            margin-bottom: 16px;
        }

        .title>h3 {
            text-align: center;
            text-decoration: underline;
            margin: 16px 0;
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

        <h4 class="mb-2">Details</h4>

        <table class="table-fixed">
            <colgroup>
                <col width="275px" />
                <col />
            </colgroup>
            <tbody>
                <tr>
                    <td>
                        <p>Policy Number</p>
                        <p class="text-red">(Master Policy issued by Salama)</p>
                    </td>
                    <td>{{$viewData['master_policy_number']}}</td>

                </tr>
                <tr>
                    <td>
                        <p>Certificate Number</p>
                        <p class="text-red">(Issued by Insurancemarket.ae)
                        </p>
                    </td>
                    <td>{{$viewData['certificate_number']}}</td>

                </tr>
                <tr>
                    <td>Full Name of Covered Member</td>
                    <td>{{$viewData['name']}}</td>
                </tr>
                <tr>
                    <td>Date of Birth</td>
                    <td>{{$viewData['dob']}}</td>

                </tr>
                <tr>
                    <td>Emirates ID</td>
                    <td></td>

                </tr>
                <tr>
                    <td>Date of Enrollment</td>
                    <td>{{$viewData['date_of_enrollment']}}</td>

                </tr>
                <tr>
                    <td>Covered Benefits</td>
                    <td></td>

                </tr>
                <tr>
                    <td>Age Limit</td>
                    <td></td>

                </tr>
                <tr>
                    <td>Type of Vehicle</td>
                    <td>{{$viewData['type']}}</td>

                </tr>
                <tr>
                    <td>Annual Contribution Amount*</td>
                    <td>{{$viewData['premium']}}</td>

                </tr>
            </tbody>
        </table>

        <div>
            <p class="my-4 italic">* Kindly note that no refunds apply for mid-term cancellations</p>
            <p class="my-4"><b>Signed on behalf of SALAMA Islamic Arab Insurance Co. (P.S.C.)</b></p>
            <div class="my-8">Signature Authorized</div>
            <div class="italic">Subject to the terms and conditions and exclusions as laid out in the Master Plan No. _________________________ issued by SALAMA which is considered renewed every year unless advised otherwise.</div>
        </div>
    </main>
</body>

</html>