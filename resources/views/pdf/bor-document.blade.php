<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Broker on Record (BOR) Document</title>
    <style>
        @page {
            margin: 1cm;
            font-family: 'Arial', sans-serif;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 24px;
            margin: 0;
            font-weight: bold;
        }
        
        .header h2 {
            color: #7f8c8d;
            font-size: 16px;
            margin: 5px 0 0 0;
            font-weight: normal;
        }
        
        .document-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        
        .document-info div {
            flex: 1;
        }
        
        .section {
            margin-bottom: 25px;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            padding: 20px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 15px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 5px;
        }
        
        .field-row {
            display: flex;
            margin-bottom: 10px;
        }
        
        .field-label {
            font-weight: bold;
            width: 180px;
            color: #555;
        }
        
        .field-value {
            flex: 1;
            color: #333;
        }
        
        .signature-section {
            margin-top: 40px;
            border: 2px solid #2c3e50;
            padding: 20px;
            background-color: #f8f9fa;
        }
        
        .signature-box {
            border: 1px solid #ccc;
            height: 100px;
            width: 300px;
            margin: 15px 0;
            background-color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .signature-image {
            max-width: 280px;
            max-height: 80px;
            object-fit: contain;
        }
        
        .signature-placeholder {
            color: #aaa;
            font-style: italic;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #777;
            padding: 10px;
            border-top: 1px solid #eee;
        }
        
        .important-notice {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .important-notice h3 {
            color: #856404;
            margin: 0 0 10px 0;
            font-size: 14px;
        }
        
        .lob-badge {
            background-color: #007bff;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        .table th,
        .table td {
            border: 1px solid #dee2e6;
            padding: 8px 12px;
            text-align: left;
        }
        
        .table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>BROKER ON RECORD (BOR) DOCUMENT</h1>
        <h2>{{ strtoupper($lob) }} Insurance Appointment Letter</h2>
    </div>

    <div class="document-info">
        <div>
            <strong>BOR Reference:</strong> {{ $bor_reference }}<br>
            <strong>Document Date:</strong> {{ $created_date }}<br>
            <strong>Quote Reference:</strong> {{ $quote_reference }}
        </div>
        <div style="text-align: right;">
            <span class="lob-badge">{{ $lob }}</span><br>
            <span class="status-badge status-{{ strtolower($status) }}">{{ ucfirst($status) }}</span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Customer Information</div>
        
        <div class="field-row">
            <div class="field-label">Customer Type:</div>
            <div class="field-value">{{ $customer_type }}</div>
        </div>
        
        @if($customer_type === 'Individual')
            <div class="field-row">
                <div class="field-label">Customer Name:</div>
                <div class="field-value">{{ $customer_name }}</div>
            </div>
        @else
            <div class="field-row">
                <div class="field-label">Company Name:</div>
                <div class="field-value">{{ $company_name }}</div>
            </div>
        @endif
        
        <div class="field-row">
            <div class="field-label">Email Address:</div>
            <div class="field-value">{{ $customer_email }}</div>
        </div>
        
        <div class="field-row">
            <div class="field-label">Phone Number:</div>
            <div class="field-value">{{ $customer_phone }}</div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Insurance Details</div>
        
        <div class="field-row">
            <div class="field-label">Insurance Company:</div>
            <div class="field-value">{{ $insurance_company }}</div>
        </div>
        
        <div class="field-row">
            <div class="field-label">Line of Business:</div>
            <div class="field-value">{{ $lob }} Insurance</div>
        </div>
        
        @if($policy_number)
            <div class="field-row">
                <div class="field-label">Policy Number:</div>
                <div class="field-value">{{ $policy_number }}</div>
            </div>
        @endif
        
        @if($policy_expiry)
            <div class="field-row">
                <div class="field-label">Policy Expiry:</div>
                <div class="field-value">{{ $policy_expiry }}</div>
            </div>
        @endif
        
        @if($chassis_number && in_array(strtolower($lob), ['car', 'bike']))
            <div class="field-row">
                <div class="field-label">Chassis Number:</div>
                <div class="field-value">{{ $chassis_number }}</div>
            </div>
        @endif
    </div>

    <div class="important-notice">
        <h3>Important Notice</h3>
        <p>
            This document serves as formal authorization for <strong>Alfred App Pte Ltd</strong> to act as your 
            insurance broker for the {{ $lob }} insurance policy mentioned above. By signing this document, 
            you acknowledge and agree to the following:
        </p>
        <ul>
            <li>Alfred App Pte Ltd is authorized to act on your behalf in all matters related to this insurance policy</li>
            <li>All communications regarding this policy may be directed through Alfred App Pte Ltd</li>
            <li>This authorization remains valid until formally revoked in writing</li>
            <li>You have read and understood the terms and conditions of this appointment</li>
        </ul>
    </div>

    @if($include_signature && $signature_path)
        <div class="signature-section">
            <div class="section-title">Customer Signature</div>
            
            <div class="field-row">
                <div class="field-label">Signed by:</div>
                <div class="field-value">{{ $signature_name }}</div>
            </div>
            
            <div class="field-row">
                <div class="field-label">Date & Time:</div>
                <div class="field-value">{{ $date_signed }}</div>
            </div>
            
            <div class="field-row">
                <div class="field-label">Digital Signature:</div>
                <div class="field-value">
                    <div class="signature-box">
                        <img src="{{ public_path(str_replace('/storage/', 'storage/', $signature_path)) }}" 
                             alt="Customer Signature" 
                             class="signature-image">
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="signature-section">
            <div class="section-title">Customer Signature</div>
            <p><strong>Status:</strong> Awaiting digital signature</p>
            
            <div class="signature-box">
                <span class="signature-placeholder">Digital signature will appear here once signed</span>
            </div>
            
            <p style="margin-top: 15px; font-size: 11px; color: #666;">
                <strong>Note:</strong> This document requires digital signature to be legally binding. 
                The customer will receive a secure link to complete the signing process.
            </p>
        </div>
    @endif

    <div class="section">
        <div class="section-title">Broker Information</div>
        
        <table class="table">
            <tr>
                <th>Broker Name</th>
                <td>Alfred App Pte Ltd</td>
            </tr>
            <tr>
                <th>License Number</th>
                <td>FA100126</td>
            </tr>
            <tr>
                <th>Address</th>
                <td>1 Raffles Place, #19-61 One Raffles Place, Singapore 048616</td>
            </tr>
            <tr>
                <th>Contact</th>
                <td>Email: support@alfred.app | Phone: +65 6909 7202</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>This document was generated electronically on {{ $current_date }} at {{ $current_time }}.</p>
        <p>Alfred App Pte Ltd - Licensed Insurance Broker | UEN: 201815053W</p>
    </div>
</body>
</html> 