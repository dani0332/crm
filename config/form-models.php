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
    'car_quote_vehicle_detail' => [
        'model' => VehicleDetailCarQuote::class
    ],
    'car_quote_kyc_status' => [
        'model' => CarQuoteKYCStatus::class
    ],
    'kyc_statuses' => [
        'model' => KycStatus::class
    ],
    'users' => [
        'model' => User::class
    ]
];
