<?php

namespace App\Listeners;

use App\Events\HealthQuoteMigration;
use App\Services\HealthQuoteRevampMigrationService;

class RunHealthQuoteRevampMigration
{
    public function __construct(
        private HealthQuoteRevampMigrationService $healthQuoteRevampMigrationService
    ) {}

    public function handle(HealthQuoteMigration $event): void
    {
        $this->healthQuoteRevampMigrationService->migrateLead($event->healthQuoteId);
    }
}
