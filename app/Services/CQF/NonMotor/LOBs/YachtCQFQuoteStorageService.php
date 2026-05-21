<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\QuoteTypeId;
use App\Models\PersonalQuote;
use App\Models\YachtQuote;
use App\Models\YachtQuoteRequestDetail;
use App\Repositories\EmbeddedProductRepository;
use App\Services\CQF\NonMotor\BaseCQFQuoteStorageService;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Eloquent\Model;

class YachtCQFQuoteStorageService extends BaseCQFQuoteStorageService
{
    public function __construct(
        YachtCQFQuoteMappingService $mappingService,
        EmbeddedProductRepository $embeddedProductRepository
    ) {
        parent::__construct($mappingService, $embeddedProductRepository);
    }

    protected function getLobName(): string
    {
        return 'yacht';
    }

    protected function getQuoteTypeId(): int
    {
        return QuoteTypeId::Yacht;
    }

    protected function copyLobQuoteDetail(PersonalQuote $newQuote, Model $oldQuote): void
    {
        if (! $oldQuote instanceof PersonalQuote) {
            return;
        }
        $this->copyYachtQuoteDetail($newQuote, $oldQuote);
    }

    protected function copyYachtQuoteDetail(PersonalQuote $newQuote, PersonalQuote $oldQuote): void
    {
        $oldYachtQuote = $oldQuote->yachtQuote;

        if ($oldYachtQuote === null) {
            LoggerService::info(self::class.' - No yacht quote detail found for old quote');

            return;
        }

        $data = $this->copyableAttributes($oldYachtQuote->getAttributes(), $newQuote->id, $newQuote->uuid, $newQuote->code);
        $data = $this->alignCopiedLobRowWithRenewalPersonalQuote($data, $newQuote, $oldYachtQuote);
        $newYachtQuote = YachtQuote::create($data);

        if ($oldYachtQuote->yachtQuoteRequestDetail) {
            YachtQuoteRequestDetail::create(['yacht_quote_request_id' => $newYachtQuote->id]);
        }

        LoggerService::info(self::class.' - Yacht quote detail copied for renewal quote');
    }
}
