<?php

namespace App\Strategies\Allocations;

use App\Enums\QuoteTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;

class AiAdvisorAllocator
{
    public static function try(QuoteTypes $quoteType, string $uuid, bool $skipAIAdvisor = false)
    {
        if ($skipAIAdvisor) {
            return null;
        }

        $lead = $quoteType->model()->where('uuid', $uuid)->first();

        // If human advisor is already assigned, skip AI advisor allocation
        if ($lead->advisor_id) {
            return null;
        }

        if ($lead->isAIAdviserRequired()) {
            ! $lead->ai_advisor_id && $lead->assignToAIAdvisor();

            return self::makeResponse($quoteType, $lead);
        } else {
            $lead->unAssignAIAdvisor();
        }

        return null;
    }

    private static function makeResponse(QuoteTypes $quoteType, Model $lead)
    {
        $response = [
            'advisorId' => $lead->ai_advisor_id,
            'isAIAdvisor' => true,
            'message' => 'AI Advisor assigned successfully',
            'status' => Response::HTTP_OK,
        ];

        if ($quoteType === QuoteTypes::CAR) {
            if ($lead->tier) {
                $response['tierId'] = $lead->tier->id;
                $response['tierName'] = $lead->tier->name;
            }
        }

        return $response;
    }
}
