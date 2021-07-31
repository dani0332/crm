<?php 

return [
    'social_driver' => 'google',
    'azure_storage_url' => env('AZURE_STORAGE_URL' ,''),
    'emailL_sys' => env('EMAIL_SYS' , 'DEVELOPMENT'),
    'central_api_endpoint' => env('CENTRAL_API_ENDPOINT' , ''),
    'central_api_token'=>env('CENTRAL_API_TOKEN' , ''),
    'datetime_format'=>env('DATETIME_FORMAT' , ''),
    'CLAIMS_UPLOAD_MIME_TYPES'=>env('CLAIMS_UPLOAD_MIME_TYPES' , ''),
    'valuation_api_route' => env('VALUATION_API_URL')
];