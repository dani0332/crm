<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insurance Comparison Table</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&family=Raleway:wght@300;400;500;600;700&display=swap');
        
        * {
            font-family: 'Prompt', sans-serif !important;
        }

        .raleway-font {
            font-family: 'Raleway', sans-serif !important;
        }
        
        html {
            line-height: 1;
            margin: 0;
            padding: 0;
        }
        
        body {
            line-height: 1;
            margin: 0;
            padding: 0;
            font-size: 12px;
            font-weight: 400;
            color: #333333;
            position: relative;
            min-height: 100vh;
        }
        
        div, span, table, tbody, tfoot, thead, tr, th, td, blockquote, dl, dd, h1, h2, h3, h4, h5, h6, hr, figure, p, pre {
            margin: 0;
            font-size: 12px;
            font-weight: 400;
        }

        .separator {
            color: #D3D3D3; /* Match border color */
            font-weight: normal; /* Ensure it's not bold */
            padding: 0 5px; /* Adjust spacing */
        }
        
        .page {
            width: 100%;
            background-color: white;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            height: 100vh;
            position: relative;
            page-break-after: always;
            overflow: hidden;
        }
        
        .page:last-of-type {
            page-break-after: auto;
        }
        
        .font-700 {
            font-weight: 700;
        }

        /* Header section styling */
        .header {
            width: 100%;
            margin: 0 auto 20px;
            padding: 8px 10px;
            border-top: 2px solid #D3D3D3;
            border-bottom: 2px solid #D3D3D3;
            background-color: white;
            box-sizing: border-box;
            display: block;
            clear: both;
        }
        
        .header-content {
            width: 100%;
            display: table;
        }
        
        .header-details {
            display: table-cell;
            vertical-align: middle;
            width: 70%;
        }
        
        .header-item {
            display: inline-block;
            padding-right: 6px;
            margin-right: 6px;
            border:0px;   
            border-top: 0px;
            border-bottom: 0px;
            border-left: 0px;
        }
        
        .header-item:last-child {
            border-right: none;
        }
        
        .header-title {
            font-family: 'Raleway', sans-serif !important;
            font-weight: 600;
            font-size: 12px;
            color: #5B5F60;
            margin: 0;
        }
        
        .header-text-highlight {
            font-weight: 500 !important;
            font-size: 8px !important;
            font-family: 'Prompt', sans-serif !important;
        }

        .header-text {
            font-family: 'Prompt', sans-serif;
            font-weight: 400;
            font-size: 8px;
            color: #5B5F60;
            margin: 0;
        }
        
        .quote-ref {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            width: 30%;
            padding-right: 20px;
        }
        
        .quote-ref p {
            font-family: 'Prompt', sans-serif;
            font-weight: 400;
            font-size: 12px;
            margin: 0;
        }
        
        /* Table styling */
        table {
            width: 90%;
            margin: 0 auto;
            border-collapse: collapse;
            border-radius: 10px;
            table-layout: fixed;
            text-indent: 0;
            border-color: #bfbfbf;
            color: #333333;
            border-spacing: 0;
        }
        
        table tr td, table tr th {
            border: 1px solid #bfbfbf;
            font-size: 12px;
            padding: 2px;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
            white-space: normal;
        }
        
        .table-headers th {
            font-family: 'Raleway', sans-serif !important;
            font-weight: 700;
            font-size: 14px;
            color: #5B5F60;
            background-color: rgba(0, 162, 255, 0.32);
        }
        
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        
        table.nested {
            border: none;
            width: 100%;
            padding: 0;
            margin: 0;
            table-layout: fixed;
        }
        
        table.nested td {
            border: none;
            padding: 0;
            margin: 0px;
        }
        
        .full-width-border {
            border-top: 1px solid #bfbfbf;
            width: 100%;
            margin: 0;
            padding: 0;
            display: block;
        }
        
        .detail-table {
            margin-top: 10px;
            width: 90%;
            table-layout: fixed;
        }
        
        .detail-table tr td:first-child {
            background-color: rgba(0, 162, 255, 0.32);
            color: #5B5F60;
            font-family: 'Raleway', sans-serif !important;
            font-weight: 700;
            width: 25% !important;
            text-align: left;
            padding-left: 6px;
        }

        /* Section headers */
        .section-header {
            background-color: #1D83BC !important;
            color: white !important;
            font-family: 'Raleway', sans-serif !important;
            font-weight: 700;
            font-size: 14px;
            padding: 5px;
            width: 100% !important;
            text-align: left;
        }
        
        /* Buy button styling */
        .buy-button {
            display: inline-block;
            background-color: #FE7333;
            color: white;
            border-radius: 5px;
            font-weight: 500;
            font-size: 14px;
            padding: 5px;
            text-align: center;
            line-height: 0.7;
            height:40px;
            line-height: 40px;
        }
        
        .buy-button p {
            font-size: 14px;
            margin: 0;
            text-align: center;
            font-weight: 500;
        }
        
        /* View quotes button */
        .view-quotes {
            background-color: #1D83BC;
            color: white;
            border-radius: 5px;
            font-weight: 500;
            text-align: center;
            font-size: 16px;
            height:30px;
            line-height: 30px;
            padding: 3px 70px;
            font-family: 'Prompt', sans-serif;
            display: inline-block;
            text-decoration: none;
            position: relative;
        }
        
        .view-quotes a {
            margin: 0;
            text-decoration: none;
            color: white;
            font-weight: 600;
        }
        
        .disclaimer {
            font-size: 14px;
            line-height: 1;
            text-align: left;
            font-family: 'Prompt', sans-serif;
            padding: 10px 20px;
        }

        /* footer section */
         .footer{
            background: #1D83BC !important;
            color: white;
            padding: 10px;
            width: 100%;
            height: 160px;
            box-sizing: border-box;
            position: fixed;
            bottom: 0;
            left: 0;
            text-align: center;
        }
        
        .footer-header {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
        }
        
        .footer-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            color: #ffffff;
        }
        
        .footer-td {
            padding: 10px;
            vertical-align: top;
            border: none;
        }
        
        .footer-box {
            border-radius: 24px;
            border: 2px solid #CF9E3C;
            padding: 6px 10px;
            text-align: left;
        }
        
        .hero-image {
            width: 100%;
            height: 1150px;
        }
        
        .hero-image img {
            width: 100%;
            height: 1150px;
        }
        
        .content-page {
            /* page-break-before: always; */
            page-break-after: auto;
        }

        .banner-page {
            width: 100%;
            height: 100%;
            display: block;
            position: relative;
        }
        
        .banner-image {
            width: 100%;
            height: 90vh; /* Reduced height to ensure it fits on one page */
            margin: 0 auto;
            text-align: center;
            display: block;
        }
        
        .full-page-image {
            width: 100%;
            z-index: 999;
            height: 88%;
        }
    </style>
</head>
<body>

        <div class="page">
            <div class="hero-image" style="height: auto; max-height: 1160px;">
                <img src="{{ public_path('images/car-comparision-4-image.png') }}" alt="Car Banner 4" style="height: auto; max-height: 1160px;">
            </div>
        </div>
</body>
</html>