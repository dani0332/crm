<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        /* Header Styles */
        .header {
            width: 100%;
            padding: 20px;
            background-color: #ffffff;
            border-bottom: 1px solid #ddd;
            overflow: hidden;
        }

        .header-logo {
            width: 40%;
            float: left;
            text-align: left;
        }

        .header-logo .custom-logo {
            max-width: 100%;
            height: auto;
        }

        .header-heading {
            width: 40%;
            float: right;
            text-align: right;
        }

        .header-heading p {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            color: #1c82bd;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {

            .header-logo,
            .header-heading {
                width: 100%;
                float: none;
                text-align: center;
            }

            .header-heading {
                margin-top: 10px;
            }
        }

        /* Content Styles */
        .content {
            padding: 20px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }

        .table th,
        .table td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .table th {
            background-color: #f2f2f2;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #555;
        }

        /* Advisor Card Styles */
        .advisor-card {
            position: relative;
            background-image: url('data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/home-sal-advisor-bg.png'))) }}');
            background-size: cover;
            background-position: center;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            margin-top: 40px;
            overflow: hidden;
            page-break-inside: avoid;
            /* Prevent the advisor card from breaking across pages */
        }

        .advisor-photo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-right: 20px;
            object-fit: cover;
            float: left;
            position: relative;
            z-index: 1;
        }

        .advisor-details {
            flex: 1;
            float: left;
            width: calc(100% - 100px);
            position: relative;
            z-index: 1;
        }

        .advisor-details h3 {
            margin: 0 0 10px 0;
            font-size: 18px;
            color: #000;
        }

        .advisor-details p {
            margin: 5px 0;
            font-size: 14px;
            color: #000;
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        .no-items {
            text-align: center;
            font-size: 16px;
            color: #555;
            margin: 20px 0;
        }

        /* Additional Text Styles */
        .additional-text {
            margin-top: 20px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #555;
            text-align: left;
        }

        .additional-text a {
            color: #1c82bd;
            text-decoration: none;
            font-weight: bold;
        }

        .additional-text a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <div class="header">
        <div class="header-logo">
            <img class="custom-logo"
                src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/im_logo_23k-hi.png'))) }}"
                alt="logo" />
        </div>
        <div class="header-heading">
            <p>Single Article Limit (SAL) Declaration</p>
        </div>
    </div>

    <!-- Content -->
    <div class="content">
        <p>Thank you for choosing Alfred!</p>
        <p>We've successfully received your declaration form for valuable item(s) with your chosen home insurance plan.
        </p>
        <p>Each item that's valued at AED 40,000 or more has been recorded for comprehensive coverage.</p>

        @if (!empty($data['items']))
            <p>Below, you'll find a summary of the declared items:</p>
            <table class="table">
                <thead>
                    <tr>
                        <th>Value</th>
                        <th>Description</th>
                        <th>Purchase Date</th>
                        <th>Invoice Number</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data['items'] as $item)
                        <tr>
                            <td>{{ $item->value }}</td>
                            <td>{{ $item->description }}</td>
                            <td>{{ $item->purchase_date }}</td>
                            <td>{{ $item->invoice_number }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Add the text here -->
            <p class="additional-text">
                Thank you for choosing <a href="https://ecom.alfred.ae/" target="_blank">InsuranceMarket.ae</a>.
                We look forward to serving you.
            </p>
            <p class="additional-text">
                For any queries or support, reach me directly via WhatsApp or Email.
            </p>
            <p class="additional-text">
                I'm here to ensure your insurance process is seamless and satisfying.
            </p>
        @else
            <p class="no-items">No items were declared.</p>
        @endif

        <!-- Advisor Card -->
        <div class="advisor-card clearfix">
            <img src="{{ $data['profile_photo_path'] != null ? $data['profile_photo_path'] : asset('image/alfred-theme.png') }}"
                alt="Advisor Profile Photo" class="advisor-photo">
            <div class="advisor-details">
                <h3>{{ $data['advisor_name'] }}</h3>
                <p>Email: {{ $data['advisor_email'] }}</p>
                <p>Mobile: {{ $data['advisor_mobile_no'] }}</p>
                <p>Landline: {{ $data['advisor_landline_no'] }}</p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Thank you for using our services. For any inquiries, please contact us at support@insurancemarket.ae</p>
    </div>
</body>

</html>
