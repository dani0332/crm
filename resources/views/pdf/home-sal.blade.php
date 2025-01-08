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

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .header p {
            margin: 5px 0 0;
            font-size: 14px;
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

        /* Advisor Info Card */
        .advisor-card {
            display: flex;
            align-items: center;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            margin-top: 40px;
        }

        .advisor-photo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-right: 20px;
            object-fit: cover;
            float: left;
            /* Float the image to the left */
        }

        .advisor-details {
            flex: 1;
            float: left;
            /* Float the details to the left */
            width: calc(100% - 100px);
            /* Adjust width to account for image and margin */
        }

        .advisor-details h3 {
            margin: 0 0 10px 0;
            font-size: 18px;
        }

        .advisor-details p {
            margin: 5px 0;
            font-size: 14px;
            color: #555;
        }

        /* Clear floats */
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>InsuranceMARKET.ae</h1>
        <p>Single Article Limit (SAL) Declaration</p>
    </div>

    <div class="content">
        <p>Thank you for choosing Alfred!</p>
        <p>We've successfully received your declaration form for valuable item(s) with your chosen home insurance plan.
        </p>
        <p>Each item that's valued at AED 40,000 or more has been recorded for comprehensive coverage.</p>
        <p>Below, you'll find a summary of the declared items:</p>

        <!-- SAL Items Table -->
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

        <!-- Advisor Info Card -->
        <div class="advisor-card clearfix">
            <!-- Advisor Profile Photo -->
            <img src="{{ $data['profile_photo_path'] != null ? $data['profile_photo_path'] : public_path('image/alfred-theme.png') }}"
                alt="Advisor Profile Photo" class="advisor-photo">

            <!-- Advisor Details -->
            <div class="advisor-details">
                <h3>{{ $data['advisor_name'] }}</h3>
                <p>Email: {{ $data['advisor_email'] }}</p>
                <p>Mobile: {{ $data['advisor_mobile_no'] }}</p>
                <p>Landline: {{ $data['advisor_landline_no'] }}</p>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Thank you for using our services. For any inquiries, please contact us at support@insurancemarket.ae</p>
    </div>
</body>

</html>
