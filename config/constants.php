<?php

return [
    'social_driver' => 'google',
    'azure_storage_url' => env('AZURE_STORAGE_URL' ,''),
    'emailL_sys' => env('EMAIL_SYS' , 'DEVELOPMENT'),
    'central_api_endpoint' => env('CENTRAL_API_ENDPOINT' , ''),
    'central_api_token'=>env('CENTRAL_API_TOKEN' , ''),
    'datetime_format'=>env('DATETIME_FORMAT' , ''),
    'CLAIMS_UPLOAD_MIME_TYPES'=>env('CLAIMS_UPLOAD_MIME_TYPES' , ''),
    'valuation_api_route' => env('VALUATION_API_URL'),
    'AML_SEARCH_API_ENDPOINT' => env('AML_SEARCH_API_ENDPOINT'),
    'AML_MATCHED_EMAIL_RECIPIENTS' => env('AML_MATCHED_EMAIL_RECIPIENTS'),
    'ERROR_EMAIL_RECIPIENTS' => env('ERROR_EMAIL_RECIPIENTS'),
    'ENABLE_TRANSAPP_WE' => env('ENABLE_TRANSAPP_WE')
];
