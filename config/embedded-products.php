<?php

return [

    /**
     * Configuration for certificates.
     */
    'certificates' => [
        'MDX' => [
            'view_file' => 'pdf.ep_certificate',
            'v2' => [
                'from' => env('EP_MDX_V2_FROM', '2024-05-08 15:00:00'),
                'view_file' => 'pdf.ep_certificate_v2',
            ],
        ],
    ],
];
