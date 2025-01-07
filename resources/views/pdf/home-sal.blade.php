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
            margin-bottom: 20px;
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

        <table class="table">
            <thead>
                <tr>
                    <th>REF-ID</th>
                    <th>Line of Business</th>
                    <th>Department</th>
                    <th>Requested Date</th>
                    <th>Lead Cost</th>
                </tr>
            </thead>
            {{-- <tbody>
                @foreach ($list as $row)
                    <tr>
                        <td>{{ $quoteType->refId($row->ref_id) }}</td>
                        <td>{{ $row->quoteType?->code }}</td>
                        <td>{{ $row->department }}</td>
                        <td>{{ \Carbon\Carbon::parse($row->requested_date)->format('Y-m-d H:i:s') }}</td>
                        <td>{{ $row->cost }}</td>
                    </tr>
                @endforeach
            </tbody> --}}
        </table>
    </div>

    <div class="footer">
        <p>Thank you for using our services. For any inquiries, please contact us at support@insurancemarket.ae</p>
    </div>
</body>

</html>
