<?php

namespace App\Http\Controllers;

use App\Enums\EaModelEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Jobs\SendEACollaborateRejectedEmailJob;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\PersonalQuote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EAApprovalController extends Controller
{
    public function approve(Request $request, string $quoteType, int $quoteId): JsonResponse
    {
        $quote = $this->resolveQuote($quoteType, $quoteId);

        if (! $this->isCollaborateEALead($quote)) {
            return response()->json(['message' => 'Not an EA collaborate lead.'], 422);
        }

        $userId = auth()->id();

        if ((int) $quote->advisor_id === $userId) {
            if ($quote->ea_assigned_advisor_approved_at || $quote->ea_assigned_advisor_rejected_at) {
                return response()->json(['message' => 'You have already acted on this lead.'], 422);
            }
            $quote->ea_assigned_advisor_approved_at = now();
        } elseif ((int) $quote->expert_advisor_id === $userId) {
            if ($quote->ea_expert_advisor_approved_at || $quote->ea_expert_advisor_rejected_at) {
                return response()->json(['message' => 'You have already acted on this lead.'], 422);
            }
            $quote->ea_expert_advisor_approved_at = now();
        } else {
            return response()->json(['message' => 'You are not an advisor on this lead.'], 403);
        }

        $quote->save();

        return response()->json(['success' => true]);
    }

    public function reject(Request $request, string $quoteType, int $quoteId): JsonResponse
    {
        $quote = $this->resolveQuote($quoteType, $quoteId);

        if (! $this->isCollaborateEALead($quote)) {
            return response()->json(['message' => 'Not an EA collaborate lead.'], 422);
        }

        $userId = auth()->id();

        if ((int) $quote->advisor_id === $userId) {
            if ($quote->ea_assigned_advisor_approved_at || $quote->ea_assigned_advisor_rejected_at) {
                return response()->json(['message' => 'You have already acted on this lead.'], 422);
            }
            $quote->ea_assigned_advisor_rejected_at = now();
        } elseif ((int) $quote->expert_advisor_id === $userId) {
            if ($quote->ea_expert_advisor_approved_at || $quote->ea_expert_advisor_rejected_at) {
                return response()->json(['message' => 'You have already acted on this lead.'], 422);
            }
            $quote->ea_expert_advisor_rejected_at = now();
        } else {
            return response()->json(['message' => 'You are not an advisor on this lead.'], 403);
        }

        $quote->save();

        SendEACollaborateRejectedEmailJob::dispatch($quote, $quoteType);

        return response()->json(['success' => true]);
    }

    private function resolveQuote(string $quoteType, int $quoteId): CarQuote|HealthQuote|PersonalQuote|BusinessQuote
    {
        return match ($quoteType) {
            'car' => CarQuote::findOrFail($quoteId),
            'health' => HealthQuote::findOrFail($quoteId),
            'business', 'groupmedical' => BusinessQuote::findOrFail($quoteId),
            default => PersonalQuote::findOrFail($quoteId),
        };
    }

    private function isCollaborateEALead(Model $quote): bool
    {
        return $quote->source === LeadSourceEnum::EA_IMCRM
            && $quote->ea_model === EaModelEnum::Collaborate
            && $quote->quote_status_id === QuoteStatusEnum::PaymentPending;
    }
}
