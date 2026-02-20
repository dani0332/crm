<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Ready</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 15px;
            text-align: center;
            border-bottom: 3px solid #327bb2;
        }
        .content {
            padding: 20px;
            background-color: #ffffff;
        }
        .download-btn {
            display: inline-block;
            background-color: #327bb2;
            color: white !important;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            margin: 20px 0;
        }
        .download-btn:hover {
            background-color: #2868a0;
        }
        .info-box {
            background-color: #f5f9fc;
            border-left: 4px solid #327bb2;
            padding: 10px 15px;
            margin: 20px 0;
        }
        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 10px 15px;
            margin: 20px 0;
        }
        .footer {
            font-size: 12px;
            text-align: center;
            color: #6c757d;
            padding: 15px;
            background-color: #f8f9fa;
        }
        ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        li {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Your Export is Ready</h2>
        </div>
        <div class="content">
            <p>Dear <strong>{{ $recipientName }}</strong>,</p>
            <p>Your <strong>{{ $exportTitle }}</strong> export has been processed successfully.</p>

            <div class="info-box">
                <p><strong>Export Details:</strong></p>
                <ul>
                    <li>Records: {{ number_format($recordCount) }}</li>
                    <li>Generated: {{ $currentDate }}</li>
                </ul>
            </div>

            <center>
                <a href="{{ $downloadUrl }}" class="download-btn">Download CSV File</a>
            </center>

            <div class="warning">
                <strong>Important:</strong> This download link will expire in {{ $expiryHours }} hours. Please download the file before it expires.
            </div>

            <p>If you have any questions or need further assistance, please contact your system administrator.</p>

            <p>Thank you,<br>
            {{ config('constants.MAIL_FROM_NAME', 'The System') }}</p>
        </div>
        <div class="footer">
            <p>Note: This is an automated email. Please do not reply directly to this message.</p>
            <p>&copy; {{ date('Y') }} {{ config('constants.MAIL_FROM_NAME', 'The System') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
