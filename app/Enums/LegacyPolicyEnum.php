<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class LegacyPolicyEnum extends Enum
{
    const INSLY_PRODUCT_MAPPING = [
        'bike insurance' => 'Bike Insurance',
        'business interruption insurance' => 'Business Insurance',
        'casco' => 'Motor Insurance',
        'contractors all risks' => 'Business Insurance',
        'critical illness' => 'Life Insurance',
        'cyber liability' => 'Business Insurance',
        'directors and officers liability insurance' => 'Business Insurance',
        'engineering and plant insurance' => 'Business Insurance',
        'fidelity guarantee' => 'Business Insurance',
        'group life' => 'Business Insurance',
        'group medical insurance' => 'Business Insurance',
        'holiday homes' => 'Business Insurance',
        'home insurance' => 'Home Insurance',
        'inbound travel insurance' => 'Travel Insurance',
        'individual life insurance' => 'Life Insurance',
        'individual or family medical' => 'Health Insurance',
        'livestock insurance' => 'Business Insurance',
        'machinery breakdown insurance' => 'Business Insurance',
        'marine cargo (individual shipment) insurance' => 'Business Insurance',
        'marine hull insurance' => 'Business Insurance',
        'medical malpractice insurance' => 'Business Insurance',
        'money insurance' => 'Business Insurance',
        'motor fleet' => 'Business Insurance',
        'motor insurance - comprehensive' => 'Motor Insurance',
        'motor insurance - tpl' => 'Motor Insurance',
        'open cover - marine cargo insurance' => 'Business Insurance',
        'outbound travel insurance' => 'Travel Insurance',
        'pedal cycle insurance' => 'Cycle Insurance',
        'personal accident' => 'Home Insurance',
        'pet insurance' => 'Pet Insurance',
        'professional indemnity insurance' => 'Business Insurance',
        'property insurance' => 'Business Insurance',
        'public liability insurance' => 'Business Insurance',
        'road transit (international)' => 'Business Insurance',
        'road transit (uae only)' => 'Business Insurance',
        'sme packaged insurance' => 'Business Insurance',
        'specialist general insurance' => '',
        'trade credit insurance' => 'Business Insurance',
        'workmens compensation insurance' => 'Business Insurance',
        'yacht insurance' => 'Yacht Insurance',
    ];
    const INSLY_COVERAGE_MAPPING = [
        'motor fleet' => 'Business Insurance',
        'casco' => 'Motor Insurance',
        'group medical insurance' => 'Group Medical Insurance',
    ];
}
