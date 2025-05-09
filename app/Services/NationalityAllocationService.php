<?php

namespace App\Services;

use App\Models\Nationality;
use App\Models\NationalityAllocationConfiguration;
use App\Models\QuoteType;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class NationalityAllocationService
{
    public function findUsersForAllocation(int $quoteTypeId, int $nationalityId): Collection
    {
        $config = NationalityAllocationConfiguration::with(['users'])
            ->where('quote_type_id', $quoteTypeId)
            ->where('nationality_id', $nationalityId)
            ->active()
            ->first();

        if (! $config) {
            return collect();
        }

        return $config->users;
    }

    public function getAllConfigurations(): Collection
    {
        return NationalityAllocationConfiguration::with(['nationality', 'quoteType', 'users'])->get();
    }

    public function createConfiguration(array $data, int $userId): NationalityAllocationConfiguration
    {
        $configuration = NationalityAllocationConfiguration::create([
            'quote_type_id' => $data['quote_type_id'],
            'nationality_id' => $data['nationality_id'],
            'is_sic_enabled' => $data['is_sic_enabled'] ?? false,
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
