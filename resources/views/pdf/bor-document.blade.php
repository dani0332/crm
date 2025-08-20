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
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            line-height: 1.8;
            margin-bottom: 20px;
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
            display: flex;
            flex-direction: row;
            align-items: center;
        }
        
        .signature-box {
            border: 1px solid #ccc;
            height: 80px;
            width: 300px;
            margin: 0px 0 0 4px;
            background-color: #f9f9f9;
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
    </style>
</head>
<body>
    <div class="header">
        @if($customer_type === 'Entity')
        <div class="document-header-entity">
            <p>
                <strong>To:</strong> {{ $insurance_company ?? '' }}<br>
            </p>
            <p>
                <strong>Date:</strong> {{ $current_date ?? now()->format('d/m/Y') }}
            </p>
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

    @if($include_signature && $signature_path)
        <div class="signature-section">
            <!-- <div class="accept-button">Signed and Accepted</div> -->
            <p><strong>Signed by:</strong> {{ $customer_type === 'Entity' ? $company_name : $customer_name }}</p>
            <div class="signature-wrapper">
                <p><strong>Signed: </strong>
                    <div class="signature-box">
                        <img src="{{ $signature_path }}" 
                            alt="Customer Signature" 
                            class="signature-image">
                    </div>
                </p>
            </div>
            <p><strong>Date signed:</strong> {{ $date_signed ?? '' }}</p>
        </div>
    @else
        <div class="signature-section">
            <div class="accept-button">Sign and accept</div>
            
            <p><strong>Name:</strong> {{ $customer_name ?? ' ' }}</p>
            <p><strong>Date signed:</strong> _____________________</p>
        </div>
    @endif

    <div class="footer">
        
    </div>
</body>
</html> 