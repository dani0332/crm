<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class AMLDecisionStatusEnum extends Enum
{
    const PASS = 'Pass';
    const ESCALATED = 'Escalated';
    const REJECTED = 'Rejected';
    const SENT_FOR_REVIEW = 'SentForReview';
    const UNKNOWN = 'Unknown';
    const FALSE_POSITIVE = 'FalsePositive';
    const TRUE_MATCH = 'TrueMatch';
    const TRUE_MATCH_ACCEPT_RISK = 'TrueMatchAcceptRisk';
    const TRUE_MATCH_REJECT_RISK = 'TrueMatchRejectRisk';
    const RYU = 'RYU';

    private static $amlStatusEvaluation = [
        'Pass' => 'Pass',
        'FalsePositive' => 'Pass',
        'TrueMatchAcceptRisk' => 'Pass',
        'Escalated' => 'Escalated',
        'SentForReview' => 'Sent For Review',
        'Rejected' => 'Rejected',
        'TrueMatchRejectRisk' => 'Rejected',
    ];

    public static function amlStatusEvaluation()
    {
        return self::$amlStatusEvaluation;
    }
}
