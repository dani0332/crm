<?php

namespace App\Services;

use App\Enums\EaModelEnum;
use App\Enums\LeadSourceEnum;
use App\Jobs\SendEALeadSubmittedEmailJob;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class EACollaborateHelper
{
    public static function applyEAIMCRMSource(array &$payload): void
    {
        $eaModel = request()->input('ea_model');

        if (! $eaModel) {
            return;
        }

        // TODO: [PII] Remove email from this log after 2 weeks in production.
        LoggerService::info('EACollaborateHelper: EA model detected on lead creation', [
            'ea_model' => $eaModel,
            'user_id' => Auth::id(),
            'quote_type_id' => $payload['quoteTypeId'] ?? null,
            'email' => $payload['email'] ?? null,
            'source_before' => $payload['source'] ?? null,
        ]);

        if ($eaModel !== EaModelEnum::Collaborate->value) {
            LoggerService::info('EACollaborateHelper: Referral model — no payload changes applied', [
                'ea_model' => $eaModel,
                'advisor_id' => $payload['advisorId'] ?? null,
            ]);

            return;
        }

        $email = $payload['email'] ?? '';
        $mobileNo = $payload['mobileNo'] ?? '';
        $quoteTypeId = $payload['quoteTypeId'] ?? 0;

        $duplicateService = app(EALeadDuplicateService::class);

        if ($duplicateService->isBlockedByRenewalExpiry($email, $mobileNo, $quoteTypeId)) {
            LoggerService::warning('EACollaborateHelper: Blocked by active renewal-upload lead', [
                'ea_model' => $eaModel,
                'email' => $email,
                'mobile_no' => $mobileNo,
                'quote_type_id' => $quoteTypeId,
            ]);

            $message = 'A renewal-upload lead exists for this client and has not yet expired.';

            throw new HttpResponseException(back()->withErrors(['email' => $message]));
        }

        $existing = $duplicateService->findDuplicate($email, $mobileNo, $quoteTypeId);

        if ($existing) {
            $advisorName = optional($existing->advisor)->name;
            $identifier = $advisorName ? "Existing Advisor : {$advisorName}" : "Existing Lead Ref : {$existing->code}";
            $message = "A lead already exists for this client within the past 60 days.\n{$identifier}";

            LoggerService::warning('EACollaborateHelper: Duplicate lead detected for EA model', [
                'ea_model' => $eaModel,
                'email' => $email,
                'mobile_no' => $mobileNo,
                'quote_type_id' => $quoteTypeId,
                'existing_advisor' => $advisorName,
                'existing_code' => $existing->code,
            ]);

            throw new HttpResponseException(back()->withErrors(['email' => $message]));
        }

        $payload['source'] = LeadSourceEnum::EA_IMCRM;
        $payload['eaModel'] = EaModelEnum::Collaborate->value;
        $payload['leadGeneratorId'] = Auth::id();
        $payload['advisorId'] = Auth::id();

        LoggerService::info('EACollaborateHelper: Collaborate payload applied', [
            'ea_model' => EaModelEnum::Collaborate->value,
            'source' => $payload['source'],
            'lead_generator_id' => Auth::id(),
            'advisor_id' => $payload['advisorId'],
        ]);
    }

    public static function dispatchLeadSubmittedEmail(Model $quote, string $quoteType): void
    {
        if (! request()->input('ea_model')) {
            return;
        }

        LoggerService::info('EACollaborateHelper: Dispatching SendEALeadSubmittedEmailJob', [
            'ea_model' => request()->input('ea_model'),
            'quote_type' => $quoteType,
            'ref_id' => $quote->code ?? null,
            'uuid' => $quote->uuid ?? null,
        ]);

        SendEALeadSubmittedEmailJob::dispatch($quote, $quoteType)->delay(now()->addMinutes(1));
    }
}
