<?php

namespace App\Services;

use App\Enums\QuoteTypes;
use App\Models\Nationality;
use App\Models\NationalityAllocationConfiguration;
use App\Models\NationalityAllocationConfigurationUser;
use App\Models\QuoteType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class NationalityAllocationService
{
    private static function resolveQuoteType(QuoteTypes $quoteType): QuoteTypes
    {
        if ($quoteType === QuoteTypes::CORPLINE || $quoteType === QuoteTypes::GROUP_MEDICAL) {
            return QuoteTypes::BUSINESS;
        }

        return $quoteType;
    }

    public static function find(QuoteTypes $quoteType, $nationalityId): ?NationalityAllocationConfiguration
    {
        if (! $nationalityId) {
            return null;
        }

        $quoteType = self::resolveQuoteType($quoteType);

        return NationalityAllocationConfiguration::query()
            ->where('quote_type_id', $quoteType->id())
            ->where('nationality_id', $nationalityId)
            ->active()
            ->first();
    }

    public static function getUserIDs(NationalityAllocationConfiguration $config): array
    {
        return $config->users->pluck('id')->toArray();
    }

    private static function getConfigIds(QuoteTypes $quoteType): array
    {
        return NationalityAllocationConfiguration::query()
            ->where('quote_type_id', $quoteType->id())
            ->active()
            ->pluck('id')
            ->toArray();
    }

    public static function getExcludedUserIds(QuoteTypes $quoteType): array
    {
        $quoteType = self::resolveQuoteType($quoteType);

        $configIds = self::getConfigIds($quoteType);

        return NationalityAllocationConfigurationUser::whereIn('nationality_allocation_configuration_id', $configIds)->pluck('user_id')->toArray();
    }

    public function createConfiguration(array $data, int $userId): NationalityAllocationConfiguration
    {
        $configuration = NationalityAllocationConfiguration::create([
            'quote_type_id' => $data['quote_type_id'],
            'nationality_id' => $data['nationality_id'],
            'should_skip_sic' => $data['should_skip_sic'] ?? false,
            'activated_at' => $data['activated_at'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        if (isset($data['user_ids']) && ! empty($data['user_ids'])) {
            $configuration->users()->attach($data['user_ids']);
        }

        return $configuration;
    }

    public function deleteConfiguration(int $configId): bool
    {
        $config = NationalityAllocationConfiguration::find($configId);

        if (! $config) {
            return false;
        }

        $config->users()->detach();

        return $config->delete();
    }

    public function getAuditLogs(int $configId, ?int $perPage = null)
    {
        $logs = DB::table('audits')
            ->select('audits.*', 'users.name')
            ->leftJoin('users', 'audits.user_id', 'users.id')
            ->where('auditable_id', $configId)
            ->where('auditable_type', NationalityAllocationConfiguration::class)
            ->where('event', '!=', 'created')
            ->when($perPage,
                fn ($q) => $q->orderBy('created_at', 'desc')->paginate($perPage),
                fn ($q) => $q->orderBy('created_at', 'desc')->get()
            );

        $nationalities = Nationality::whereIn('id', $this->extractIdsFromAudits($logs, 'nationality_id'))->get()->keyBy('id');
        $quoteTypes = QuoteType::whereIn('id', $this->extractIdsFromAudits($logs, 'quote_type_id'))->get()->keyBy('id');

        $logs = collect($logs)->map(function ($log) use ($nationalities, $quoteTypes) {
            $log = (object) $log;
            $log->old_values = json_decode($log->old_values);
            $log->new_values = json_decode($log->new_values);

            $this->addLabelsToValues($log->old_values, $nationalities, $quoteTypes);
            $this->addLabelsToValues($log->new_values, $nationalities, $quoteTypes);

            if ($log->event === 'sync' || $log->event === 'attach' || $log->event === 'detach') {
                $log->relation_changes = $this->processRelationChanges($log);
            }

            return $log;
        });

        return $logs;
    }

    private function extractIdsFromAudits($audits, string $field): array
    {
        $ids = [];

        foreach ($audits as $audit) {
            $oldValues = json_decode($audit->old_values, true) ?? [];
            $newValues = json_decode($audit->new_values, true) ?? [];

            if (isset($oldValues[$field])) {
                $ids[] = $oldValues[$field];
            }

            if (isset($newValues[$field])) {
                $ids[] = $newValues[$field];
            }
        }

        return array_unique(array_filter($ids));
    }

    private function addLabelsToValues($values, $nationalities, $quoteTypes): void
    {
        if (! $values) {
            return;
        }

        if (isset($values->nationality_id) && isset($nationalities[$values->nationality_id])) {
            $values->nationality_label = $nationalities[$values->nationality_id]->text;
        }

        if (isset($values->quote_type_id) && isset($quoteTypes[$values->quote_type_id])) {
            $values->quote_type_label = $quoteTypes[$values->quote_type_id]->text;
        }
    }

    private function processRelationChanges($log): array
    {
        $changes = [];

        if (isset($log->new_values->relation)) {
            $relation = $log->new_values->relation;

            if ($relation === 'users') {
                // Get the IDs of attached and detached users
                $attached = $log->new_values->attached ?? [];
                $detached = $log->new_values->detached ?? [];

                // Get user details
                $attachedUsers = [];
                $detachedUsers = [];

                if (! empty($attached)) {
                    $attachedUsers = User::whereIn('id', $attached)->get(['id', 'name', 'email'])->toArray();
                }

                if (! empty($detached)) {
                    $detachedUsers = User::whereIn('id', $detached)->get(['id', 'name', 'email'])->toArray();
                }

                $changes = [
                    'attached' => $attachedUsers,
                    'detached' => $detachedUsers,
                ];
            }
        }

        return $changes;
    }
}
