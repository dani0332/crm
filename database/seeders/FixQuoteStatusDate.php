<?php

namespace Database\Seeders;

use App\Enums\QuoteStatusEnum;
use App\Models\HealthQuote;
use App\Models\PersonalQuote;
use App\Services\CRUDService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FixQuoteStatusDate extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        HealthQuote::whereIn('quote_status_id', [QuoteStatusEnum::PolicyBooked])
            ->chunk(400, function ($leads) {
                foreach ($leads as $lead) {
                    $history = app(CRUDService::class)->getLeadAuditHistory('health', $lead->id);

                    $lastHistoryWithLeadStatus = collect($history)->filter(fn ($record) => ! empty($record->NewStatus))->first();

                    if ($lastHistoryWithLeadStatus && $lastHistoryWithLeadStatus->ModifiedAt) {
                        if (Carbon::parse($lastHistoryWithLeadStatus->ModifiedAt)->toDateTimeString() !== Carbon::parse($lead->quote_status_date)->toDateTimeString()) {
                            info("FixQuoteStatusDate: Updating Lead Quote Status Date from {$lead->quote_status_date} to {$lastHistoryWithLeadStatus->ModifiedAt} for uuid: {$lead->uuid}");
                            HealthQuote::withoutEvents(function () use ($lead, $lastHistoryWithLeadStatus) {
                                $lead->update([
                                    'quote_status_date' => Carbon::parse($lastHistoryWithLeadStatus->ModifiedAt),
                                ]);
                            });
                            PersonalQuote::withoutEvents(function () use ($lead, $lastHistoryWithLeadStatus) {
                                PersonalQuote::where('uuid', $lead->uuid)->update([
                                    'quote_status_date' => Carbon::parse($lastHistoryWithLeadStatus->ModifiedAt),
                                ]);
                            });
                        }
                    }
                }
            });
    }
}
