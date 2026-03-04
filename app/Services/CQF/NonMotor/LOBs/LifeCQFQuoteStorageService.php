<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\QuoteTypeId;
use App\Models\LifeQuote;
use App\Models\LifeQuoteRequestDetail;
use App\Models\PersonalQuote;
use App\Services\CQF\NonMotor\BaseCQFQuoteStorageService;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Eloquent\Model;

class LifeCQFQuoteStorageService extends BaseCQFQuoteStorageService
{
    public function __construct(
        LifeCQFQuoteMappingService $mappingService
    ) {
        parent::__construct($mappingService);
    }

    protected function getLobName(): string
    {
        return 'life';
    }

    protected function getQuoteTypeId(): int
    {
        return QuoteTypeId::Life;
    }

    protected function copyLobQuoteDetail(PersonalQuote $newQuote, Model $oldQuote): void
    {
        if (! $oldQuote instanceof PersonalQuote) {
            return;
        }
        $this->copyLifeQuoteDetail($newQuote, $oldQuote);
    }

    protected function copyLifeQuoteDetail(PersonalQuote $newQuote, PersonalQuote $oldQuote): void
    {
        $oldLifeQuote = $oldQuote->lifeQuote;

        if ($oldLifeQuote === null) {
            LoggerService::info(self::class.' - No life quote detail found for old quote');

            return;
        }

        $data = $this->copyableAttributes($oldLifeQuote->getAttributes(), $newQuote->id, $newQuote->uuid, $newQuote->code);
        $newLifeQuote = LifeQuote::create($data);

        if ($oldLifeQuote->lifeQuoteRequestDetail) {
            $detailAttrs = $oldLifeQuote->lifeQuoteRequestDetail->getAttributes();
            unset($detailAttrs['id'], $detailAttrs['life_quote_request_id'], $detailAttrs['created_at'], $detailAttrs['updated_at']);
            $detailAttrs['life_quote_request_id'] = $newLifeQuote->id;
            LifeQuoteRequestDetail::create($detailAttrs);
        }

        LoggerService::info(self::class.' - Life quote detail copied for renewal quote');
    }

    /**
     * Life quote table also has quote_id; unset it when copying.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function copyableAttributes(array $attributes, int $personalQuoteId, string $newQuoteUuid, string $newQuoteCode): array
    {
        $attributes = parent::copyableAttributes($attributes, $personalQuoteId, $newQuoteUuid, $newQuoteCode);
        unset($attributes['quote_id']);

        return $attributes;
    }
}
