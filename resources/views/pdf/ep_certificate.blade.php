<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Plans Comparison PDF</title>

    <style>
        @page {
            margin:0;
            padding: 0;
        }
        html {
            line-height: 1.5;
            margin:0;
            padding: 0;
        }
        body {
            line-height: 1;
            font-family: "DejaVu Sans", sans-serif;

        }
        header{
            position: fixed;
            top: 0;
            left: 0;
            height: 200px;
            width: 100%;
            display: block;
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
        table.tbl-dec {
            border: none;
        }
        table.tbl-dec tr td, table.tbl-dec tr td a {border: none;}
        table {
            min-width: 1220px;
            width: 1220px;
            text-indent: 0;
            border-color: #bfbfbf;
            max-width: 1220px;
            margin: 7px 12px auto;
            border-spacing: 0;
        }
        tbody{
            margin-bottom: 130px;
        }
        .header {
            background: #1d83bc;
            color: #ffffff;
            font-size: 16px;
            text-align: center;
            padding: 8px 10px;
            width: 100%;
            height: 57px;
            max-height: 57px;
        }
        .header .logo {
            float: left;
            background-color: white;
            border-radius: 5px;
            padding: 5px 10px 5px 0px;
            height: 50px;
            max-height: 50px;
        }
        .header .logo img {
            max-height: 50px;
            height: 50px;
        }
        .header h3 {
            float: right;
            text-align: right;
            padding-right: 18px;
        }
        tbody > tr > td {
            border: 1px solid #bfbfbf;
        }
        thead > tr > th {
            border: 1px solid #bfbfbf;
        }
        td > p, th > p {
            padding: 4px;
            font-size: 14px;
            text-align: center;
            font-weight: normal;
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
        .blue-box {
            background: #ddfdfc;
        }
        .bg-light-blue {
            border: 1px solid #bfbfbf;
            background: #EFF6FF;
            padding: 8px;
            color: #252525;
        }
        .text-black{color: #000000;}
        .provider {
            border: 1px solid #bfbfbf;
            font-size: 15px;
            line-height: 28px;
            font-weight: 400;
            color: #4ea4a8;
            vertical-align: middle;
            max-height: 50px;
            height: 50px;
        }
        .spacer {
            padding: 3px;
        }
        .alfred { text-align: right;padding-right: 0;vertical-align: bottom;border-left: none;border-top: none;}
        .quote-info {
            text-align: right;
            vertical-align: bottom;
            margin-top: -1px;
            background: #EFF6FF;
            font-size: 14px;
            text-align: left;
            padding: 8px;
            max-width: 100%;
            font-weight: normal;
        }
        .info h5 {
            background: #1d83bc;
            color: #ffffff;
            padding: 3px;
            font-weight: normal;
            margin: 0 0 10px 0;
        }
        .info p {
            font-size: 12px;
        }
        .btn-all-quotes {
            background-color: #1d83bc;
            color: #ffffff;
            padding: 8px 25px;
            margin-top: 50px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 15px;
            font-weight: bold;
            border-radius: 5px;
            margin-bottom: 0px;
        }
        .btn-buy
        {
            background-color: #FE7333;
            color: #ffffff;
            padding: 12px 15px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            font-weight: bold;
            border-radius: 5px;
        }
        .btn-buy:hover{
            background-color: #d7fbd0;
        }
        .text-heading {
            color: #ffffff;
            background-color: #1d83bc;
        }
        .heading-desc {
            font-size: 12px;
        }
        .provider-logo {
            width: 100px;
        }
        .no-border {border: none;}
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

        table.tbl-footer {
            padding: 18px 12px;
            margin: 0;
            width: 100%;
            border: none;
        }
        th.provider-name {
            padding: 0;
            margin: 0;
        }
        table.tbl-footer tr td, table.tbl-footer tr td a {
            color: #ffffff;
            border: none;
            font-size: 14px;
        }
        .text-left {text-align: left;}
        .text-right {text-align: right;}
        .full-page-image {
            width: 100%;
            z-index: 999;
        }
        .text-center {text-align: center;}
        .text-white { color: #ffffff}
        .text-underline{text-decoration:underline }

    </style>
</head>

<body>
{{--
    <header>
        <div>
            <img src="{{public_path('images/header.png')}}">
        </div>
    </header> --}}

    {{-- PDF Page Footer --}}
    <footer>

    </footer>

    {{-- PDF Page Inner Content --}}
    <main>
        <table class="tbl-dec">
            <tbody>
                <tr>
                    <td>
                        <span class="text-center"><b>CERTIFICATE</b></span>
                        <p class="text-center">This is to certify that the below member is an eligible customer under the Personal Accident and Medical Expenses cover for holders of an Individual Motor Policy sold via Insurancemarket.ae.</p>

                    </td>
                </tr>
            </tbody>
        </table>
      <table class="table-fixed text-center tbl-plans" style="position: relative;top: 100px;margin-bottom: 130px;">

            <tr>
              <td>Policy Number
                (Master Policy issued by Salama)</td>
              <td>Maria Anders</td>

            </tr>
            <tr>
              <td>Certificate Number
                (Issued by Insurancemarket.ae)</td>
              <td>Francisco Chang</td>

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
              <td>Yoshi Tannamuri</td>

            </tr>
            <tr>
              <td>Date of Enrollment</td>
              <td>Giovanni Rovelli</td>

            </tr>
            <tr>
              <td>Covered Benefits</td>
              <td>Giovanni Rovelli</td>

            </tr>
            <tr>
              <td>Age Limit</td>
              <td>Giovanni Rovelli</td>

            </tr>
            <tr>
              <td>Type of Vehicle</td>
              <td>Giovanni Rovelli</td>

            </tr>
            <tr>
              <td>Annual Contribution Amount*</td>
              <td>Giovanni Rovelli</td>

            </tr>
          </table>

          <table class="tbl-dec">
            <tbody>
                <tr>
                    <td>
                          <p class="text-left text-xs">* Kindly note that no refunds apply for mid-term cancellations</p>
                     </td>

                </tr>
            </tbody>
        </table>
    </table>
    </main>
</body>

</html>