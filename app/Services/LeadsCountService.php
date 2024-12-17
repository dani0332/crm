<?php

namespace App\Services;

use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;

class LeadsCountService
{
    public static function getLeadCount()
    {
        $allowedLOBs = $totalCount = 0;
        $nameSpace = '\\App\\Models\\';
        $allowedQuoteTypes = [];
        $response = ['is_multiple_lobs_allowed' => false, 'total_count' => 0, 'quote_route' => ''];
        $userRoles = auth()->user()?->getRoleNames()->toArray() ?? [];
        $quoteTypes = [
            // PD Revert
            // QuoteTypes::HOME,
            // QuoteTypes::HEALTH,
            // QuoteTypes::YACHT,
            // QuoteTypes::PET,
            // QuoteTypes::CYCLE,
            // QuoteTypes::CORPLINE,
        ];

        $cardViewRoute = [
            QuoteTypes::HEALTH->name => route('health.cards', ['is_stale' => true]) ?? '',
            QuoteTypes::HOME->name => route('home-cardView', ['is_stale' => true]) ?? '',
            QuoteTypes::PET->name => route('pet-quotes-card', ['is_stale' => true]) ?? '',
            QuoteTypes::YACHT->name => route('yacht-quotes-card', ['is_stale' => true]) ?? '',
            QuoteTypes::CYCLE->name => route('cycle-quotes-card', ['is_stale' => true]) ?? '',
            QuoteTypes::CORPLINE->name => route('business.cards', ['is_stale' => true]) ?? '',
        ];

        foreach ($quoteTypes as $quoteType) {
            if (in_array($quoteType->name.'_ADVISOR', $userRoles) || in_array($quoteType->name.'_MANAGER', $userRoles)) {
                $allowedLOBs = ++$allowedLOBs;
                $allowedQuoteTypes[] = $quoteType;
            } elseif (in_array(RolesEnum::Admin, $userRoles)) {
                $allowedLOBs = ++$allowedLOBs;
                $allowedQuoteTypes[] = $quoteType;
            } else {
                continue;
            }
        }

        foreach ($allowedQuoteTypes as $allowedQuoteType) {
            $quoteTypeEnum = $allowedQuoteType;
            $allowedQuoteType = strtolower($allowedQuoteType->name);
            $modelType = (checkPersonalQuotes(ucfirst($allowedQuoteType))) ? $nameSpace.'PersonalQuote' :
                ((strtoupper($allowedQuoteType) == QuoteTypes::CORPLINE->name) ? $nameSpace.ucwords(QuoteTypes::BUSINESS->name).'Quote' : $nameSpace.ucwords($allowedQuoteType).'Quote');

            if (! class_exists($modelType)) {
                return false;
            }

            // Need to verify the stale_at where check, it should be fetch only 90 days old leads.
            $baseQuery = checkPersonalQuotes(ucwords($allowedQuoteType)) ?
            $modelType::whereNotNull('stale_at')->where('quote_type_id', $quoteTypeEnum->id())->where('stale_at', '>=', date(config('constants.DATE_FORMAT_ONLY'), strtotime('-90 days'))) :
            $modelType::whereNotNull('stale_at')->where('stale_at', '>=', date(config('constants.DATE_FORMAT_ONLY'), strtotime('-90 days')));

            if (auth()->user()->isAdvisor() && ! auth()->user()->isManagerOrDeputy()) {
                $baseQuery->where('advisor_id', auth()->user()->id);
            }

            $quoteCount = $baseQuery->count();

            $response['quotes_count'][$allowedQuoteType]['count'] = $quoteCount;
            $response['quotes_count'][$allowedQuoteType]['quote_route'] = $cardViewRoute[strtoupper($allowedQuoteType)];
            $totalCount = $quoteCount;

            if ($allowedLOBs > 1 || auth()->user()->isManagerOrDeputy()) {
                $response['is_multiple_lobs_allowed'] = true;
                $response['total_count'] = $totalCount;
                $response['quote_route'] = route('stale-leads-report');
            } else {
                $response['is_multiple_lobs_allowed'] = false;
                $response['total_count'] = $totalCount;
                $response['quote_route'] = $cardViewRoute[strtoupper($allowedQuoteType)];
            }
        }

        return $response;
    }
}
