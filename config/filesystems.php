<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
     */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
     */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'temp' => [
            'driver' => 'local',
            'root' => storage_path('/temp'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_REGION'),
            'bucket' => env('AWS_INSLY_DOCUMENT_BUCKET_NAME'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            // 'visibility' => 'public',
            'bucket_endpoint' => true, // add this
        ],
        'insly_documents' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            // 'visibility' => 'public',
            'bucket_endpoint' => true, // add this
        ],
        // RYU Container for Azure
        'azureForRyu' => [
            'driver' => 'azure-storage-blob',
            'connection_string' => 'DefaultEndpointsProtocol=https;AccountName='.env('AZURE_RYU_STORAGE_NAME').';AccountKey='.env('AZURE_RYU_STORAGE_KEY').';EndpointSuffix=core.windows.net',
            'container' => env('AZURE_RYU_STORAGE_CONTAINER'),
            'url' => env('AZURE_RYU_STORAGE_URL'),
            'prefix' => null,
        ],
        'azureIM' => [
            'driver' => 'azure-storage-blob',
            'connection_string' => 'DefaultEndpointsProtocol=https;AccountName='.env('AZURE_IM_STORAGE_NAME').';AccountKey='.env('AZURE_IM_STORAGE_KEY').';EndpointSuffix=core.windows.net',
            'container' => env('AZURE_IM_STORAGE_CONTAINER'),
            'url' => env('AZURE_IM_STORAGE_URL'),
            'prefix' => null,
        ],
        'instantchat' => [
            'driver' => 'local',
            'root' => storage_path('InstantChat'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
     */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
