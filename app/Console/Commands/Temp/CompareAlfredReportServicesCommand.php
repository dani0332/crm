<?php

/**
 * TEMPORARY COMMAND — delete after testing is complete.
 *
 * Compares UUID sets and timing between the old InstantAlfredService
 * and the new InstantAlfredReportService for the same filter params.
 *
 * Usage:
 *   php artisan alfred:compare-services --quote-type=Car --from=2025-08-01 --to=2025-08-31
 *   php artisan alfred:compare-services --quote-type=Car --from=2025-10-01 --to=2025-10-31 --segment=SIC
 */

namespace App\Console\Commands\Temp;

use App\Services\InstantAlfredReportService;
use App\Services\InstantAlfredService;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Benchmark;
use Illuminate\Support\Facades\DB;

class CompareAlfredReportServicesCommand extends Command
{
    protected $signature = 'alfred:compare-services
        {--quote-type=Car    : Quote type (Car, Health, Travel, Home)}
        {--from=             : chat_initiated_at start date (Y-m-d)}
        {--to=               : chat_initiated_at end date   (Y-m-d)}
        {--segment=          : Optional segment filter (SIC, NON-SIC, AIG, SIC-REVIVAL)}
        {--sale-leads=       : Optional sale_leads filter (Yes / No)}';
    protected $description = '[TEMP] Compare UUID results and timing: InstantAlfredService vs InstantAlfredReportService';

    public function handle(): int
    {
        $quoteType = $this->option('quote-type');
        $from = $this->option('from');
        $to = $this->option('to');
        $segment = $this->option('segment');
        $saleLeads = $this->option('sale-leads');

        if (! $from || ! $to) {
            $this->error('--from and --to are required. Example: --from=2025-08-01 --to=2025-08-31');

            return self::FAILURE;
        }

        $params = array_filter([
            'report' => 'Consolidated',   // forces full query in the old service
            'quoteType' => $quoteType,
            'chat_initiated_at' => [$from, $to],
            'segment' => $segment ?: null,
            'sale_leads' => $saleLeads ?: null,
        ]);

        $this->injectFakeRequest($params);

        $this->line('');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info(' Alfred Report Service Comparison');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->table(
            ['Filter', 'Value'],
            [
                ['Quote Type', $quoteType],
                ['Date Range', "$from → $to"],
                ['Segment', $segment ?: '(none)'],
                ['Sale Leads', $saleLeads ?: '(none)'],
            ]
        );

        // ── Old service ──────────────────────────────────────────────────────
        $this->line('');
        $this->comment('Running OLD service (InstantAlfredService)…');
        DB::setDefaultConnection('mysql_read');

        $oldUuids = [];
        $oldMs = Benchmark::measure(function () use ($params, &$oldUuids) {
            $query = app(InstantAlfredService::class)->getChatConsolidateReportQuery($params);
            $oldUuids = $query->pluck('uuid')->toArray();
        });

        DB::setDefaultConnection('mysql');

        // ── New service ──────────────────────────────────────────────────────
        $this->comment('Running NEW service (InstantAlfredReportService)…');
        DB::setDefaultConnection('mysql_read');

        $newUuids = [];
        $newMs = Benchmark::measure(function () use ($params, &$newUuids) {
            $query = app(InstantAlfredReportService::class)->getReportQuery($params);
            $newUuids = $query->pluck('uuid')->toArray();
        });

        DB::setDefaultConnection('mysql');

        // ── Results ──────────────────────────────────────────────────────────
        $oldSet = array_unique($oldUuids);
        $newSet = array_unique($newUuids);

        sort($oldSet);
        sort($newSet);

        $onlyInOld = array_values(array_diff($oldSet, $newSet));
        $onlyInNew = array_values(array_diff($newSet, $oldSet));
        $matched = count($oldSet) === count($newSet) && empty($onlyInOld) && empty($onlyInNew);

        $this->line('');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info(' Results');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->table(
            ['Metric', 'Old Service', 'New Service'],
            [
                ['UUID count (raw)',   count($oldUuids), count($newUuids)],
                ['UUID count (unique)', count($oldSet),  count($newSet)],
                ['Execution time',     round($oldMs, 1).' ms', round($newMs, 1).' ms'],
                ['Speedup',           '—', $oldMs > 0 ? round($oldMs / $newMs, 1).'x faster' : '—'],
            ]
        );

        if ($matched) {
            $this->line('');
            $this->info('✅  UUID sets MATCH — results are identical.');
        } else {
            $this->line('');
            $this->error('❌  UUID sets DO NOT match.');

            if (! empty($onlyInOld)) {
                $this->warn('UUIDs in OLD but missing from NEW ('.count($onlyInOld).'):');
                foreach (array_slice($onlyInOld, 0, 20) as $uuid) {
                    $this->line("  - $uuid");
                }
                if (count($onlyInOld) > 20) {
                    $this->line('  … and '.(count($onlyInOld) - 20).' more');
                }
            }

            if (! empty($onlyInNew)) {
                $this->warn('UUIDs in NEW but missing from OLD ('.count($onlyInNew).'):');
                foreach (array_slice($onlyInNew, 0, 20) as $uuid) {
                    $this->line("  - $uuid");
                }
                if (count($onlyInNew) > 20) {
                    $this->line('  … and '.(count($onlyInNew) - 20).' more');
                }
            }
        }

        $this->line('');

        return self::SUCCESS;
    }

    /**
     * Injects a fake Request into the container so both services
     * can call request() and get the correct filter values.
     */
    private function injectFakeRequest(array $params): void
    {
        $request = Request::create('/', 'GET', $params);
        app()->instance('request', $request);
    }
}
