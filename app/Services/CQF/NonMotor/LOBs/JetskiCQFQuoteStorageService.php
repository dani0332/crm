<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\QuoteTypeId;
use App\Models\JetskiQuote;
use App\Models\PersonalQuote;
use App\Services\CQF\NonMotor\BaseCQFQuoteStorageService;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Eloquent\Model;

class JetskiCQFQuoteStorageService extends BaseCQFQuoteStorageService
{
    public function __construct(
        JetskiCQFQuoteMappingService $mappingService
    ) {
        parent::__construct($mappingService);
    }

    protected function getLobName(): string
    {
        return 'jetski';
    }

    protected function getQuoteTypeId(): int
    {
        return QuoteTypeId::Jetski;
    }

    protected function copyLobQuoteDetail(PersonalQuote $newQuote, Model $oldQuote): void
    {
        if (! $oldQuote instanceof PersonalQuote) {
            return;
        }
        $this->copyJetskiQuoteDetail($newQuote, $oldQuote);
    }

    protected function copyJetskiQuoteDetail(PersonalQuote $newQuote, PersonalQuote $oldQuote): void
    {
        $oldJetskiQuote = $oldQuote->jetskiQuote;

        if ($oldJetskiQuote === null) {
            LoggerService::info(self::class.' - No jetski quote detail found for old quote');

            return;
        }

        $data = $this->copyableAttributes($oldJetskiQuote->getAttributes(), $newQuote->id, $newQuote->uuid, $newQuote->code);
        $data = $this->alignCopiedLobRowWithRenewalPersonalQuote($data, $newQuote, $oldJetskiQuote);
        JetskiQuote::create($data);

        LoggerService::info(self::class.' - Jetski quote detail copied for renewal quote');
    }
}
