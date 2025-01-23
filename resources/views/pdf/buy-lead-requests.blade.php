<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th, .table td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .table th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h2>My Lead Requests</h2>

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
        <tbody>
            @foreach($list as $row)
                <tr>
                    <td>{{ $quoteType->refId($row->ref_id) }}</td>
                    <td>{{ $row->quoteType?->code }}</td>
                    <td>{{ $row->department }}</td>
                    <td>{{ \Carbon\Carbon::parse($row->created_at)->format('Y-m-d H:i:s') }}</td>
                    <td>{{ $row->cost }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
