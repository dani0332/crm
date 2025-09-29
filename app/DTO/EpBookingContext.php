<?php

namespace App\DTO;

use Illuminate\Contracts\Support\Arrayable;

class EpBookingContext implements Arrayable
{
    public int $etId;
    public string $quoteId;
    public int $quoteTypeId;
    public string $quoteUUID;

    public array $logExtra;

    public function __construct(int $etId, string $quoteId, int $quoteTypeId, string $quoteUUID)
    {
        $this->etId = $etId;
        $this->quoteId = $quoteId;
        $this->quoteTypeId = $quoteTypeId;
        $this->quoteUUID = $quoteUUID;

        $this->logExtra = [
            'etId' => $etId,
            'quoteId' => $quoteId,
            'quoteTypeId' => $quoteTypeId,
            'quoteUUID' => $quoteUUID,
        ];
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
