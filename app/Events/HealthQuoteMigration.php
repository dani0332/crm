<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class HealthQuoteMigration
{
    use Dispatchable;

    public function __construct(
        public int $healthQuoteId
    ) {}
}
