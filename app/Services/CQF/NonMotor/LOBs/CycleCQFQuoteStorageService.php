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

        CycleQuote::create($this->mapLobRenewalDetail($oldCycleQuote, $newQuote));

        LoggerService::info(self::class.' - Cycle quote detail copied for renewal quote');
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapLobRenewalDetail(CycleQuote $oldLob, PersonalQuote $newQuote): array
    {
        return [
            'personal_quote_id' => $newQuote->id,
            'quote_status_id' => $newQuote->quote_status_id,
            'transaction_approved_at' => $newQuote->transaction_approved_at,
            'cycle_make' => $oldLob->cycle_make,
            'cycle_model' => $oldLob->cycle_model,
            'year_of_manufacture_id' => $oldLob->year_of_manufacture_id,
            'accessories' => $oldLob->accessories,
            'has_accident' => $oldLob->has_accident,
            'has_good_condition' => $oldLob->has_good_condition,
        ];
    }
}
