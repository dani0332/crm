<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\QuoteTypes;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AmlAutomationScreeningSucceeded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $quoteRequestId,
        public string $quoteUuid,
        public string $quoteCode,
        public QuoteTypes $quoteType,
    ) {}
}
