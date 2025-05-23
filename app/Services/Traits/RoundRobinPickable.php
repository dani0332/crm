<?php

namespace App\Services\Traits;

use App\Enums\QuoteTypes;
use App\Models\User;
use App\Services\Logger\LoggerService;

trait RoundRobinPickable
{
    protected array $advisorIds = [];
    protected int $advisorIndex = 0;

    protected function initRoundRobinAdvisors(QuoteTypes $quoteType, array $roles, $teamId): void
    {
        $this->advisorIds = User::query()
            ->join('lead_allocation as la', 'la.user_id', '=', 'users.id')
            ->join('model_has_roles as mhr', 'mhr.model_id', '=', 'users.id')
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->when($teamId, function ($q) use ($teamId) {
                $q->whereIn('users.id', fn ($query) => $query->select('user_id')->from('user_team')->where('team_id', $teamId));
            })
            ->whereIn('r.name', $roles)
            ->where('la.quote_type_id', $quoteType->id())
            ->activeUser()
            ->orderBy('users.id')
            ->pluck('users.id')
            ->values()
            ->toArray();

        LoggerService::info(self::class.'::initRoundRobinAdvisors', [
            'advisorIds' => $this->advisorIds,
            'count' => count($this->advisorIds),
        ]);

        $this->advisorIndex = 0;
    }

    protected function getNextRoundRobinAdvisorId(): ?int
    {
        $count = count($this->advisorIds);
        if ($count === 0) {
            return null;
        }

        $advisorId = $this->advisorIds[$this->advisorIndex];
        $this->advisorIndex = ($this->advisorIndex + 1) % $count;

        LoggerService::info(self::class.'::getNextRoundRobinAdvisorId', [
            'advisorId' => $advisorId,
            'advisorIndex' => $this->advisorIndex,
        ]);

        return $advisorId;
    }
}
