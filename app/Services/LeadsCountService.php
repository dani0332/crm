<?php

namespace App\Services;

use App\Enums\QuoteTypes;

class LeadsCountService 
{
    public static function getLeadCount()
    {

        $allowedLOBs = $totalCount = 0;
        $nameSpace = '\\App\\Models\\';
        $allowedQuoteTypes = [];
        $response = ['is_multiple_lobs_allowed' => false, 'total_count' => 0, 'route' => 'javascript:void(0)'];
        $userRoles = auth()->user()?->getRoleNames()->toArray() ?? [];
        $quoteTypes = [
            QuoteTypes::HOME,
            QuoteTypes::HEALTH,
            QuoteTypes::YACHT,
            QuoteTypes::PET,
            QuoteTypes::CYCLE,
            QuoteTypes::CORPLINE,
        ];

        $cardViewRoute = [
            QuoteTypes::HEALTH->name => route('health.cards'),
            QuoteTypes::HOME->name => route('home-cardView'),
            QuoteTypes::PET->name => route('pet-quotes-card'),
            QuoteTypes::YACHT->name => route('yacht-quotes-card'),
            QuoteTypes::CYCLE->name => route('cycle-quotes-card'),
            QuoteTypes::CORPLINE->name => route('business.cards'),
        ];

        foreach($quoteTypes as $quoteType) {
            if(in_array($quoteType->name . '_ADVISOR', $userRoles) || in_array($quoteType->name . '_MANAGER', $userRoles)) {
                $allowedLOBs = ++$allowedLOBs;
                $allowedQuoteTypes[] = $quoteType;
            }
        }

        foreach ($allowedQuoteTypes as $allowedQuoteType) {
            $quoteTypeEnum = $allowedQuoteType;
            $allowedQuoteType = strtolower($allowedQuoteType->name);
            $modelType = (in_array(ucfirst($allowedQuoteType), newUi()) && checkPersonalQuotes(ucfirst($allowedQuoteType))) ? $nameSpace.'PersonalQuote' : $nameSpace.ucwords($allowedQuoteType).'Quote';
            
            if (! class_exists($modelType)) {
                return false;
            }

            $quoteCount = checkPersonalQuotes(ucwords($allowedQuoteType)) ? 
                $modelType::whereNotNull('stale_at')->where('quote_type_id', $quoteTypeEnum->id())->count() : 
                $modelType::whereNotNull('stale_at')->count();

            $response['quotes_count'][$allowedQuoteType]['count'] = $quoteCount;
            $response['quotes_count'][$allowedQuoteType]['route'] = $cardViewRoute[strtoupper($allowedQuoteType)];
            $totalCount = $totalCount + $quoteCount;

            if($allowedLOBs > 1) {
                $response['is_multiple_lobs_allowed'] = true;
                $response['total_count'] = $totalCount;
                // Todo :: Report route need to be update, it should be stale lead report route.
                $response['route'] = route('advisor-conversion-report-view');

            } else {
                $response['is_multiple_lobs_allowed'] = false;
                $response['total_count'] = $totalCount;
                $response['route'] = $cardViewRoute[strtoupper($allowedQuoteType)];
            }

        }

        return $response;
    }
}