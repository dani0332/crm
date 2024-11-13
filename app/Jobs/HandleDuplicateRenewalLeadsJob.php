<?php

namespace App\Jobs;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\CarQuote;
use App\Models\QuoteStatusLog;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;

class HandleDuplicateRenewalLeadsJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, Stackable;

    public $tries = 1;
    public $timeout = 300;
    public $uniqueFor = 640;
    private $uniqueId;
    public function __construct($uniqueId)
    {
        $this->uniqueId = $uniqueId;
        $this->onQueue('renewals');
    }

    public function handle()
    {
        $date = '2024-10-04';
        info('Starting to handle duplicate renewal leads for date: '.$date);
        $totalDuplicates = 0;
        try {
            $startOfDay = date(config('constants.DATE_FORMAT_ONLY').' 00:00:00', strtotime($date));
            $endOfDay = date(config('constants.DATE_FORMAT_ONLY').' 23:59:59', strtotime($date));

            CarQuote::select('previous_quote_policy_number', 'previous_policy_expiry_date')
                ->selectRaw('COUNT(*) as total')
                ->groupBy('previous_quote_policy_number', 'previous_policy_expiry_date')
                ->having('total', '>', 1)
                ->where('source', LeadSourceEnum::RENEWAL_UPLOAD)
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->whereNotNull('previous_quote_policy_number')
                ->whereNotNull('previous_policy_expiry_date')
                ->where('quote_status_id', '<>', QuoteStatusEnum::Duplicate)
                ->orderBy('previous_quote_policy_number')
                ->orderBy('previous_policy_expiry_date')
                ->chunk(500, function ($duplicateLeads) use ($date, &$totalDuplicates) {
                    foreach ($duplicateLeads as $lead) {
                        Log::info('Processing duplicate lead for policy number: '.$lead->previous_quote_policy_number);

                        $duplicateLeadInfo = CarQuote::where('previous_quote_policy_number', $lead->previous_quote_policy_number)
                            ->where('previous_policy_expiry_date', $lead->previous_policy_expiry_date)
                            ->where('source', LeadSourceEnum::RENEWAL_UPLOAD)
                            ->whereBetween('created_at', [Carbon::parse($date)->startOfDay(), Carbon::parse($date)->endOfDay()])
                            ->get();

                        $totalDuplicatesRecords = $duplicateLeadInfo->count();
                        $totalDuplicatesRecordsAdvisorAssigned = $duplicateLeadInfo->where('advisor_id', '!=', null)->count();
                        $totalDuplicatesRecordsNotAssigned = $duplicateLeadInfo->where('advisor_id', null)->count();

                        info('Total duplicate records found for policy number: '.$lead->previous_quote_policy_number.' is: '.$totalDuplicatesRecords);
                        $isRecordIgnored = false;

                        foreach ($duplicateLeadInfo as $leadInfo) {
                            if (($totalDuplicatesRecords == $totalDuplicatesRecordsAdvisorAssigned || $totalDuplicatesRecords == $totalDuplicatesRecordsNotAssigned) && ! $isRecordIgnored) {
                                $isRecordIgnored = true;
                                info('Record ignored as it has code: '.$leadInfo->code.' for lead code: '.$leadInfo->code);

                                continue;
                            } elseif ($leadInfo->advisor_id != null && ! $isRecordIgnored) {
                                $isRecordIgnored = true;
                                info('Record ignored as it has code: '.$leadInfo->code.' for lead code: '.$leadInfo->code);

                                continue;
                            }
                            $oldQuoteStatusId = $leadInfo->quote_status_id;
                            $leadInfo->previous_quote_policy_number = $leadInfo->previous_quote_policy_number.'-duplicate';
                            $leadInfo->quote_status_id = QuoteStatusEnum::Duplicate;
                            $leadInfo->save();

                            if ($oldQuoteStatusId != QuoteStatusEnum::Duplicate) {
                                QuoteStatusLog::create([
                                    'quote_type_id' => QuoteTypeId::Car,
                                    'quote_request_id' => $leadInfo->id,
                                    'current_quote_status_id' => $leadInfo->quote_status_id,
                                    'previous_quote_status_id' => $oldQuoteStatusId,
                                ]);
                            }

                            info('Record process: '.($totalDuplicates + 1).' Updated duplicate lead code: '.$leadInfo->code.' with new policy number: '.$leadInfo->previous_quote_policy_number);
                            $totalDuplicates++;
                        }
                    }
                });

            info('Completed handling duplicate renewal leads for date: '.$date);
            info('Total number of duplicate records processed: '.$totalDuplicates);
        } catch (Exception $e) {
            Log::error('Error handling duplicate renewal leads for date: '.$date.'. Error: '.$e->getMessage());
        }
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->uniqueId))->dontRelease()];
    }

    public function uniqueId(): string
    {
        return $this->uniqueId;
    }
}
