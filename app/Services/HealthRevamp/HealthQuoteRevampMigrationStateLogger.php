<?php

namespace App\Services\HealthRevamp;

use App\Models\CustomerMembers;
use App\Models\HealthQuote;
use App\Services\Logger\LoggerService;

final class HealthQuoteRevampMigrationStateLogger
{
    /**
     * @var list<string>
     */
    private const HEALTH_QUOTE_LOG_ATTRIBUTES = [
        'id',
        'cover_for_id',
        'insure_code',
        'policy_holder_code',
        'gender',
        'marital_status_id',
        'policy_holder_category_code',
        'salary_band_id',
        'visa_category_id',
        'member_category_id',
    ];

    /**
     * @var list<string>
     */
    private const CUSTOMER_MEMBER_LOG_ATTRIBUTES = [
        'id',
        'customer_type',
        'code',
        'first_name',
        'last_name',
        'is_principal',
        'is_policy_holder',
        'is_insured',
        'is_third_party_payer',
        'is_pec_marked',
        'gender',
        'marital_status_id',
        'relation_code',
        'salary_band_id',
        'visa_category_id',
        'member_category_id',
        'emirate_of_your_visa_id',
    ];

    /**
     * @return array{health_quote: array<string, mixed>, customer_members: array<int|string, array<string, mixed>>}
     */
    /**
     * Captures and logs the full pre-migration state of the health quote and its members.
     * Returns snapshots that are passed to logLeadStateAfter to compute the diff.
     *
     * @return array{health_quote: array<string, mixed>, customer_members: array<int|string, array<string, mixed>>}
     */
    public function logLeadStateBefore(HealthQuote $healthQuote): array
    {
        $beforeQuote = $this->snapshotHealthQuoteAttributes($healthQuote);
        $beforeMembers = $this->snapshotCustomerMembers($healthQuote);

        LoggerService::info('Health quote revamp migration: lead state before', extra: $this->buildHealthMigrationLogContext(
            $healthQuote,
            $beforeQuote,
            $beforeMembers
        ));

        return [
            'health_quote' => $beforeQuote,
            'customer_members' => $beforeMembers,
        ];
    }

    /**
     * @param  array<string, mixed>  $beforeQuote
     * @param  array<int|string, array<string, mixed>>  $beforeMembers
     */
    /**
     * Re-fetches the quote after mutation, diffs it against the before snapshots, and logs the result.
     * Only includes a 'changed' key in the log payload when actual differences exist.
     *
     * @param  array<string, mixed>  $beforeQuote
     * @param  array<int|string, array<string, mixed>>  $beforeMembers
     */
    public function logLeadStateAfter(
        HealthQuote $healthQuote,
        array $beforeQuote,
        array $beforeMembers,
    ): void {
        $healthQuote = $healthQuote->fresh();
        $afterQuote = $this->snapshotHealthQuoteAttributes($healthQuote);
        $afterMembers = $this->snapshotCustomerMembers($healthQuote);

        $changed = array_filter([
            'health_quote' => $this->diffLogAttributeMaps($beforeQuote, $afterQuote),
            'customer_members' => $this->diffCustomerMemberSnapshots($beforeMembers, $afterMembers),
        ]);

        $afterContext = $this->buildHealthMigrationLogContext(
            $healthQuote,
            $afterQuote,
            $afterMembers
        );
        if ($changed !== []) {
            $afterContext['changed'] = $changed;
        }

        LoggerService::info('Health quote revamp migration: lead state after', extra: $afterContext);
    }

    /**
     * Extracts the subset of health quote attributes tracked for migration logging.
     *
     * @return array<string, mixed>
     */
    public function snapshotHealthQuoteAttributes(HealthQuote $healthQuote): array
    {
        return $healthQuote->only(self::HEALTH_QUOTE_LOG_ATTRIBUTES);
    }

    /**
     * Snapshots all non-deleted members of the quote, keyed by member id for diffing.
     *
     * @return array<int|string, array<string, mixed>>
     */
    public function snapshotCustomerMembers(HealthQuote $healthQuote): array
    {
        $out = [];
        foreach (
            $healthQuote->members()
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->select(self::CUSTOMER_MEMBER_LOG_ATTRIBUTES)
                ->get() as $member
        ) {
            /** @var CustomerMembers $member */
            $out[$member->id] = $member->only(self::CUSTOMER_MEMBER_LOG_ATTRIBUTES);
        }

        return $out;
    }

    /**
     * Assembles the structured log payload shared by the before and after log entries.
     *
     * @param  array<int|string, array<string, mixed>>  $membersById
     * @return array<string, mixed>
     */
    public function buildHealthMigrationLogContext(HealthQuote $healthQuote, array $healthQuoteSnapshot, array $membersById): array
    {
        return [
            'code' => $healthQuote->code,
            'health_quote' => $healthQuoteSnapshot,
            'customer_members' => array_values($membersById),
        ];
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array<string, array{before: mixed, after: mixed}>
     */
    /**
     * Compares two flat attribute maps and returns only the keys that changed,
     * each with a {before, after} pair. Uses JSON encoding for type-safe comparison.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array<string, array{before: mixed, after: mixed}>
     */
    public function diffLogAttributeMaps(array $before, array $after): array
    {
        $out = [];
        $keys = array_unique(array_merge(array_keys($before), array_keys($after)));
        foreach ($keys as $key) {
            $b = $before[$key] ?? null;
            $a = $after[$key] ?? null;
            if (json_encode($b) !== json_encode($a)) {
                $out[$key] = ['before' => $b, 'after' => $a];
            }
        }

        return $out;
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $beforeById
     * @param  array<int|string, array<string, mixed>>  $afterById
     * @return array<string, mixed>
     */
    /**
     * Diffs two member snapshot maps (keyed by member id).
     * Rows present only in after are marked 'created'; only in before are marked 'removed';
     * rows in both are diffed at the attribute level via diffLogAttributeMaps.
     *
     * @param  array<int|string, array<string, mixed>>  $beforeById
     * @param  array<int|string, array<string, mixed>>  $afterById
     * @return array<string, mixed>
     */
    public function diffCustomerMemberSnapshots(array $beforeById, array $afterById): array
    {
        $out = [];
        foreach ($afterById as $id => $afterRow) {
            if (! isset($beforeById[$id])) {
                $out[(string) $id] = [
                    'action' => 'created',
                    'after' => $afterRow,
                ];

                continue;
            }

            $rowDiff = $this->diffLogAttributeMaps($beforeById[$id], $afterRow);
            if ($rowDiff !== []) {
                $out[(string) $id] = $rowDiff;
            }
        }

        foreach ($beforeById as $id => $beforeRow) {
            if (! isset($afterById[$id])) {
                $out[(string) $id] = [
                    'action' => 'removed',
                    'before' => $beforeRow,
                ];
            }
        }

        return $out;
    }
}
