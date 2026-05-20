<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Horizon Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Horizon will be accessible from. If this
    | setting is null, Horizon will reside under the same domain as the
    | application. Otherwise, this value will serve as the subdomain.
    |
    */

    'domain' => env('HORIZON_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Horizon will be accessible from. Feel free
    | to change this path to anything you like. Note that the URI will not
    | affect the paths of its internal API that aren't exposed to users.
    |
    */

    'path' => env('HORIZON_PATH', 'queue-dashboard'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    |
    | This is the name of the Redis connection where Horizon will store the
    | meta information required for it to function. It includes the list
    | of supervisors, failed jobs, job metrics, and other information.
    |
    */

    'use' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix will be used when storing all Horizon data in Redis. You
    | may modify the prefix when you are running multiple installations
    | of Horizon on the same server so that they don't have problems.
    |
    */

    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_horizon:'
    ),

    /*
    |--------------------------------------------------------------------------
    | Horizon Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will get attached onto each Horizon route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Time Thresholds
    |--------------------------------------------------------------------------
    |
    | This option allows you to configure when the LongWaitDetected event
    | will be fired. Every connection / queue combination may have its
    | own, unique threshold (in seconds) before this event is fired.
    |
    */

    'waits' => [
        'redis:default' => 60,
        'redis:ocr_dedicated' => 180,
        'redis_policy_issuance:policy-issuance-automation' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Trimming Times
    |--------------------------------------------------------------------------
    |
    | Here you can configure for how long (in minutes) you desire Horizon to
    | persist the recent and failed jobs. Typically, recent jobs are kept
    | for one hour while all failed jobs are stored for an entire week.
    |
    */

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics
    |--------------------------------------------------------------------------
    |
    | Here you can configure how many snapshots should be kept to display in
    | the metrics graph. This will get used in combination with Horizon's
    | `horizon:snapshot` schedule to define how long to retain metrics.
    |
    */

    'metrics' => [
        'trim_snapshots' => [
            'job' => 100,
            'queue' => 100,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fast Termination
    |--------------------------------------------------------------------------
    |
    | When this option is enabled, Horizon's "terminate" command will not
    | wait on all of the workers to terminate unless the --wait option
    | is provided. Fast termination can shorten deployment delay by
    | allowing a new instance of Horizon to start while the last
    | instance will continue to terminate each of its workers.
    |
    */

    'fast_termination' => false,

    /*
    |--------------------------------------------------------------------------
    | Memory Limit (MB)
    |--------------------------------------------------------------------------
    |
    | This value describes the maximum amount of memory the Horizon master
    | supervisor may consume before it is terminated and restarted. For
    | configuring these limits on your workers, see the next section.
    |
    */

    'memory_limit' => 256,

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may define the queue worker settings used by your application
    | in all environments. These supervisors and settings handle all your
    | queued jobs and will be provisioned by Horizon during deployment.
    |
    */

    'environments' => [
        'production' => [
            'supervisor-prod' => [
                'connection' => 'redis',
                'queue' => ['default', 'renewals', 'insly', 'advisor-payment-notification'],
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 3,
                'tries' => 3,
                'timeout' => 60,
            ],
            'supervisor-policy-issuance-prod' => [
                'connection' => 'redis_policy_issuance',
                'queue' => ['policy-issuance-automation'],
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 3,
                'tries' => 1,
                'timeout' => 200,
            ],
            'supervisor-prod-shared' => [
                'connection' => 'redis',
                'queue' => ['shared', 'lead_ocr_data_comparison', 'private-client'],
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 3,
            ],
            'supervisor-prod-ocr-dedicated' => [
                'connection' => 'redis',
                'queue' => ['ocr_dedicated'],
                'balance' => 'simple',
                'processes' => 2,
                'tries' => 3,
                'timeout' => 120,
                'memory' => 512,
            ],
        ],
        'uat' => [
            'supervisor-uat' => [
                'connection' => 'redis',
                'queue' => ['default', 'renewals', 'insly', 'advisor-payment-notification'],
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 3,
                'tries' => 3,
                'timeout' => 60,
            ],
            'supervisor-policy-issuance-uat' => [
                'connection' => 'redis_policy_issuance',
                'queue' => ['policy-issuance-automation'],
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 3,
                'tries' => 1,
                'timeout' => 200,
            ],
            'supervisor-uat-shared' => [
                'connection' => 'redis',
                'queue' => ['shared', 'lead_ocr_data_comparison', 'private-client'],
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 3,
                'tries' => 3,
                'timeout' => 60,
            ],
            'supervisor-uat-ocr-dedicated' => [
                'connection' => 'redis',
                'queue' => ['ocr_dedicated'],
                'balance' => 'simple',
                'processes' => 2,
                'tries' => 3,
                'timeout' => 120,
                'memory' => 512,
            ],
        ],
        'staging' => [
            'supervisor-stg' => [
                'connection' => 'redis',
                'queue' => ['default', 'renewals', 'insly', 'advisor-payment-notification', 'shared', 'lead_ocr_data_comparison', 'private-client', 'ocr_dedicated'],
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 2,
                'tries' => 3,
                'timeout' => 60,
            ],
            'supervisor-policy-issuance-stg' => [
                'connection' => 'redis_policy_issuance',
                'queue' => ['policy-issuance-automation'],
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 2,
                'tries' => 1,
                'timeout' => 200,
            ],
        ],
        'dev01' => [
            'supervisor-dev' => [
                'connection' => 'redis',
                'queue' => ['default', 'renewals', 'insly'],
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 3,
                'tries' => 3,
                'timeout' => 60,
            ],
            'supervisor-policy-issuance-dev' => [
                'connection' => 'redis_policy_issuance',
                'queue' => ['policy-issuance-automation'],
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 3,
                'tries' => 1,
                'timeout' => 200,
            ],
            'supervisor-dev-shared' => [
                'connection' => 'redis',
                'queue' => ['shared', 'lead_ocr_data_comparison', 'private-client'],
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 3,
            ],
            'supervisor-dev-ocr-dedicated' => [
                'connection' => 'redis',
                'queue' => ['ocr_dedicated'],
                'balance' => 'simple',
                'processes' => 2,
                'tries' => 3,
                'timeout' => 120,
                'memory' => 512,
            ],
        ],
        'test' => [
            'supervisor-test' => [
                'connection' => 'redis',
                'queue' => ['default', 'renewals', 'insly', 'advisor-payment-notification'],
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 3,
                'tries' => 3,
                'timeout' => 60,
            ],
            'supervisor-policy-issuance-test' => [
                'connection' => 'redis_policy_issuance',
                'queue' => ['policy-issuance-automation'],
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 3,
                'tries' => 1,
                'timeout' => 200,
            ],
            'supervisor-test-shared' => [
                'connection' => 'redis',
                'queue' => ['shared', 'lead_ocr_data_comparison', 'private-client'],
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 3,
            ],
            'supervisor-test-ocr-dedicated' => [
                'connection' => 'redis',
                'queue' => ['ocr_dedicated'],
                'balance' => 'simple',
                'processes' => 2,
                'tries' => 3,
                'timeout' => 120,
                'memory' => 512,
            ],
        ],
        'local' => [
            'supervisor-dev' => [
                'connection' => 'redis',
                'queue' => ['default', 'renewals', 'insly', 'advisor-payment-notification'],
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 3,
                'tries' => 3,
                'timeout' => 5000,  // Increased from 60 to 5000 seconds (83 minutes) for heavy jobs
                'memory' => 3072,   // Set memory limit to 3GB for job workers
            ],
            'supervisor-local-shared' => [
                'connection' => 'redis',
                'queue' => ['shared', 'lead_ocr_data_comparison', 'private-client'],
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 3,
                'memory' => 512,
            ],
            'supervisor-policy-issuance-local' => [
                'connection' => 'redis_policy_issuance',
                'queue' => ['policy-issuance-automation'],
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 3,
                'tries' => 1,
                'timeout' => 5000,
                'memory' => 3072,
            ],
            'supervisor-local-ocr-dedicated' => [
                'connection' => 'redis',
                'queue' => ['ocr_dedicated'],
                'balance' => 'simple',
                'processes' => 2,
                'tries' => 3,
                'timeout' => 120,
                'memory' => 512,
            ],
        ],
    ],
];
