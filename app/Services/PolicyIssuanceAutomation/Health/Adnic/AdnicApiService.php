<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Health\Adnic;

use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicRequestBuilder;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicResponseHandler;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicDocumentHandler;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicQuoteUpdaterService;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicValidationService;

class AdnicApiService
{
    public function __construct(
        private AdnicRequestBuilder $requestBuilder,
        private AdnicResponseHandler $responseHandler,
        private AdnicDocumentHandler $documentHandler,
        private AdnicQuoteUpdaterService $quoteUpdater,
        private AdnicValidationService $validationService,
    ) {}

}
