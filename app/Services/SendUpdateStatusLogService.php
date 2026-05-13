<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SendUpdateLog;
use App\Models\SendUpdateStatusLog;
use App\Support\StatusChangeRequestNotes;
use BackedEnum;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;

class SendUpdateStatusLogService extends BaseService
{
    /**
     * @return EloquentCollection<int, SendUpdateStatusLog>
     */
    public function getSendUpdateStatusLogs(int $sendUpdateLogId): EloquentCollection
    {
        return SendUpdateStatusLog::query()
            ->where('send_update_log_id', $sendUpdateLogId)
            ->with(['createdBy'])
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Persist a single send-update status transition (append-only audit row).
     */
    public function createSendUpdateStatusLog(
        SendUpdateLog $sendUpdateLog,
        string|BackedEnum|null $previousStatus,
        string|BackedEnum $currentStatus,
    ): SendUpdateStatusLog {
        return SendUpdateStatusLog::query()->create([
            'send_update_log_id' => $sendUpdateLog->getKey(),
            'previous_status' => $this->normalizeStatusSegment($previousStatus),
            'current_status' => $this->normalizeStatusSegment($currentStatus),
            'created_by' => Auth::id(),
            'notes' => StatusChangeRequestNotes::toJson(request()),
        ]);
    }

    protected function normalizeStatusSegment(string|BackedEnum|null $value): string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        return (string) ($value ?? '');
    }

}
