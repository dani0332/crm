<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Notification</title>
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
        .footer {
            font-size: 12px;
            text-align: center;
            color: #6c757d;
            padding: 15px;
            background-color: #f8f9fa;
        }
        .highlight {
            font-weight: bold;
            color: #327bb2;
        }
        .info-box {
            background-color: #f5f9fc;
            border-left: 4px solid #327bb2;
            padding: 10px 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Export Completed Successfully</h2>
        </div>

        <div class="content">
            <p>Dear <span class="highlight">{{ $recipientName }}</span>,</p>

            <p>Your requested export of <span class="highlight">{{ $quoteTypeName }}</span> data has been processed successfully on {{ $currentDate }}.</p>

            <div class="info-box">
                <p><strong>Export Details:</strong></p>
                <ul>
                    <li>Content: {{ $quoteTypeName }} data</li>
                    <li>Records: {{ $recordCount }}</li>
                    <li>File size: Approximately {{ $fileSize }} KB</li>
                    <li>Export date: {{ $currentDate }}</li>
                </ul>
            </div>

            <p>The CSV file is attached to this email for your reference. This file includes all data you requested according to your specified filters and parameters.</p>

            <p>If you have any questions or need further assistance, please contact your system administrator.</p>

            <p>Thank you,<br>
            {{ $systemName }}</p>
        </div>

        <div class="footer">
            <p>Note: This is an automated email. Please do not reply directly to this message.</p>
            <p>&copy; {{ date('Y') }} {{ $systemName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
