<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\TeamNameEnum;
use App\Enums\TiersIdEnum;
use App\Models\CarQuote;
use App\Models\User;
use App\Services\ApplicationStorageService;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TestCarAllocation extends Command
{
    // TODO: Remove this command after testing

    protected $signature = 'test:car-allocation';
    protected $description = 'Test Car lead allocation - fetches leads from past week and prompts for allocation';

    public function handle(ApplicationStorageService $applicationStorageService)
    {
        $this->info('==============================================');
        $this->info('  CAR ALLOCATION BACKUP JOB - TEST MODE');
        $this->info('==============================================');
        $this->newLine();

        $this->testBatchAllocation($applicationStorageService);

        $this->newLine();
        $this->info('==============================================');
        $this->info('  Test completed!');
        $this->info('==============================================');

        return Command::SUCCESS;
    }

    public function testExecuteCarAllocation($quoteType, $to, $chunkSize, $allocationStartDate, ApplicationStorageService $applicationStorageService)
    {
        $this->info('═══════════════════════════════════════════════════════');
        $this->info('<fg=white;options=bold>TESTING EXECUTE CAR ALLOCATION LOGIC</>');
        $this->info('═══════════════════════════════════════════════════════');
        $this->newLine();

        $this->line("  <fg=cyan>Parameters:</>");
        $this->line("  • Quote Type: <fg=white>{$quoteType}</>");
        $this->line("  • To Date: <fg=white>{$to->format('Y-m-d H:i:s')}</>");
        $this->line("  • Chunk Size: <fg=white>{$chunkSize}</>");
        $this->line("  • Allocation Start Date: <fg=white>{$allocationStartDate->format('Y-m-d H:i:s')}</>");
        $this->newLine();

        $processedRecords = 0;
        $shouldIncludeDubaiNow = $applicationStorageService->getValueByKey(ApplicationStorageEnums::APPLY_DUBAI_NOW_EXCLUSION) == 1;
        $exemptedLeadSources = [LeadSourceEnum::IMCRM, LeadSourceEnum::INSLY, LeadSourceEnum::REVIVAL];

        $this->line("  <fg=cyan>Variables:</>");
        $this->line("  • Should Include Dubai Now: <fg=white>".($shouldIncludeDubaiNow ? 'Yes' : 'No')."</>");
        $this->line("  • Exempted Lead Sources: <fg=white>".implode(', ', $exemptedLeadSources)."</>");

        if ($shouldIncludeDubaiNow) {
            $exemptedLeadSources[] = LeadSourceEnum::DUBAI_NOW;
            $this->line("  • Updated Exempted Lead Sources: <fg=white>".implode(', ', $exemptedLeadSources)."</>");
        }
        $this->newLine();

        $aiAdvisorId = null;
        try {
            $aiAdvisor = User::getAiAdvisor();
            $aiAdvisorId = $aiAdvisor?->id;
        } catch (\Exception $e) {
            $this->warn('⚠️  Could not fetch AI advisor from database, will only query leads without advisors');
        }

        $leads = CarQuote::query()
            ->where(function ($q) use ($aiAdvisorId) {
                $q->whereNull('advisor_id');
                if ($aiAdvisorId) {
                    $q->orWhere(function ($sq) use ($aiAdvisorId) {
                        $sq->where('advisor_id', $aiAdvisorId);
                        $sq->where('ai_advisor_required', false);
                    });
                }
            })
            ->select([
                'uuid',
                'payment_status_id',
                'source',
                'is_renewal_tier_email_sent',
                'lead_allocation_failed_at',
                'sic_flow_enabled',
                'sic_advisor_requested',
                'quote_status_id',
            ])
            ->where('created_at', '<=', $to)
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->whereNotIn('source', $exemptedLeadSources)
            ->orderByDesc('created_at')
            ->where(function ($q) {
                $q->eligibleForAllocation(QuoteTypes::CAR);
                $q->orWhere(function ($sq) {
                    $sq->whereNull('advisor_id')->where('ai_advisor_required', true);
                });
            })
            ->take($chunkSize);

        $leads->logRawSql();

        $this->info("  <fg=cyan>Query executed with chunk size:</> <fg=white>{$chunkSize}</>");
        $this->line("  <fg=cyan>AI Advisor ID:</> <fg=white>".($aiAdvisorId ? $aiAdvisorId : 'N/A (will only query leads without advisors)')."</>");
        $this->newLine();

        $teamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);
        $this->line("  <fg=cyan>Team ID (SIC_UNASSISTED):</> <fg=white>{$teamId}</>");
        $this->newLine();

        try {
            $leadsCollection = $leads->get();
        } catch (\Illuminate\Database\QueryException $e) {
            $this->error('❌ Database connection error!');
            $this->newLine();
            $this->line('  <fg=yellow>This command requires a database connection to query Car leads.</>');
            $this->line('  <fg=gray>Please ensure your database is running and configured correctly.</>');
            $this->newLine();
            $this->line('  <fg=cyan>Error details:</>');
            $this->line('  <fg=gray>'.str_replace("\n", "\n  ", $e->getMessage()).'</>');
            $this->newLine();

            return;
        } catch (\Exception $e) {
            $this->error('❌ Unexpected error while querying leads!');
            $this->newLine();
            $this->line('  <fg=red>'.$e->getMessage().'</>');
            $this->newLine();

            return;
        }
        $this->info("  <fg=cyan>Found leads:</> <fg=white>{$leadsCollection->count()}</>");
        $this->newLine();

        foreach ($leadsCollection as $lead) {
            if ($lead->tier_id == TiersIdEnum::TIER_R) {
                $this->warn("  ⚠️  Skipping Tier R lead: <fg=white>{$lead->uuid}</>");
                continue;
            }

            $this->line("  <fg=cyan>Processing lead:</> <fg=white>{$lead->uuid}</>");
            $this->line("    • Payment Status ID: <fg=white>{$lead->payment_status_id}</>");
            $this->line("    • Source: <fg=white>{$lead->source}</>");
            $this->line("    • SIC Advisor Requested: <fg=white>".($lead->sic_advisor_requested ? 'Yes' : 'No')."</>");
            $this->line("    • SIC Flow Enabled: <fg=white>".($lead->sic_flow_enabled ? 'Yes' : 'No')."</>");

            LoggerService::startQuoteLogging($lead, LoggerFeatureEnum::ALLOCATION);

            LoggerService::info(self::class.': Processing car quote allocation', extra: [
                'payment_status_id' => $lead->payment_status_id,
                'source' => $lead->source,
                'is_renewal_tier_email_sent' => $lead->is_renewal_tier_email_sent,
                'lead_allocation_failed_at' => $lead->lead_allocation_failed_at,
                'sic_flow_enabled' => $lead->sic_flow_enabled,
                'sic_advisor_requested' => $lead->sic_advisor_requested,
                'quote_status_id' => $lead->quote_status_id,
            ]);

            $currentTeamId = $lead->payment_status_id == PaymentStatusEnum::AUTHORISED ? $teamId : false;
            $this->line("    • Current Team ID: <fg=white>".($currentTeamId ? $currentTeamId : 'false (will use default allocation)')."</>");

            QuoteTypes::CAR->allocate(uuid: $lead->uuid, teamId: $currentTeamId);
            $processedRecords++;
            $this->info("    ✅ <fg=green>Allocated successfully</>");
            LoggerService::info(self::class.': Processed car quote allocation');
            $this->newLine();
        }

        $this->info('═══════════════════════════════════════════════════════');
        $this->info('<fg=white;options=bold>ALLOCATION SUMMARY</>');
        $this->info('═══════════════════════════════════════════════════════');
        $this->line("  <fg=cyan>Total Leads Found:</> <fg=white>{$leadsCollection->count()}</>");
        $this->line("  <fg=cyan>Processed Records:</> <fg=white>{$processedRecords}</>");
        $this->newLine();

        if ($processedRecords === 0) {
            LoggerService::info(self::class.': No records found', extra: [
                'quote_type' => $quoteType instanceof QuoteTypes ? $quoteType->value : QuoteTypeId::getDescription($quoteType),
            ]);
        }
    }

    private function testBatchAllocation(ApplicationStorageService $applicationStorageService)
    {
        $this->info('Fetching Car leads from the past week...');
        $this->newLine();

        $startDate = now()->subWeek()->startOfDay();
        $endDate = now()->subMinutes(5);

        $this->info("Date range: {$startDate->format('Y-m-d H:i:s')} to {$endDate->format('Y-m-d H:i:s')}");
        $this->newLine();

        // Try to get Dubai Now exclusion setting, default to false if DB unavailable
        try {
            $shouldIncludeDubaiNow = $applicationStorageService->getValueByKey(ApplicationStorageEnums::APPLY_DUBAI_NOW_EXCLUSION) == 1;
        } catch (\Exception $e) {
            $this->warn('⚠️  Could not fetch Dubai Now exclusion setting from database, defaulting to false');
            $shouldIncludeDubaiNow = false;
        }

        $exemptedLeadSources = [LeadSourceEnum::IMCRM, LeadSourceEnum::INSLY, LeadSourceEnum::REVIVAL];

        if ($shouldIncludeDubaiNow) {
            $exemptedLeadSources[] = LeadSourceEnum::DUBAI_NOW;
        }

        // Try to get AI advisor ID, handle gracefully if DB unavailable
        $aiAdvisorId = null;
        try {
            $aiAdvisor = User::getAiAdvisor();
            $aiAdvisorId = $aiAdvisor?->id;
        } catch (\Exception $e) {
            $this->warn('⚠️  Could not fetch AI advisor from database, will only query leads without advisors');
        }

        try {
            $leads = CarQuote::query()
                ->where(function ($q) use ($aiAdvisorId) {
                    $q->whereNull('advisor_id');
                    if ($aiAdvisorId) {
                        $q->orWhere(function ($sq) use ($aiAdvisorId) {
                            $sq->where('advisor_id', $aiAdvisorId);
                            $sq->where('ai_advisor_required', false);
                        });
                    }
                })
                ->select([
                    'uuid',
                    'code',
                    'payment_status_id',
                    'source',
                    'is_renewal_tier_email_sent',
                    'lead_allocation_failed_at',
                    'sic_flow_enabled',
                    'sic_advisor_requested',
                    'quote_status_id',
                    'tier_id',
                    'ai_advisor_required',
                    'advisor_id',
                    'created_at',
                ])
                ->where('created_at', '<=', $endDate)
                ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
                ->whereNotIn('source', $exemptedLeadSources)
                ->orderByDesc('created_at')
                ->where(function ($q) {
                    $q->eligibleForAllocation(QuoteTypes::CAR);
                    $q->orWhere(function ($sq) {
                        $sq->whereNull('advisor_id')->where('ai_advisor_required', true);
                    });
                })
                ->take(1);

            $leads->logRawSql();

            $leads = $leads->get();
        } catch (\Illuminate\Database\QueryException $e) {
            $this->error('❌ Database connection error!');
            $this->newLine();
            $this->line('  <fg=yellow>This command requires a database connection to query Car leads.</>');
            $this->line('  <fg=gray>Please ensure your database is running and configured correctly.</>');
            $this->newLine();
            $this->line('  <fg=cyan>Error details:</>');
            $this->line('  <fg=gray>'.str_replace("\n", "\n  ", $e->getMessage()).'</>');
            $this->newLine();

            return;
        } catch (\Exception $e) {
            $this->error('❌ Unexpected error while querying leads!');
            $this->newLine();
            $this->line('  <fg=red>'.$e->getMessage().'</>');
            $this->newLine();

            return;
        }

        if ($leads->isEmpty()) {
            $this->warn('⚠️  No eligible Car leads found in the past week');

            return;
        }

        $this->info("Found {$leads->count()} eligible lead(s) from the past week (limited to 1 for debugging):");
        $this->newLine();

        $this->displayLeadsTable($leads);
        $this->newLine();

        if (! $this->confirm("Do you want to allocate {$leads->count()} lead(s)?", false)) {
            $this->info('Allocation cancelled.');

            return;
        }

        $processed = 0;
        $failed = 0;
        $skipped = 0;

        $maxLeadsToProcess = 1;
        $leadsProcessed = 0;

        foreach ($leads as $lead) {
            if ($leadsProcessed >= $maxLeadsToProcess) {
                $this->warn("⚠️  Reached maximum limit of {$maxLeadsToProcess} leads for debugging. Stopping allocation.");
                break;
            }

            try {
                // Skip Tier R leads
                if ($lead->tier_id == TiersIdEnum::TIER_R) {
                    $this->warn("⚠️  Skipping Tier R lead: {$lead->uuid}");
                    $skipped++;
                    continue;
                }

                $this->info('═══════════════════════════════════════════════════════');
                $this->info("Processing lead: <fg=white;options=bold>{$lead->uuid}</>");
                $this->allocateLead($lead, $applicationStorageService);
                $processed++;
                $leadsProcessed++;
            } catch (\Exception $e) {
                if (str_contains($e->getMessage(), 'does not meet allocation criteria')) {
                    $skipped++;
                } else {
                    $this->error("Unexpected error for {$lead->uuid}: {$e->getMessage()}");
                    $failed++;
                }
                $leadsProcessed++;
            }
        }

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════');
        $this->info('<fg=white;options=bold>BATCH ALLOCATION SUMMARY</>');
        $this->info('═══════════════════════════════════════════════════════');
        $this->newLine();

        $total = $leads->count();
        $this->line("  <fg=cyan>Total Leads Processed:</> <fg=white>{$total}</>");
        $this->line("  <fg=green>✅ Successfully Allocated:</> <fg=white>{$processed}</>");
        $this->line("  <fg=yellow>⚠️  Skipped (Criteria Not Met / Tier R):</> <fg=white>{$skipped}</>");
        $this->line("  <fg=red>❌ Failed (Errors):</> <fg=white>{$failed}</>");
        $this->newLine();

        if ($processed > 0) {
            $this->info("  🎉 <fg=green>{$processed} lead(s) successfully assigned to advisors!</>");
        }

        if ($skipped > 0) {
            $this->warn("  ℹ️  <fg=yellow>{$skipped} lead(s) skipped - they don't meet allocation criteria or are Tier R</>");
        }

        if ($failed > 0) {
            $this->error("  ⚠️  <fg=red>{$failed} lead(s) encountered unexpected errors</>");
        }

        if ($processed === 0) {
            LoggerService::info(\App\Console\Commands\QuoteAllocation::class.': No records found', extra: [
                'quote_type' => QuoteTypes::CAR->value,
            ]);
        }
    }

    private function displayLeadInfo(CarQuote $lead)
    {
        $isPaid = $lead->isPaymentAuthorizedOrDeclined();
        $hasRetryFlag = $lead->isAllocationFailed();
        $isAIG = $lead->isAIG(QuoteTypes::CAR);
        $isTierR = $lead->tier_id == TiersIdEnum::TIER_R;
        $isAIAdvisorRequired = $lead->ai_advisor_required ?? false;
        
        // Try to get AI advisor ID for comparison
        $aiAdvisorId = null;
        try {
            $aiAdvisor = User::getAiAdvisor();
            $aiAdvisorId = $aiAdvisor?->id;
        } catch (\Exception $e) {
            // If we can't get AI advisor, we can't determine if it's assigned
        }
        $isAIAdvisorAssigned = $aiAdvisorId && $lead->advisor_id == $aiAdvisorId;

        $createdAt = $lead->created_at instanceof \Carbon\Carbon
            ? $lead->created_at->format('Y-m-d H:i:s')
            : Carbon::parse($lead->created_at)->format('Y-m-d H:i:s');

        $tierName = $lead->tier_id ? (TiersIdEnum::getDescription($lead->tier_id) ?? "TIER_{$lead->tier_id}") : 'Not Assigned';

        $this->table(
            ['Property', 'Value'],
            [
                ['UUID', $lead->uuid],
                ['Created At', $createdAt],
                ['Quote Status', QuoteStatusEnum::getDescription($lead->quote_status_id)],
                ['Payment Status', PaymentStatusEnum::getDescription($lead->payment_status_id)],
                ['Is Paid (AUTHORISED/DECLINED)', $isPaid ? '✅ Yes' : '❌ No'],
                ['Source', $lead->source],
                ['SIC Flow Enabled', $lead->sic_flow_enabled ? '✅ Yes' : '❌ No'],
                ['SIC Advisor Requested', $lead->sic_advisor_requested ? '✅ Yes' : '❌ No'],
                ['Is AIG Lead', $isAIG ? '✅ Yes' : '❌ No'],
                ['Tier ID', $lead->tier_id ? "{$lead->tier_id} ({$tierName})" : 'N/A'],
                ['Is Tier R', $isTierR ? '⚠️  Yes (Will be skipped)' : '❌ No'],
                ['AI Advisor Required', $isAIAdvisorRequired ? '✅ Yes' : '❌ No'],
                ['Is AI Advisor Assigned', $isAIAdvisorAssigned ? '✅ Yes' : '❌ No'],
                ['Is Renewal Tier Email Sent', $lead->is_renewal_tier_email_sent ? '✅ Yes' : '❌ No'],
                ['Lead Allocation Failed At', $lead->lead_allocation_failed_at ?? 'N/A'],
                ['Current Advisor', $lead->advisor_id ? "{$lead->advisor?->name} (ID: {$lead->advisor_id})" : 'None'],
            ]
        );
    }

    private function displayLeadsTable($leads)
    {
        $rows = [];

        foreach ($leads as $lead) {
            $isPaid = $lead->isPaymentAuthorizedOrDeclined();
            $hasRetryFlag = $lead->isAllocationFailed();
            $isTierR = $lead->tier_id == TiersIdEnum::TIER_R;
            $isAIAdvisorRequired = $lead->ai_advisor_required ?? false;

            $createdAt = $lead->created_at instanceof \Carbon\Carbon
                ? $lead->created_at->format('Y-m-d H:i:s')
                : Carbon::parse($lead->created_at)->format('Y-m-d H:i:s');

            $tierName = $lead->tier_id ? (TiersIdEnum::getDescription($lead->tier_id) ?? "TIER_{$lead->tier_id}") : 'N/A';

            $rows[] = [
                $lead->uuid,
                $createdAt,
                $isPaid ? '✅' : '❌',
                $lead->sic_advisor_requested ? '✅' : '❌',
                $lead->sic_flow_enabled ? '✅' : '❌',
                $hasRetryFlag ? '✅' : '❌',
                $tierName,
                $isTierR ? '⚠️' : '✅',
                $isAIAdvisorRequired ? '✅' : '❌',
            ];
        }

        $this->table(
            ['UUID', 'Created At', 'Paid', 'SIC Req', 'SIC Flow', 'Retry', 'Tier', 'Tier R', 'AI Req'],
            $rows
        );
    }

    private function checkEligibility(CarQuote $lead, ApplicationStorageService $applicationStorageService): bool
    {
        // Try to get Dubai Now exclusion setting, default to false if DB unavailable
        try {
            $shouldIncludeDubaiNow = $applicationStorageService->getValueByKey(ApplicationStorageEnums::APPLY_DUBAI_NOW_EXCLUSION) == 1;
        } catch (\Exception $e) {
            $this->warn('⚠️  Could not fetch Dubai Now exclusion setting from database, defaulting to false');
            $shouldIncludeDubaiNow = false;
        }

        $exemptedLeadSources = [LeadSourceEnum::IMCRM, LeadSourceEnum::INSLY, LeadSourceEnum::REVIVAL];

        if ($shouldIncludeDubaiNow) {
            $exemptedLeadSources[] = LeadSourceEnum::DUBAI_NOW;
        }

        $isPaid = $lead->isPaymentAuthorizedOrDeclined();
        $hasRetryFlag = $lead->isAllocationFailed();
        $isAIG = $lead->isAIG(QuoteTypes::CAR);
        $isTierR = $lead->tier_id == TiersIdEnum::TIER_R;
        $isAIAdvisorRequired = $lead->ai_advisor_required ?? false;
        $isExemptedSource = in_array($lead->source, $exemptedLeadSources);
        $isFakeOrDuplicate = in_array($lead->quote_status_id, [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]);

        $this->info('Checking eligibility:');
        $this->line('  - Is Paid (AUTHORISED/DECLINED): '.($isPaid ? '✅ Yes' : '❌ No'));
        $this->line('  - SIC Advisor Requested: '.($lead->sic_advisor_requested ? '✅ Yes' : '❌ No'));
        $this->line('  - SIC Flow Enabled: '.($lead->sic_flow_enabled ? '✅ Yes' : '❌ No'));
        $this->line('  - Lead Allocation Failed (Retry): '.($hasRetryFlag ? '✅ Yes' : '❌ No'));
        $this->line('  - Is AIG Lead: '.($isAIG ? '✅ Yes' : '❌ No'));
        $this->line('  - Tier ID: '.($lead->tier_id ? (TiersIdEnum::getDescription($lead->tier_id) ?? "TIER_{$lead->tier_id}") : 'Not Assigned'));
        $this->line('  - Is Tier R: '.($isTierR ? '⚠️  Yes (Will be skipped)' : '❌ No'));
        $this->line('  - AI Advisor Required: '.($isAIAdvisorRequired ? '✅ Yes' : '❌ No'));
        $this->line('  - Source: '.$lead->source);
        $this->line('  - Is Exempted Source: '.($isExemptedSource ? '⚠️  Yes (Will be excluded)' : '❌ No'));
        $this->line('  - Is Fake/Duplicate: '.($isFakeOrDuplicate ? '⚠️  Yes (Will be excluded)' : '❌ No'));
        $this->newLine();

        // Check basic exclusions
        if ($isFakeOrDuplicate) {
            $this->warn('  ❌ Lead is Fake or Duplicate - NOT eligible');

            return false;
        }

        if ($isExemptedSource) {
            $this->warn('  ❌ Lead source is exempted - NOT eligible');

            return false;
        }

        if ($isTierR) {
            $this->warn('  ❌ Lead is Tier R - Will be skipped during allocation');

            return false;
        }

        // Check if eligible via eligibleForAllocation scope or AI advisor required
        $query = CarQuote::query()
            ->where('uuid', $lead->uuid)
            ->where(function ($q) {
                $q->eligibleForAllocation(QuoteTypes::CAR);
                $q->orWhere(function ($sq) {
                    $sq->whereNull('advisor_id')->where('ai_advisor_required', true);
                });
            });

        $isEligible = $query->exists();

        if ($isEligible) {
            $this->info('  ✅ Lead meets eligibility criteria');
        } else {
            $this->warn('  ❌ Lead does NOT meet eligibility criteria');
        }

        return $isEligible;
    }

    private function allocateLead(CarQuote $lead, ApplicationStorageService $applicationStorageService)
    {
        LoggerService::startQuoteLogging($lead, LoggerFeatureEnum::ALLOCATION);

        LoggerService::info(\App\Console\Commands\QuoteAllocation::class.': Processing car quote allocation', extra: [
            'payment_status_id' => $lead->payment_status_id,
            'source' => $lead->source,
            'is_renewal_tier_email_sent' => $lead->is_renewal_tier_email_sent,
            'lead_allocation_failed_at' => $lead->lead_allocation_failed_at,
            'sic_flow_enabled' => $lead->sic_flow_enabled,
            'sic_advisor_requested' => $lead->sic_advisor_requested,
            'quote_status_id' => $lead->quote_status_id,
        ]);

        $isPaid = $lead->isPaymentAuthorizedOrDeclined();
        $hasRetryFlag = $lead->isAllocationFailed();
        $isAIG = $lead->isAIG(QuoteTypes::CAR);
        $isTierR = $lead->tier_id == TiersIdEnum::TIER_R;

        // Skip Tier R leads
        if ($isTierR) {
            $this->warn('⚠️  Skipping Tier R lead - Tier R leads are not allocated');
            LoggerService::info('TestCarAllocation: Skipping Tier R lead', extra: [
                'uuid' => $lead->uuid,
                'tier_id' => $lead->tier_id,
            ]);

            return;
        }

        // Get the teamId for AUTHORISED payment status
        $teamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);
        $currentTeamId = $lead->payment_status_id == PaymentStatusEnum::AUTHORISED ? $teamId : false;

        // Display pre-allocation status
        $this->newLine();
        $this->line('  <fg=cyan>Lead Conditions:</>');
        $this->line('  • Payment Status: '.($isPaid ? '<fg=green>PAID</>' : '<fg=yellow>UNPAID</>'));
        $this->line('  • Payment Status ID: <fg=white>'.$lead->payment_status_id.' ('.PaymentStatusEnum::getDescription($lead->payment_status_id).')</>');
        $this->line('  • SIC Advisor Requested: '.($lead->sic_advisor_requested ? '<fg=green>YES</>' : '<fg=red>NO</>'));
        $this->line('  • SIC Flow Enabled: '.($lead->sic_flow_enabled ? '<fg=green>YES</>' : '<fg=red>NO</>'));
        $this->line('  • Retry Flag (lead_allocation_failed_at): '.($hasRetryFlag ? '<fg=green>YES</>' : '<fg=gray>NO</>'));
        $this->line('  • Is AIG Lead: '.($isAIG ? '<fg=green>YES</>' : '<fg=gray>NO</>'));
        $this->line('  • Tier ID: <fg=white>'.($lead->tier_id ? (TiersIdEnum::getDescription($lead->tier_id) ?? "TIER_{$lead->tier_id}") : 'Not Assigned').'</>');
        $this->line('  • Source: <fg=white>'.$lead->source.'</>');
        $this->line('  • Team ID: '.($currentTeamId ? "<fg=green>{$currentTeamId} (SIC_UNASSISTED)</>" : '<fg=gray>None (will use default allocation)</>'));
        $this->newLine();

        LoggerService::info('TestCarAllocation: Allocating Car lead', extra: [
            'uuid' => $lead->uuid,
            'payment_status_id' => $lead->payment_status_id,
            'quote_status_id' => $lead->quote_status_id,
            'source' => $lead->source,
            'tier_id' => $lead->tier_id,
            'isPaid' => $isPaid,
            'sicAdvisorRequested' => $lead->sic_advisor_requested,
            'sicFlowEnabled' => $lead->sic_flow_enabled,
            'hasRetryFlag' => $hasRetryFlag,
            'isAIG' => $isAIG,
            'teamId' => $currentTeamId,
            'is_renewal_tier_email_sent' => $lead->is_renewal_tier_email_sent,
            'ai_advisor_required' => $lead->ai_advisor_required,
        ]);

        try {
            $leadUuid = $lead->uuid;
            $result = QuoteTypes::CAR->allocate(uuid: $leadUuid, teamId: $currentTeamId);

            $refreshedLead = $lead->fresh();
            if (! $refreshedLead) {
                $refreshedLead = CarQuote::withTrashed()->where('uuid', $leadUuid)->first();
            }

            if (! $refreshedLead) {
                $this->warn('⚠️  <fg=yellow;options=bold>Could not refresh lead after allocation</>');
                $this->line("  • Lead UUID: <fg=white>{$leadUuid}</>");
                $this->line('  • Reason: <fg=gray>Lead may have been deleted or soft-deleted during allocation</>');
                LoggerService::info('TestCarAllocation: Could not refresh lead after allocation', extra: [
                    'uuid' => $leadUuid,
                ]);

                return;
            }

            $lead = $refreshedLead;

            // Check if allocation actually succeeded by verifying advisor was assigned
            $aiAdvisorId = null;
            try {
                $aiAdvisor = User::getAiAdvisor();
                $aiAdvisorId = $aiAdvisor?->id;
            } catch (\Exception $e) {
                // If we can't get AI advisor, check if any advisor is assigned
            }

            if ($lead->advisor_id && ($aiAdvisorId === null || $lead->advisor_id !== $aiAdvisorId)) {
                $this->info('✅ <fg=green;options=bold>ALLOCATION SUCCESSFUL</>');
                $this->line("  • Lead UUID: <fg=white>{$lead->uuid}</>");
                $this->line("  • Assigned To: <fg=white>{$lead->advisor?->name}</>");
                $this->line("  • Advisor Email: <fg=white>{$lead->advisor?->email}</>");
                $this->line("  • Advisor ID: <fg=white>{$lead->advisor_id}</>");
                $this->line("  • Tier ID: <fg=white>".($lead->tier_id ? (TiersIdEnum::getDescription($lead->tier_id) ?? "TIER_{$lead->tier_id}") : 'Not Assigned')."</>");

                if ($isPaid && $lead->payment_status_id == PaymentStatusEnum::AUTHORISED) {
                    $this->line('  • Reason: <fg=cyan>Lead is AUTHORISED → Assigned to SIC_UNASSISTED team</>');
                } elseif ($isPaid) {
                    $this->line('  • Reason: <fg=cyan>Lead is PAID (DECLINED/CAPTURED) → Assigned via normal allocation</>');
                } elseif ($lead->sic_advisor_requested) {
                    $this->line('  • Reason: <fg=cyan>SIC Advisor Requested → Assigned via normal allocation</>');
                } elseif ($hasRetryFlag) {
                    $this->line('  • Reason: <fg=cyan>Retry Flag Set (lead_allocation_failed_at) → Assigned via normal allocation</>');
                } elseif ($isAIG) {
                    $this->line('  • Reason: <fg=cyan>AIG Lead → Assigned via normal allocation</>');
                } else {
                    $this->line('  • Reason: <fg=cyan>Assigned via normal allocation logic</>');
                }

                LoggerService::info('TestCarAllocation: Allocation completed successfully', extra: [
                    'advisorId' => $lead->advisor_id,
                    'advisorName' => $lead->advisor?->name,
                    'advisorEmail' => $lead->advisor?->email,
                    'tierId' => $lead->tier_id,
                    'teamId' => $currentTeamId,
                ]);

                LoggerService::info(\App\Console\Commands\QuoteAllocation::class.': Processed car quote allocation');
            } elseif ($aiAdvisorId && $lead->advisor_id == $aiAdvisorId) {
                $this->warn('⚠️  <fg=yellow;options=bold>ALLOCATION ASSIGNED TO AI ADVISOR</>');
                $this->line("  • Lead UUID: <fg=white>{$lead->uuid}</>");
                $this->line("  • Assigned To: <fg=yellow>AI Advisor (ID: {$lead->advisor_id})</>");
                $this->line('  • Reason: <fg=cyan>Lead assigned to AI Advisor (may be temporary or requires human advisor)</>');

                LoggerService::info('TestCarAllocation: Allocation assigned to AI Advisor', extra: [
                    'advisorId' => $lead->advisor_id,
                    'aiAdvisorRequired' => $lead->ai_advisor_required,
                ]);

                LoggerService::info(\App\Console\Commands\QuoteAllocation::class.': Processed car quote allocation');
            } else {
                // Allocation was attempted but no advisor was assigned (likely rejected by pipeline)
                $this->warn('⚠️  <fg=yellow;options=bold>ALLOCATION SKIPPED</>');
                $this->line("  • Lead UUID: <fg=white>{$lead->uuid}</>");

                // Determine the exact reason based on business logic
                if (! $isPaid && ! $lead->sic_advisor_requested && ! $hasRetryFlag && ! $isAIG && ! $lead->sic_flow_enabled) {
                    $this->line('  • Reason: <fg=red>Lead does NOT meet allocation criteria</>');
                    $this->line('    <fg=gray>├─</> Payment Status: <fg=yellow>UNPAID</>');
                    $this->line('    <fg=gray>├─</> SIC Advisor Requested: <fg=red>NO</>');
                    $this->line('    <fg=gray>├─</> SIC Flow Enabled: <fg=red>NO</>');
                    $this->line('    <fg=gray>├─</> Retry Flag: <fg=gray>NO</>');
                    $this->line('    <fg=gray>└─</> Is AIG: <fg=gray>NO</>');
                    $this->newLine();
                    $this->line('  <fg=cyan>ℹ</> <fg=white>Expected Behavior:</>');
                    $this->line('    For allocation to succeed, lead must meet one of these conditions:');
                    $this->line('    • Be PAID (AUTHORISED/DECLINED/CAPTURED)');
                    $this->line('    • OR have SIC Advisor Requested = YES');
                    $this->line('    • OR have SIC Flow Enabled = YES with payment');
                    $this->line('    • OR have Retry Flag set (lead_allocation_failed_at)');
                    $this->line('    • OR be an AIG lead');
                } elseif ($hasRetryFlag) {
                    $this->line('  • Reason: <fg=red>Pipeline error despite retry flag</>');
                    $this->line('    <fg=yellow>⚠️  This lead has a retry flag but allocation failed</>');
                    $this->line('    <fg=yellow>⚠️  Check the pipeline logic in EvaluateTeamPipe or other pipes</>');
                } else {
                    $this->line('  • Reason: <fg=yellow>Unknown - Pipeline stopped allocation</>');
                    $this->line('    <fg=gray>This is unexpected - check pipeline logs for details</>');
                }

                LoggerService::info('TestCarAllocation: Allocation skipped - criteria not met', extra: [
                    'isPaid' => $isPaid,
                    'sicRequested' => $lead->sic_advisor_requested,
                    'sicFlowEnabled' => $lead->sic_flow_enabled,
                    'hasRetryFlag' => $hasRetryFlag,
                    'isAIG' => $isAIG,
                ]);

                throw new \Exception('Lead does not meet allocation criteria');
            }
        } catch (\Exception $e) {
            if (! str_contains($e->getMessage(), 'does not meet allocation criteria')) {
                $this->error('❌ <fg=red;options=bold>ALLOCATION ERROR</>');
                $this->line("  • Lead UUID: <fg=white>{$lead->uuid}</>");
                $this->line("  • Error: <fg=red>{$e->getMessage()}</>");
                $this->line("  • Stack Trace: <fg=gray>".substr($e->getTraceAsString(), 0, 200)."...</>");
            }

            LoggerService::error('TestCarAllocation: Allocation failed', exception: $e, extra: [
                'uuid' => $lead->uuid,
                'payment_status_id' => $lead->payment_status_id,
                'quote_status_id' => $lead->quote_status_id,
                'tier_id' => $lead->tier_id,
                'source' => $lead->source,
            ]);

            throw $e;
        }
    }
}

