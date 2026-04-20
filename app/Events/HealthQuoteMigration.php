<?php

namespace App\Events;

use App\Models\HealthQuote;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HealthQuoteMigration
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public HealthQuote $healthQuote
    ) {}
}
