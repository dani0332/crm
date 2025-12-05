<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Health\Adnic;

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
