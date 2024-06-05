<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ManagementReportGroupByEnum extends Enum
{
    const ADVISOR = 'Advisor';
    const POLICY_ISSUER = 'Policy Issuer';
    const CUSTOMER_GROUP = 'Customer Group';
    const INSURER = 'Insurer';
    const CAR = 'Car';
    const HOME = 'Home';
    const TRAVEL = 'Travel';
    const PET = 'Pet';
    const LIFE = 'Life';
    const HEALTH = 'Health';
    const BUSINESS = 'Business';
}
