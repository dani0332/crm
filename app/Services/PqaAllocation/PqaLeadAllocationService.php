<?php

declare(strict_types=1);

namespace App\Services\PqaAllocation;

use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\UserStatusEnum;
use App\Models\PqaLeadAllocationConfig;
use App\Models\User;
use App\Services\Logger\LoggerService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PqaLeadAllocationService
{
    /**
     * Create a PQA allocation row when a user is eligible for a quote type (idempotent).
     */
    public function syncPqaAllocationConfig(int $userId, object $data): bool
    {

        if (isset($data->quoteTypeId) && ! empty($data->quoteTypeId) && $data->quoteTypeId == QuoteTypes::BUSINESS->id()) {
            $existing = PqaLeadAllocationConfig::query()
                ->where('user_id', $userId)
                ->where('quote_type_id', $data->quoteTypeId)
                ->first();

            if ($existing) {
                LoggerService::info('PQA allocation config already exists for user: '.$userId.' and quote type: '.$data->quoteTypeId);

                return true;
            }

            PqaLeadAllocationConfig::query()->create([
                'user_id' => $userId,
                'quote_type_id' => $data->quoteTypeId,
                'max_capacity' => 100,
                'allocation_count' => 0,
                'auto_assignment_count' => 0,
                'manual_assignment_count' => 0,
                'last_allocated' => null,
                'reset_cap' => 0,
            ]);

            return true;
        }

        LoggerService::warning('Quote type id is not set for PQA allocation sync on user: '.$userId);

        return false;
    }

    /**
     * Atomically increment allocation counters after a successful PQA assignment.
     */
    public function updatePqaAllocationConfig(int $userId, int $quoteTypeId): bool
    {
        $quoteTypeId = in_array($quoteTypeId, [QuoteTypeId::GroupMedical, QuoteTypeId::Corpline]) ? QuoteTypeId::Business : $quoteTypeId;
        $updated = PqaLeadAllocationConfig::query()
            ->where('user_id', $userId)
            ->where('quote_type_id', $quoteTypeId)
            ->update([
                'allocation_count' => DB::raw('allocation_count + 1'),
                'auto_assignment_count' => DB::raw('auto_assignment_count + 1'),
                'last_allocated' => now()->timestamp,
                'updated_at' => now(),
            ]);

        return $updated > 0;
    }

    /**
     * Update ILA counters when a Team Lead manually assigns or reassigns PQA on a lead.
     */
    public function recordManualPqaAssignment(int $newUserId, int $quoteTypeId, ?int $previousUserId): void
    {
        if ($previousUserId !== null && $previousUserId === $newUserId) {
            return;
        }

        DB::transaction(function () use ($newUserId, $quoteTypeId, $previousUserId) {
            if ($previousUserId !== null) {
                $this->adjustCountsAfterPqaReassignment($previousUserId, $quoteTypeId);
            }

            $this->incrementManualPqaAssignmentCount($newUserId, $quoteTypeId);
        });
    }

    public function incrementManualPqaAssignmentCount(int $userId, int $quoteTypeId): void
    {
        $updated = PqaLeadAllocationConfig::query()
            ->where('user_id', $userId)
            ->where('quote_type_id', $quoteTypeId)
            ->update([
                'allocation_count' => DB::raw('allocation_count + 1'),
                'manual_assignment_count' => DB::raw('manual_assignment_count + 1'),
                'last_allocated' => now()->timestamp,
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            LoggerService::warning('PQA manual assignment: no allocation config row for user '.$userId.' quote_type_id '.$quoteTypeId);
        }
    }

    public function adjustCountsAfterPqaReassignment(int $previousUserId, int $quoteTypeId): void
    {
        $config = PqaLeadAllocationConfig::query()
            ->where('user_id', $previousUserId)
            ->where('quote_type_id', $quoteTypeId)
            ->first();

        if ($config === null || $config->allocation_count <= 0) {
            return;
        }

        $config->allocation_count = max(0, $config->allocation_count - 1);

        if ($config->manual_assignment_count > 0) {
            $config->manual_assignment_count--;
        } elseif ($config->auto_assignment_count > 0) {
            $config->auto_assignment_count--;
        }

        $config->save();
    }

    public function userIsEligiblePreQualificationAdvisor(int $userId, int $quoteTypeId): bool
    {
        $user = User::query()->find($userId);

        if ($user === null || ! $user->hasRole(RolesEnum::PreQualificationAdvisor)) {
            return false;
        }

        return PqaLeadAllocationConfig::query()
            ->where('user_id', $userId)
            ->where('quote_type_id', $quoteTypeId)
            ->exists();
    }

    /**
     * @param  array<string, mixed>|object  $request
     */
    public function updateAvailability(mixed $request): void
    {
        $items = is_array($request) ? $request : $request->all();

        if (empty($items)) {
            return;
        }

        $userIds = collect($items)->pluck('userId')->unique()->toArray();
        $users = User::query()->whereIn('id', $userIds)->get()->keyBy('id');
        $configs = $this->getConfigs($items);

        foreach ($items as $item) {
            $configKey = $item['userId'].'-'.$item['id'];
            $config = $configs->get($configKey);

            if (! $config) {
                continue;
            }

            if (array_key_exists('reason', $item)) {
                $reason = $item['reason'];
                if ($reason !== UserStatusEnum::OFFLINE && $reason !== UserStatusEnum::ONLINE) {
                    LoggerService::info('PQA user status is going to change to: '.UserStatusEnum::getUserStatusText($reason));
                }

                $user = $users->get($item['userId']);
                if ($user) {
                    $user->status = $reason;
                    LoggerService::info('PQA user status updated on id: '.$user->id.' status: '.$user->status);
                    $user->save();
                }
            }

            $config->save();
        }
    }

    /**
     * @param  array<string, mixed>|object  $request
     */
    public function updateCaps(mixed $request): void
    {
        $items = is_array($request) ? $request : $request->all();
        if (empty($items)) {
            return;
        }

        $configs = $this->getConfigs($items);

        foreach ($items as $item) {
            $configKey = $item['userId'].'-'.$item['id'];
            $config = $configs->get($configKey);
            if (! $config) {
                continue;
            }
            $config->max_capacity = (int) $item['maxCap'];
            $config->save();
        }
    }

    /**
     * @param  array<string, mixed>|object  $request
     */
    public function resetCap(mixed $request): void
    {
        $items = is_array($request) ? $request : $request->all();
        $configs = $this->getConfigs($items);

        foreach ($items as $item) {
            $configKey = $item['userId'].'-'.$item['id'];
            $config = $configs->get($configKey);
            if (! $config) {
                continue;
            }
            $config->reset_cap = (int) $item['resetCap'];
            $config->save();
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return Collection<string, PqaLeadAllocationConfig>
     */
    public function getConfigs(array $items): Collection
    {
        $userIds = collect($items)->pluck('userId')->unique()->toArray();
        $configIds = collect($items)->pluck('id')->unique()->toArray();

        return PqaLeadAllocationConfig::query()
            ->whereIn('user_id', $userIds)
            ->whereIn('id', $configIds)
            ->get()
            ->keyBy(fn ($item) => $item->user_id.'-'.$item->id);
    }
}
