<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\QuoteTypeId;
use App\Models\CycleQuote;
use App\Models\PersonalQuote;
use App\Repositories\EmbeddedProductRepository;
use App\Services\CQF\NonMotor\BaseCQFQuoteStorageService;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Eloquent\Model;

class CycleCQFQuoteStorageService extends BaseCQFQuoteStorageService
{
    public function __construct(
        CycleCQFQuoteMappingService $mappingService,
        EmbeddedProductRepository $embeddedProductRepository
    ) {
        parent::__construct($mappingService, $embeddedProductRepository);
    }

    protected function getLobName(): string
    {
        return 'cycle';
    }

    protected function getQuoteTypeId(): int
    {
        return QuoteTypeId::Cycle;
    }

    protected function copyLobQuoteDetail(PersonalQuote $newQuote, Model $oldQuote): void
    {
        if (! $oldQuote instanceof PersonalQuote) {
            return;
        }
        $this->copyCycleQuoteDetail($newQuote, $oldQuote);
    }

    protected function copyCycleQuoteDetail(PersonalQuote $newQuote, PersonalQuote $oldQuote): void
    {
        $oldCycleQuote = $oldQuote->cycleQuote;

        if ($oldCycleQuote === null) {
            LoggerService::info(self::class.' - No cycle quote detail found for old quote');

            return;
        }

        $data = $this->copyableAttributes($oldCycleQuote->getAttributes(), $newQuote->id, $newQuote->uuid, $newQuote->code);
        $data = $this->alignCopiedLobRowWithRenewalPersonalQuote($data, $newQuote, $oldCycleQuote);
        CycleQuote::create($data);

        LoggerService::info(self::class.' - Cycle quote detail copied for renewal quote');
    }
}
