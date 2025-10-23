<?php

return [

    /**
     * Configuration for certificates.
     */
    'certificates' => [
        'MDX' => [
            'email_template_alias' => 'embedded-products-payment-auth',
            'view_file' => 'pdf.ep_certificate',
            'view_file_v2' => 'pdf.ep_certificate_v2',
            'view_file_v3' => 'pdf.ep_certificate_v3',
        ],
        'RDX' => [
            'email_template_alias' => 'bike-embedded-products-payment-auth',
            'view_file' => 'pdf.ep_certificate_v3',
        ],
    ],
];
