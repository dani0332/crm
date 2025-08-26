<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Letter of Appointment/Authorisation/EBOR</title>
    <style>
        @page {
            margin: 1cm;
            font-family: 'Arial', sans-serif;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        .header {
            margin-bottom: 30px;
        }
        
        .document-header {
            font-size: 14px;
            line-height: 1.8;
            margin-bottom: 20px;
        }

        .document-header-entity {
            font-size: 14px;
            line-height: 1.8;
            margin-bottom: 20px;
            width: 100%;
        }
        
        .header-row {
            width: 100%;
            margin-bottom: 10px;
        }
        
        .header-left {
            float: left;
            width: 60%;
        }
        
        .header-right {
            float: right;
            width: 35%;
            text-align: right;
        }
        
        .clearfix::after {
            content: "";
            clear: both;
        }
        
        .document-header strong {
            font-weight: bold;
        }
        
        .document-title {
            font-size: 16px;
            font-weight: bold;
            margin: 20px 0;
            text-decoration: underline;
        }
        
        .letter-content {
            text-align: justify;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .letter-content p {
            margin-bottom: 15px;
        }
        
        .signature-section {
            margin-top: 20px;
        }
        
        .signature-section p {
            margin-bottom: 10px;
        }


        .signature-wrapper {
            width: 100%;
            margin-bottom: 10px;
        }
        
        .signature-label {
            float: left;
            width: 10%;
            margin-top: 30px;
            font-weight: bold;
        }
        
        .signature-box-container {
            float: left;
            width: 75%;
            margin-left: 3%;
        }

        .stamp-box {
            display: block;
        }
        
        .signature-box {
            border: 1px solid #ccc;
            height: 80px;
            width: 300px;
            margin: 0px 0 0 4px;
            background-color: #f9f9f9;
            text-align: center;
            line-height: 80px;
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

         .stamp-box {
            display: block;
            width: 300px;
            padding: 5px;
            border-radius: 6px;
            border: 2px solid #cfabab;
            background-color: white;
        }
        
        .signature-placeholder-box {
            border: 2px dashed #CBCBCB;
            border-radius: 6px;
            height: 160px;
            width: 290px;
            background-color: white;
            text-align: center;
            position: relative;
            margin: 10px 0;
        }
        
        .signature-placeholder-text {
            color: #333333;
            font-size: 14px;
            font-weight: bold;
            height: 10px;
        }
        
        .accept-button {
            background-color: #ff6b35;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: bold;
            margin: 20px 0;
            display: inline-block;
        }
        
        .footer {
            bottom: 0;
            left: 0;
            right: 0;
            margin-top: 200px;
            text-align: center;
            font-size: 10px;
            color: #777;
            padding: 10px;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="header">
        @if($customer_type === 'Entity')
        <div class="document-header-entity">
            <div class="header-row clearfix">
                <div class="header-left">
                    <strong>To:</strong> {{ $insurance_company ?? '' }}
                </div>
                <div class="header-right">
                    <strong>Date:</strong> {{ $current_date ?? now()->format('d/m/Y') }}
                </div>
            </div>
        </div>
        @else
        <div class="document-header">
            <strong>Date:</strong> {{ $current_date ?? now()->format('d/m/Y') }}<br>
            <strong>To:</strong> {{ $insurance_company ?? '' }}<br>
            <strong>Policy Number:</strong> {{ $policy_number ?? '[IMCRM or client updated]' }}
        </div>
        @endif
        
        <div class="document-title">
            Letter of Appointment/Authorisation/EBOR
        </div>
    </div>

    @if($customer_type === 'Entity')
    <div class="letter-content">
        <p>This letter confirms the exclusive appointment of InsuranceMarket.ae (a registered trademark of AFIA Insurance Brokerage Services L.L.C, with registration number 85) as our duly authorised insurance broker, effective immediately. This appointment nullifies any previous authorisations.</p>

        <p>InsuranceMarket.ae is empowered to manage all aspects of our insurance portfolio, including arranging coverage upon our approval and obtaining information about past policies, They are authorised to disclose relevant insurance details to insurers and other necessary entities.</p>

        <p>By proceeding, I confirm my understanding that all premium payments must be made directly to the Insurance Company. I authorise my broker to retain this acknowledgment for regulatory compliance.</p>
        
        <p>
            Furthermore, they are authorised to engage in discussions and negotiations concerning potential claims.
        </p>
        
    </div>
    @else
        <div class="letter-content">
            <p>
                This letter serves as a formal appointment of InsuranceMarket.ae (a registered trademark of AFIA Insurance Brokerage Services L.L.C.) as my exclusive period.
            </p>
            
            <p>
                InsuranceMarket.ae is authorised to act on my behalf in all matters related to my insurance policy, including servicing, placement, implementation, and negotiation of terms, effective immediately.
            </p>
            
            <p>
                By proceeding, I confirm my understanding that all premium payments must be made directly to the Insurance Company. I authorise my broker to retain this acknowledgment for regulatory compliance.
            </p>
            
            <p>
                This appointment supersedes any previous authorisations and is made without obligation or liability on our part. All decisions regarding acceptance of terms will be at our discretion.
            </p>
            
            <p>
                Yours sincerely,
            </p>
        </div>
    @endif
    @if($include_signature && $signature_path)
        <div class="signature-section">
            <!-- <div class="accept-button">Signed and Accepted</div> -->
            <p><strong>Signed by:</strong> {{ $customer_type === 'Entity' ? $company_name : $customer_name }}</p>
            <div class="signature-wrapper clearfix">
                <div class="signature-label">
                    Sign here:
                </div>
                <div class="signature-box-container">
                    <div class="signature-box">
                        <img src="{{ $signature_path }}" 
                            alt="Customer Signature" 
                            class="signature-image">
                    </div>
                </div>
            </div>
            <div style="clear: both; margin-top: 15px;">
                <p><strong>Date signed:</strong> {{ $date_signed .' '. $date_signed_time ?? '' }}</p>
            </div>
        </div>
    @else
        @if($customer_type === 'Entity')
            <div class="signature-section">
                <p><strong>Company Name:</strong> {{ $company_name }}</p>
                <p><strong>Authorized Signatory:</strong> </p>
                <p><strong>Designation:</strong> </p>
                <!-- Signature placeholder box -->
                <div class="stamp-box">
                    <div class="signature-placeholder-text">Sign and stamp here</div>
                    <div class="signature-placeholder-box">
                    </div>
                </div>
                <p><strong>Date signed:</strong> _____________________</p>
            </div>
        @else
            <div class="signature-section">
                <div class="accept-button">{{ $customer_type === 'Entity' ? "Upload BOR letter": "Sign and accept" }}</div>
                
                <p><strong>Name:</strong> {{ $customer_name ?? ' ' }}</p>
                <p><strong>Date signed:</strong> _____________________</p>
            </div>
        @endif
    @endif

    <div class="footer">
        @if($include_signature && $signature_path)
        <div style="text-align: center; font-size: 10px; color: #777; line-height: 1.4;">
            This document is digitally signed by <strong>{{ $customer_type === 'Entity' ? $company_name : $customer_name }}</strong> on <strong>{{ $date_signed ?? now()->format('d/m/Y') }}</strong> at <strong>{{ $date_signed_time ?? now()->format('H:i:s') }}</strong>, no manual signature required<br>
            Document Hash: <strong>{{ $document_id ?? '[Unique Hash]' }}</strong><br>
            Accessed By: <strong>{{ $user_agent ?? request()->ip() }}</strong>
        </div>
        @endif
    </div>
</body>
</html> 