<?php

namespace App\Console\Commands;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TestCyberAllocation extends Command
{

    // TODO: Remove this command after testing
    
    protected $signature = 'test:cyber-allocation 
                            {--uuid= : Test allocation for specific Cyber lead UUID}
                            {--days=7 : Number of days to look back for leads}
                            {--limit=10 : Maximum number of leads to process}
                            {--dry-run : Show leads without allocating}';

    protected $description = 'Test Cyber lead allocation backup job';

    public function handle()
    {
        $this->info('==============================================');
        $this->info('  CYBER ALLOCATION BACKUP JOB - TEST MODE');
        $this->info('==============================================');
        $this->newLine();

        $uuid = $this->option('uuid');
        $days = (int) $this->option('days');
        $limit = (int) $this->option('limit');
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No leads will be allocated');
            $this->newLine();
        }

        if ($uuid) {
            $this->testSingleLead($uuid, $isDryRun);
        } else {
            $this->testBatchAllocation($days, $limit, $isDryRun);
        }

        $this->newLine();
        $this->info('==============================================');
        $this->info('  Test completed!');
        $this->info('==============================================');

        return Command::SUCCESS;
    }

    private function testSingleLead(string $uuid, bool $isDryRun)
    {
        $this->info("Testing allocation for specific lead: {$uuid}");
        $this->newLine();

        $lead = PersonalQuote::where('uuid', $uuid)
            ->where('quote_type_id', QuoteTypeId::Cyber)
            ->with('cyberQuoteRequest:id,personal_quote_id,sic_advisor_requested')
            ->first();

        if (! $lead) {
            $this->error("❌ Cyber lead not found with UUID: {$uuid}");

            return;
        }

        $this->displayLeadInfo($lead);

        if ($lead->advisor_id) {
            $this->warn("⚠️  Lead already has an advisor assigned: {$lead->advisor?->name} (ID: {$lead->advisor_id})");
            
            if (! $this->confirm('Do you want to continue anyway?', false)) {
                $this->info('Test cancelled.');

                return;
            }
        }

        $isEligible = $this->checkEligibility($lead);

        if (! $isEligible) {
            $this->error('❌ Lead is NOT eligible for allocation');

            return;
        }

        $this->info('✅ Lead is eligible for allocation');
        $this->newLine();

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN - Skipping actual allocation');

            return;
        }

        if ($this->confirm('Proceed with allocation?', true)) {
            try {
                $this->allocateLead($lead);
                $this->newLine();
                $this->info('✅ <fg=green;options=bold>Single lead allocation test completed successfully!</>');
            } catch (\Exception $e) {
                $this->newLine();
                if (str_contains($e->getMessage(), 'does not meet allocation criteria')) {
                    $this->warn('⚠️  <fg=yellow;options=bold>Lead was skipped - does not meet allocation criteria</>');
                } else {
                    $this->error('❌ <fg=red;options=bold>Single lead allocation test failed!</>');
                }
            }
        } else {
            $this->info('Test cancelled.');
        }
    }

    private function testBatchAllocation(int $days, int $limit, bool $isDryRun)
    {
        $this->info("Testing batch allocation for Cyber leads");
        $this->info("Looking back: {$days} days");
        $this->info("Limit: {$limit} leads");
        $this->newLine();

        $startDate = now()->subDays($days)->startOfDay();
        $endDate = now();

        $this->info("Date range: {$startDate->format('Y-m-d H:i:s')} to {$endDate->format('Y-m-d H:i:s')}");
        $this->newLine();

        $leads = PersonalQuote::whereNull('advisor_id')
            ->select([
                'id',
                'uuid',
                'payment_status_id',
                'quote_status_id',
                'lead_allocation_failed_at',
                'sic_flow_enabled',
                'quote_type_id',
                'created_at',
            ])
            ->with('cyberQuoteRequest:id,personal_quote_id,sic_advisor_requested')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('quote_type_id', QuoteTypeId::Cyber)
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->orderBy('created_at', 'desc')
            ->eligibleForAllocationCyber()
            ->limit($limit)
            ->get();

        if ($leads->isEmpty()) {
            $this->warn('⚠️  No eligible Cyber leads found in the specified date range');

            return;
        }

        $this->info("Found {$leads->count()} eligible lead(s):");
        $this->newLine();

        $this->displayLeadsTable($leads);
        $this->newLine();

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN - Skipping actual allocation');

            return;
        }

        if (! $this->confirm("Proceed with allocating {$leads->count()} lead(s)?", true)) {
            $this->info('Test cancelled.');

            return;
        }

        $processed = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($leads as $lead) {
            try {
                $this->info("═══════════════════════════════════════════════════════");
                $this->info("Processing lead: <fg=white;options=bold>{$lead->uuid}</>");
                $this->allocateLead($lead);
                $processed++;
            } catch (\Exception $e) {
                if (str_contains($e->getMessage(), 'does not meet allocation criteria')) {
                    $skipped++;
                } else {
                    $this->error("Unexpected error for {$lead->uuid}: {$e->getMessage()}");
                    $failed++;
                }
            }
        }

        $this->newLine();
        $this->info("═══════════════════════════════════════════════════════");
        $this->info("<fg=white;options=bold>BATCH ALLOCATION SUMMARY</>");
        $this->info("═══════════════════════════════════════════════════════");
        $this->newLine();
        
        $total = $leads->count();
        $this->line("  <fg=cyan>Total Leads Processed:</> <fg=white>{$total}</>");
        $this->line("  <fg=green>✅ Successfully Allocated:</> <fg=white>{$processed}</>");
        $this->line("  <fg=yellow>⚠️  Skipped (Criteria Not Met):</> <fg=white>{$skipped}</>");
        $this->line("  <fg=red>❌ Failed (Errors):</> <fg=white>{$failed}</>");
        $this->newLine();
        
        if ($processed > 0) {
            $this->info("  🎉 <fg=green>{$processed} lead(s) successfully assigned to advisors!</>");
        }
        
        if ($skipped > 0) {
            $this->warn("  ℹ️  <fg=yellow>{$skipped} lead(s) skipped - they don't meet allocation criteria</>");
            $this->line("     <fg=gray>(Unpaid leads without SIC advisor request)</>");
        }
        
        if ($failed > 0) {
            $this->error("  ⚠️  <fg=red>{$failed} lead(s) encountered unexpected errors</>");
        }
    }

    private function displayLeadInfo(PersonalQuote $lead)
    {
        $isPaid = $lead->isPaymentAuthorizedOrDeclined();
        $sicRequested = $lead->cyberQuoteRequest?->sic_advisor_requested ?? false;

        $createdAt = $lead->created_at instanceof \Carbon\Carbon 
            ? $lead->created_at->format('Y-m-d H:i:s')
            : Carbon::parse($lead->created_at)->format('Y-m-d H:i:s');

        $this->table(
            ['Property', 'Value'],
            [
                ['UUID', $lead->uuid],
                ['Created At', $createdAt],
                ['Quote Status', QuoteStatusEnum::getDescription($lead->quote_status_id)],
                ['Payment Status', $lead->payment_status_id],
                ['Is Paid', $isPaid ? '✅ Yes' : '❌ No'],
                ['SIC Flow Enabled', $lead->sic_flow_enabled ? '✅ Yes' : '❌ No'],
                ['SIC Advisor Requested', $sicRequested ? '✅ Yes' : '❌ No'],
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
            $sicRequested = $lead->cyberQuoteRequest?->sic_advisor_requested ?? false;

            $createdAt = $lead->created_at instanceof \Carbon\Carbon 
                ? $lead->created_at->format('Y-m-d H:i:s')
                : Carbon::parse($lead->created_at)->format('Y-m-d H:i:s');

            $rows[] = [
                $lead->uuid,
                $createdAt,
                $isPaid ? '✅' : '❌',
                $sicRequested ? '✅' : '❌',
                $lead->sic_flow_enabled ? '✅' : '❌',
                $lead->lead_allocation_failed_at ? '✅' : '❌',
            ];
        }

        $this->table(
            ['UUID', 'Created At', 'Paid', 'SIC Req', 'SIC Flow', 'Failed'],
            $rows
        );
    }

    private function checkEligibility(PersonalQuote $lead): bool
    {
        $isPaid = $lead->isPaymentAuthorizedOrDeclined();
        $sicRequested = $lead->cyberQuoteRequest?->sic_advisor_requested ?? false;
        $hasRetryFlag = $lead->isAllocationFailed();

        $this->info('Checking eligibility:');
        $this->line("  - Is Paid (AUTHORISED/DECLINED): ".($isPaid ? '✅ Yes' : '❌ No'));
        $this->line("  - SIC Advisor Requested: ".($sicRequested ? '✅ Yes' : '❌ No'));
        $this->line("  - Lead Allocation Failed: ".($hasRetryFlag ? '✅ Yes (Retry)' : '❌ No'));
        $this->newLine();

        return $isPaid || $sicRequested || $hasRetryFlag;
    }

    private function allocateLead(PersonalQuote $lead)
    {
        LoggerService::startQuoteLogging($lead, LoggerFeatureEnum::ALLOCATION);

        $isPaid = $lead->isPaymentAuthorizedOrDeclined();
        $sicRequested = $lead->cyberQuoteRequest?->sic_advisor_requested ?? false;
        $hasRetryFlag = $lead->isAllocationFailed();

        // Display pre-allocation status
        $this->newLine();
        $this->line("  <fg=cyan>Lead Conditions:</>");
        $this->line("  • Payment Status: ".($isPaid ? '<fg=green>PAID</>' : '<fg=yellow>UNPAID</>'));
        $this->line("  • SIC Advisor Requested: ".($sicRequested ? '<fg=green>YES</>' : '<fg=red>NO</>'));
        $this->line("  • Retry Flag (lead_allocation_failed_at): ".($hasRetryFlag ? '<fg=green>YES</>' : '<fg=gray>NO</>'));
        $this->newLine();

        LoggerService::info('TestCyberAllocation: Allocating Cyber lead', extra: [
            'uuid' => $lead->uuid,
            'payment_status_id' => $lead->payment_status_id,
            'quote_status_id' => $lead->quote_status_id,
            'isPaid' => $isPaid,
            'sicAdvisorRequested' => $sicRequested,
            'hasRetryFlag' => $hasRetryFlag,
        ]);

        try {
            $result = QuoteTypes::CYBER->allocate(uuid: $lead->uuid);

            $lead->refresh();
            
            // Check if allocation actually succeeded by verifying advisor was assigned
            if ($lead->advisor_id) {
                $this->info("✅ <fg=green;options=bold>ALLOCATION SUCCESSFUL</>");
                $this->line("  • Lead UUID: <fg=white>{$lead->uuid}</>");
                $this->line("  • Assigned To: <fg=white>{$lead->advisor?->name}</>");
                $this->line("  • Advisor Email: <fg=white>{$lead->advisor?->email}</>");
                $this->line("  • Advisor ID: <fg=white>{$lead->advisor_id}</>");
                
                if ($isPaid) {
                    $this->line("  • Reason: <fg=cyan>Lead is PAID → Assigned to Happiness Support User</>");
                } elseif ($sicRequested) {
                    $this->line("  • Reason: <fg=cyan>SIC Advisor Requested → Assigned to hardcoded advisors</>");
                } elseif ($hasRetryFlag) {
                    $this->line("  • Reason: <fg=cyan>Retry Flag Set (lead_allocation_failed_at) → Assigned to hardcoded advisors</>");
                }
                
                LoggerService::info('TestCyberAllocation: Allocation completed successfully', extra: [
                    'advisorId' => $lead->advisor_id,
                    'advisorName' => $lead->advisor?->name,
                    'advisorEmail' => $lead->advisor?->email,
                ]);
            } else {
                // Allocation was attempted but no advisor was assigned (likely rejected by pipeline)
                $this->warn("⚠️  <fg=yellow;options=bold>ALLOCATION SKIPPED</>");
                $this->line("  • Lead UUID: <fg=white>{$lead->uuid}</>");
                
                // Determine the exact reason based on business logic
                if (! $isPaid && ! $sicRequested && ! $hasRetryFlag) {
                    $this->line("  • Reason: <fg=red>Lead does NOT meet allocation criteria</>");
                    $this->line("    <fg=gray>├─</> Payment Status: <fg=yellow>UNPAID</>");
                    $this->line("    <fg=gray>├─</> SIC Advisor Requested: <fg=red>NO</>");
                    $this->line("    <fg=gray>└─</> Retry Flag: <fg=gray>NO</>");
                    $this->newLine();
                    $this->line("  <fg=cyan>ℹ</> <fg=white>Expected Behavior:</>");
                    $this->line("    For allocation to succeed, lead must be either:");
                    $this->line("    • PAID (goes to Happiness Support User)");
                    $this->line("    • OR have SIC Advisor Requested = YES (goes to hardcoded advisors)");
                    $this->line("    • OR have Retry Flag set (lead_allocation_failed_at)");
                } elseif ($hasRetryFlag) {
                    $this->line("  • Reason: <fg=red>Pipeline error despite retry flag</>");
                    $this->line("    <fg=yellow>⚠️  This lead has a retry flag but allocation failed</>");
                    $this->line("    <fg=yellow>⚠️  Check the pipeline logic in EvaluateTeamPipe</>");
                } else {
                    $this->line("  • Reason: <fg=yellow>Unknown - Pipeline stopped allocation</>");
                    $this->line("    <fg=gray>This is unexpected - check pipeline logs for details</>");
                }
                
                LoggerService::info('TestCyberAllocation: Allocation skipped - criteria not met', extra: [
                    'isPaid' => $isPaid,
                    'sicRequested' => $sicRequested,
                    'hasRetryFlag' => $hasRetryFlag,
                ]);
                
                throw new \Exception("Lead does not meet allocation criteria");
            }
        } catch (\Exception $e) {
            if (! str_contains($e->getMessage(), 'does not meet allocation criteria')) {
                $this->error("❌ <fg=red;options=bold>ALLOCATION ERROR</>");
                $this->line("  • Lead UUID: <fg=white>{$lead->uuid}</>");
                $this->line("  • Error: <fg=red>{$e->getMessage()}</>");
            }
            
            LoggerService::error('TestCyberAllocation: Allocation failed', exception: $e, extra: [
                'uuid' => $lead->uuid,
            ]);
            
            throw $e;
        }
    }
}

