<?php

return [
    'car_quote_request' => [
        'model' => CarQuote::class,
    ],
    'car_quote_insurance_coverage' => [
        'model' => CarQuoteInsuranceCoverage::class,
    ],
    'car_model' => [
        'model' => CarModel::class,
    ],
    'car_make' => [
        'model' => CarMake::class,
    ],
    'car_quote_documents' => [
        'model' => CarQuoteDocuments::class,
    ],
    'insurance_provider' => [
        'model' => InsuranceProvider::class,
    ],
    'car_plan' => [
        'model' => CarPlan::class,
    ],
    'vehicle_type' => [
        'model' => VehicleType::class,
    ],
    'car_quote_payment' => [
        'model' => CarQuotePayment::class,
    ],
    'car_quote_policy' => [
        'model' => CarQuotePolicy::class,
    ],
    'car_quote_assign_oe_to_advisor' => [
        'model' => CarQuoteAdvisorToOE::class,
    ],
    'car_quote_payment_history' => [
        'model' => CarQuotePaymentHistory::class,
    ],
    'car_quote_vehicle_detail' => [
        'model' => VehicleDetailCarQuote::class,
    ],
    'car_quote_kyc_status' => [
        'model' => CarQuoteKYCStatus::class,
    ],
    'kyc_statuses' => [
        'model' => KycStatus::class,
    ],
    'teams' => [
        'model' => Teams::class,
    ],
    'quote_status' => [
        'model' => QuoteStatus::class,
    ],
    'users' => [
        'model' => User::class,
    ],
    'car_quote_kyc' => [
        'model' => CarQuoteKyc::class,
    ],
    'kyc_logs' => [
        'model' => KycLog::class,
    ],
    'car_quote_aml_status' => [
        'model' => CarQuoteAMLStatus::class,
    ],
    'car_quote_aml_status_lookup' => [
        'model' => CarQuoteAMLStatusLookup::class,
    ],
    'type_of_insurances' => [
        'model' => CarTypeInsurance::class,
    ],
    'nationality' => [
        'model' => Nationality::class,
    ],
];
