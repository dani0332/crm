<?php
return [
    'car_quote_request' => [
        'model' => CarQuote::class
    ],
    'car_quote_insurance_coverage' => [
        'model' => CarQuoteInsuranceCoverage::class
    ],
    'car_model' => [
        'model' => CarModel::class,
    ],
    'car_quote_ftc_documents' => [
        'model' => FtcDocument::class
    ],
    'car_quote_documents' => [
        'model' => CarQuoteDocuments::class
    ],
    'insurance_companies' =>[
        'model' => InsuranceCompany::class
    ],
    'car_quote_insurance_plan' => [
        'model' => CarQuoteInsurancePlan::class
    ],
    'vehicle_type' => [
        'model' => VehicleType::class
    ],
    'ftc_history' => [
        'model' => FTCHistory::class
    ],
    'car_quote_payment' => [
        'model' => CarQuotePayment::class
    ],
    'ftc_quote_status_history' => [
        'model' => FtcQuoteStatusHistory::class
    ],
    'car_quote_payment_history' => [
        'model' => CarQuotePaymentHistory::class
    ],
    'car_quote_vehicle_detail' => [
        'model' => VehicleDetailCarQuote::class
    ],
    'car_quote_kyc_status' => [
        'model' => CarQuoteKYCStatus::class
    ],
    'kyc_statuses' => [
        'model' => KycStatus::class
    ],
    'quote_status' => [
        'model' => QuoteStatus::class
    ],
    'users' => [
        'model' => User::class
    ],
    'car_quote_kyc' => [
        'model' => CarQuoteKyc::class
    ],
    'kyc_logs' => [
        'model' => KycLog::class
    ],
    'car_quote_aml_status' => [
        'model' => CarQuoteAMLStatus::class
    ],
    'car_quote_aml_status_lookup' => [
        'model' => CarQuoteAMLStatusLookup::class
    ]
];
