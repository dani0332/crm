<?php

namespace App\Models;

use Carbon\Carbon;

class CommunicationEventLog extends BaseMongoModel
{
    protected $table = 'communication_event_logs';
    protected $guarded = [];
    protected $casts = [
        'quoteTypeId' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function toArray(): array
    {
        return [
            'id' => (string) ($this->getKey() ?? ''),
            'quote_uuid' => $this->quoteUuid,
            'quote_type_id' => $this->quoteTypeId,
            'event_channel' => $this->eventChannel,
            'communication_type' => $this->communicationType,
            'action_event' => $this->actionEvent,
            'created_at' => $this->formatDisplayDatetime($this->created_at),
            'updated_at' => $this->formatDisplayDatetime($this->updated_at),
        ];
    }

    private function formatDisplayDatetime(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return Carbon::parse($value)->timezone(config('app.timezone'))->format(config('constants.DATETIME_DISPLAY_FORMAT'));
    }
}
