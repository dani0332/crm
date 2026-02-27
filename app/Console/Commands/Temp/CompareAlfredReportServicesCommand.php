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
 *   php artisan alfred:compare-services --quote-type=Car --from=2025-08-01 --to=2025-08-31 --dump-sql
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
        {--sale-leads=       : Optional sale_leads filter (Yes / No)}
        {--dump-sql          : Dump raw SQL queries instead of running them}';
    protected $description = '[TEMP] Compare UUID results and timing: InstantAlfredService vs InstantAlfredReportService';

    public function handle(): int
    {
        $quoteType = $this->option('quote-type');
        $from = $this->option('from');
        $to = $this->option('to');
        $segment = $this->option('segment');
        $saleLeads = $this->option('sale-leads');
        $dumpSql = $this->option('dump-sql');

        if (! $from || ! $to) {
            $this->error('--from and --to are required. Example: --from=2025-08-01 --to=2025-08-31');

            return self::FAILURE;
        }

        $params = array_filter([
            'report' => 'Consolidated',
            'quoteType' => $quoteType,
            'chat_initiated_at' => [$from, $to],
            'segment' => $segment ?: null,
            'sale_leads' => $saleLeads ?: null,
        ]);

        $this->injectFakeRequest($params);

        $this->line('');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info(' Alfred Report Service — '.($dumpSql ? 'Raw SQL Dump' : 'Comparison'));
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

        if ($dumpSql) {
            return $this->dumpRawSql($params);
        }

        return $this->runComparison($params);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SQL Dump Mode
    // ─────────────────────────────────────────────────────────────────────────

    private function dumpRawSql(array $params): int
    {
        $this->line('');

        // ── Old service ──────────────────────────────────────────────────────
        $this->info('── OLD SERVICE (InstantAlfredService) ──────────────');
        $oldQuery = app(InstantAlfredService::class)->getChatConsolidateReportQuery($params);
        $this->line($this->toRawSql($oldQuery));

        $this->line('');

        // ── New service ──────────────────────────────────────────────────────
        $this->info('── NEW SERVICE (InstantAlfredReportService) ────────');
        $newQuery = app(InstantAlfredReportService::class)->getReportQuery($params);
        $this->line($this->toRawSql($newQuery));

        $this->line('');

        // ── Sorted IDs query (Pass 1 for sortType) ───────────────────────────
        $this->info('── NEW SERVICE — Pass 1 getSortedIds() ─────────────');
        $paramsWithSort = array_merge($params, ['sortType' => 'desc']);
        $this->injectFakeRequest($paramsWithSort);
        $sortedIdsQuery = app(InstantAlfredReportService::class)->getSortedIds($paramsWithSort);
        $this->comment('(returns '.count($sortedIdsQuery).' sorted IDs — not a query object)');

        $this->line('');

        return self::SUCCESS;
    }

    /**
     * Interpolates bindings into the SQL string to produce a ready-to-run query.
     */
    private function toRawSql($query): string
    {
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $rawSql = preg_replace_callback('/\?/', function () use (&$bindings) {
            $binding = array_shift($bindings);

            if (is_null($binding)) {
                return 'NULL';
            }

            if (is_bool($binding)) {
                return $binding ? '1' : '0';
            }

            if (is_numeric($binding)) {
                return $binding;
            }

            return "'".addslashes((string) $binding)."'";
        }, $sql);

        return $rawSql;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Comparison Mode
    // ─────────────────────────────────────────────────────────────────────────

    private function runComparison(array $params): int
    {
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
                ['UUID count (raw)',    count($oldUuids),              count($newUuids)],
                ['UUID count (unique)', count($oldSet),                count($newSet)],
                ['Execution time',     round($oldMs, 1).' ms',        round($newMs, 1).' ms'],
                ['Speedup',            '—', $oldMs > 0 ? round($oldMs / $newMs, 1).'x faster' : '—'],
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

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function injectFakeRequest(array $params): void
    {
        $request = Request::create('/', 'GET', $params);
        app()->instance('request', $request);
    }
}
