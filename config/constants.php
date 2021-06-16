<?php 

return [
    'social_driver' => 'google',
    'azure_storage_url' => env('AZURE_STORAGE_URL' ,''),
    'emailL_sys' => env('EMAIL_SYS' , 'DEVELOPMENT'),
    'central_api_endpoint' => env('CENTRAL_API_ENDPOINT' , ''),
    'central_api_token'=>env('CENTRAL_API_TOKEN' , ''),
    'datetime_format'=>env('DATETIME_FORMAT' , '')
];