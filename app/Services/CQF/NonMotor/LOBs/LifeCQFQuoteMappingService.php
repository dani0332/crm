<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\LeadSourceEnum;
use App\Enums\LookupsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use App\Models\RenewalsUploadLeads;
use App\Repositories\LookupRepository;
use App\Services\CQF\Contracts\CQFQuoteMappingInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class LifeCQFQuoteMappingService implements CQFQuoteMappingInterface
{
    public function mapRenewalQuote(Model $quote, RenewalsUploadLeads $renewalsUploadLeads, string $quoteUuid): array
    {
        if (! $quote instanceof PersonalQuote) {
            return [];
        }

        $quoteData = [
            'customer_id' => $quote->customer_id,
            'first_name' => $quote->first_name,
            'last_name' => $quote->last_name,
            'email' => $quote->email,
            'mobile_no' => $quote->mobile_no,
            'uuid' => $quoteUuid,
            'code' => sprintf('%s%s', str_replace('-', '', QuoteTypes::LIFE->shortCode()), $quoteUuid),
            'source' => LeadSourceEnum::RENEWAL_UPLOAD,
            'dob' => $quote->dob,
            'advisor_id' => null,
            'assignment_type' => null,
            'renewal_batch' => null,
            'renewal_batch_id' => null,
            'quote_status_id' => QuoteStatusEnum::NewLead,
            'renewal_import_code' => $renewalsUploadLeads->renewal_import_code,
            'previous_quote_policy_number' => $quote->policy_number,
            'previous_policy_start_date' => $quote->policy_start_date,
            'previous_policy_expiry_date' => $quote->policy_expiry_date,
            'previous_quote_policy_premium' => $quote->premium,
            'previous_advisor_id' => $quote->advisor_id,
            'previous_quote_id' => $quote->id,
            'quote_type_id' => QuoteTypeId::Life,
            'nationality_id' => $quote->nationality_id,
            'currently_insured_with_id' => $quote->currently_insured_with_id,
            'insurance_provider_id' => $quote->insurance_provider_id,
        ];

        $lookup = LookupRepository::where('key', LookupsEnum::TRANSACTION_TYPES)
            ->where('code', LookupsEnum::EXT_CUSTOMER_RENWAL)
            ->first();

        if ($lookup) {
            $quoteData['transaction_type_id'] = $lookup->id;
        }

        return $quoteData;
    }

    public function mapFailedQuoteData(Model $quote): array
    {
        if (! $quote instanceof PersonalQuote) {
            return [];
        }

        $lifeQuote = $quote->lifeQuote;

        return [
            'customer_name' => trim($quote->first_name.' '.($quote->last_name ?? '')),
            'email' => $quote->email ?? null,
            'mobile_no' => $quote->mobile_no ?? null,
            'quote_type' => str_replace('-', '', QuoteTypes::LIFE->shortCode()),
            'insurer' => $quote->insuranceProvider?->text ?? $quote->currentlyInsuredWith?->text ?? null,
            'product' => 'Life insurance',
            'product_type' => null,
            'advisor' => null,
            'policy_number' => $quote->policy_number ?? null,
            'start_date' => $quote->policy_start_date ? Carbon::parse($quote->policy_start_date)->format('d/m/Y') : null,
            'end_date' => $quote->policy_expiry_date ? Carbon::parse($quote->policy_expiry_date)->format('d/m/Y') : null,
            'batch' => null,
            'sum_insured_value' => $lifeQuote?->sum_insured_value ?? null,
            'plan_name' => null,
            'previous_advisor' => $quote->advisor?->email ?? null,
            'previous_quote_policy_premium' => $quote->premium ?? null,
            'source' => $quote->source ?? null,
            'notes' => $quote->notes ?? null,
            'errors' => $quote->validation_errors ?? null,
        ];
    }
}
