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

    'ecb' => [
        'prod' => [
            'recipient_emails' => [
                'to' => [],
                'cc' => ['nidhi.kaushal@myalfred.com', 'tasawar.hussain@myalfred.com'],
                'bcc' => ['newleadpool@insurancemarket.ae'],
            ]
        ],
        'non_prod' => [
            'recipient_emails' => [
                'to' => [],
                'cc' => ['nidhi.kaushal@myalfred.com', 'tasawar.hussain@myalfred.com'],
                'bcc' => ['newleadpool@insurancemarket.ae'],
            ]
        ],
        'policy_context' => [
            'policy_claim_limit' => 'One claim per policy term.',
            'policy_coverage' => 'If you have an accident, you pay part of the repair bill (this is called "excess"), usually between AED 350 to AED 1,400. This benefit gives you back up to AED 1,200.',
            'policy_duration' => 'Your coverage lasts for 13 months or until the expiry of your motor insurance policy, whichever comes first.',
        ],
    ],
];
