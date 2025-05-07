<?php

declare(strict_types=1);

namespace App\Services\DTOs;

use App\Models\CarQuote;

class FetchCarLeadResult
{
    public function __construct(
        public readonly ?CarQuote $lead,
        public readonly bool $notFound = false
    ) {}
}
