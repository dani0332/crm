<?php

namespace App\Observers;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Events\CarQuoteAdvisorUpdated;
use App\Jobs\MACRM\CancelCourierQuoteOnMACRM;
use App\Jobs\MACRM\SyncCourierQuoteWithMacrm;
use App\Jobs\MAWelcomeJob;
use App\Models\CarQuote;
use App\Traits\PersonalQuoteSyncTrait;

class CarQuoteObserver
{
    use PersonalQuoteSyncTrait;

    public function updated(CarQuote $lead)
    {
        $changes = [];

        foreach ($lead->getDirty() as $attribute => $value) {
            if ($lead->isDirty($attribute)) {
                $changes[$attribute] = [
                    'old' => $lead->getOriginal($attribute),
                    'new' => $value,
                ];
            }
        }

        if ($lead->isDirty('advisor_id')) {
            $oldAdvisorId = $changes['advisor_id']['old'];
            event(new CarQuoteAdvisorUpdated($lead, $oldAdvisorId));
        }

        $dirty = $lead->getDirty();
        if ($lead->isDirty('quote_status_id')) {
            if ($lead->quote_status_id === QuoteStatusEnum::TransactionApproved) {
                MAWelcomeJob::dispatchIf(
                    isMyAlfredCampaignEnabled(getAppStorageValueByKey(ApplicationStorageEnums::EMAIL_CAMPAIGN)) && $lead->customer,
                    $lead->customer?->first_name,
                    $lead->customer?->last_name,
                    $lead->customer?->email,
                    $lead->customer?->mobile_no,
                    'CUSTOMER_UPDATE',
                    'customer-update-myalfred-we'
                );
                CarQuote::withoutEvents(function () use ($lead) {
                    $lead->update([
                        'transaction_approved_at' => now(),
                        'quote_status_date' => now(),
                    ]);
                });
                $dirty = [...$dirty, 'transaction_approved_at' => $lead->transaction_approved_at];
            }

            if ($lead->quote_status_id === QuoteStatusEnum::PolicyIssued) {
                SyncCourierQuoteWithMacrm::dispatch($lead, QuoteTypeId::Car);
            }

            if (in_array($lead->quote_status_id, [QuoteStatusEnum::PolicyCancelled])) {
                CancelCourierQuoteOnMACRM::dispatch($lead, QuoteTypeId::Car);
            }
        }

        $this->syncQuote($lead, $dirty);
    }
}
