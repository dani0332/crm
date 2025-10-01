<?php

namespace App\DTO;

use Illuminate\Contracts\Support\Arrayable;

class EpBookingContext implements Arrayable
{
    public int $etId;
    public string $quoteId;
    public int $quoteTypeId;
    public string $quoteCode;

    public array $logExtra;

    public function __construct(int $etId, string $quoteId, int $quoteTypeId, string $quoteCode)
    {
        $this->etId = $etId;
        $this->quoteId = $quoteId;
        $this->quoteTypeId = $quoteTypeId;
        $this->quoteCode = $quoteCode;

        $this->logExtra = [
            'etId' => $etId,
            'quoteId' => $quoteId,
            'quoteTypeId' => $quoteTypeId,
            'quoteCode' => $quoteCode
        ];
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
