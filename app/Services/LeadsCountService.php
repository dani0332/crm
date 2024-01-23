<?php

namespace App\Services;

use App\Enums\QuoteTypes;
use Illuminate\Routing\Route;

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
            QuoteTypes::HOME,
            QuoteTypes::HEALTH,
            QuoteTypes::YACHT,
            QuoteTypes::PET,
            QuoteTypes::CYCLE,
            QuoteTypes::CORPLINE,
        ];

        $cardViewRoute = [
            QuoteTypes::HEALTH->name => Route::hasMacro('health.cards') ? route('health.cards') : '',
            QuoteTypes::HOME->name => Route::hasMacro('home-cardView') ? route('home-cardView') : '',
            QuoteTypes::PET->name => Route::hasMacro('pet-quotes-card') ? route('pet-quotes-card') : '',
            QuoteTypes::YACHT->name => Route::hasMacro('yacht-quotes-card') ? route('yacht-quotes-card') : '',
            QuoteTypes::CYCLE->name => Route::hasMacro('cycle-quotes-card') ? route('cycle-quotes-card') : '',
            QuoteTypes::CORPLINE->name => Route::hasMacro('business.cards') ? route('business.cards') : '',
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

            // Need to verify the stale_at where check it should be fetch only 90 days old leads.
            $quoteCount = checkPersonalQuotes(ucwords($allowedQuoteType)) ? 
                $modelType::whereNotNull('stale_at')->where('quote_type_id', $quoteTypeEnum->id())->where('stale_at', '<=', date(config('constants.DATE_FORMAT_ONLY'), strtotime('-90 days')))->count() : 
                $modelType::whereNotNull('stale_at')->where('stale_at', '<=', date(config('constants.DATE_FORMAT_ONLY'), strtotime('-90 days')))->count();

            $response['quotes_count'][$allowedQuoteType]['count'] = $quoteCount;
            $response['quotes_count'][$allowedQuoteType]['quote_route'] = $cardViewRoute[strtoupper($allowedQuoteType)];
            $totalCount = $totalCount + $quoteCount;

            if($allowedLOBs > 1) {
                $response['is_multiple_lobs_allowed'] = true;
                $response['total_count'] = $totalCount;
                // Todo :: Report route need to be update, it should be stale lead report route.
                $response['quote_route'] = route('advisor-conversion-report-view');

            } else {
                $response['is_multiple_lobs_allowed'] = false;
                $response['total_count'] = $totalCount;
                $response['quote_route'] = $cardViewRoute[strtoupper($allowedQuoteType)];
            }

        }

        return $response;
    }
}