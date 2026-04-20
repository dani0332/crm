<?php

namespace App\Services;

use App\Enums\LeadSourceEnum;
use App\Facades\Ken;
use App\Models\HealthQuote;
use App\Services\Logger\LoggerService;
use App\Traits\HealthServiceUtils;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HealthQuoteRefreshPlansService extends BaseService
{
    use HealthServiceUtils;

    public function refreshPlans(Request $request): array|object
    {
        $quoteId = $request->quoteId ?? null;
        $quote = HealthQuote::where('uuid', $quoteId)->with('activeMembers')->first();

        if (! $quote) {
            return [
                'status' => false,
                'message' => 'Quote not found',
            ];
        }

        try {
            $dataArray = [
                'firstName' => $quote->first_name,
                'lastName' => $quote->last_name,
                'email' => $quote->email,
                'mobileNo' => $quote->mobile_no,
                'dob' => ! empty($quote->dob) ? Carbon::parse($quote->dob)->toDateString() : null,
                'gender' => $quote->gender,
                'emirateOfYourVisaId' => $quote->emirate_of_your_visa_id,
                'salaryBandId' => $quote->salary_band_id,
                'memberCategoryId' => $quote->member_category_id,
                'nationalityId' => $quote->nationality_id,
                'quoteUID' => $quote->uuid,
                'healthPlanTypeId' => $quote->health_plan_type_id,
                'quoteStatusId' => $quote->quote_status_id,
                'maritalStatusId' => $quote->marital_status_id,
                'filters' => [],
                'callSource' => strtolower(LeadSourceEnum::IMCRM),
                'userId' => auth()->user()->id,
                'customerType' => $quote->customer_type,
            ];

            $dataArray['memberDetails'] = $quote->activeMembers
                ->filter(function ($member) use ($quote) {
                    return $member->is_third_party_payer == 0 && $member->customer_type == $quote->customer_type;
                })
                ->map(fn ($member) => $this->prepareMemberDetailPayload($member))->all();

            LoggerService::info('Health quote refresh plans - Ken API request', extra: ['request' => $dataArray, 'uuid' => $quoteId]);

            $response = Ken::request('/get-revised-health-quote-plans', 'POST', $dataArray);

            LoggerService::info('Health quote refresh plans - Ken API response', extra: ['uuid' => $quoteId]);

            return $response;
        } catch (\Exception $e) {
            LoggerService::error('HealthQuoteRefreshPlansService - refreshPlans - Error', extra: [
                'uuid' => $quoteId,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => false,
                'message' => 'Failed to refresh plans',
            ];
        }
    }
}
