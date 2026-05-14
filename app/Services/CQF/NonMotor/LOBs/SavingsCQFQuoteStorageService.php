<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\QuoteTypeId;
use App\Models\PersonalQuote;
use App\Models\SavingsQuote;
use App\Services\CQF\NonMotor\BaseCQFQuoteStorageService;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Eloquent\Model;

class SavingsCQFQuoteStorageService extends BaseCQFQuoteStorageService
{
    public function __construct(
        SavingsCQFQuoteMappingService $mappingService
    ) {
        parent::__construct($mappingService);
    }

    protected function getLobName(): string
    {
        return 'savings';
    }

    protected function getQuoteTypeId(): int
    {
        return QuoteTypeId::Savings;
    }

    protected function copyLobQuoteDetail(PersonalQuote $newQuote, Model $oldQuote): void
    {
        if (! $oldQuote instanceof PersonalQuote) {
            return;
        }
        $this->copySavingsQuoteDetail($newQuote, $oldQuote);
    }

    protected function copySavingsQuoteDetail(PersonalQuote $newQuote, PersonalQuote $oldQuote): void
    {
        $oldSavingsQuote = $oldQuote->savingsQuote;

        if ($oldSavingsQuote === null) {
            LoggerService::info(self::class.' - No savings quote detail found for old quote');

            return;
        }

        $data = $this->copyableAttributes($oldSavingsQuote->getAttributes(), $newQuote->id, $newQuote->uuid, $newQuote->code);
        $data = $this->alignCopiedLobRowWithRenewalPersonalQuote($data, $newQuote, $oldSavingsQuote);
        SavingsQuote::create($data);

        LoggerService::info(self::class.' - Savings quote detail copied for renewal quote');
    }
}
