<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\QuoteTypeId;
use App\Models\HomeQuote;
use App\Models\HomeQuoteRequestDetail;
use App\Models\PersonalQuote;
use App\Repositories\EmbeddedProductRepository;
use App\Services\CQF\NonMotor\BaseCQFQuoteStorageService;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Eloquent\Model;

class HomeCQFQuoteStorageService extends BaseCQFQuoteStorageService
{
    public function __construct(
        HomeCQFQuoteMappingService $mappingService,
        EmbeddedProductRepository $embeddedProductRepository
    ) {
        parent::__construct($mappingService, $embeddedProductRepository);
    }

    protected function getLobName(): string
    {
        return 'home';
    }

    protected function getQuoteTypeId(): int
    {
        return QuoteTypeId::Home;
    }

    protected function copyLobQuoteDetail(PersonalQuote $newQuote, Model $oldQuote): void
    {
        if (! $oldQuote instanceof PersonalQuote) {
            return;
        }
        $this->copyHomeQuoteDetail($newQuote, $oldQuote);
    }

    protected function copyHomeQuoteDetail(PersonalQuote $newQuote, PersonalQuote $oldQuote): void
    {
        $oldHomeQuote = $oldQuote->homeQuote;

        if ($oldHomeQuote === null) {
            LoggerService::info(self::class.' - No home quote detail found for old quote');

            return;
        }

        $data = $this->copyableAttributes($oldHomeQuote->getAttributes(), $newQuote->id, $newQuote->uuid, $newQuote->code);
        $data = $this->alignCopiedLobRowWithRenewalPersonalQuote($data, $newQuote, $oldHomeQuote);
        $data['claim_history_id'] = null;
        $data['location_area'] = null;
        $newHomeQuote = HomeQuote::create($data);

        if ($oldHomeQuote->homeQuoteRequestDetail) {
            HomeQuoteRequestDetail::create(['home_quote_request_id' => $newHomeQuote->id]);
        }

        LoggerService::info(self::class.' - Home quote detail copied for renewal quote');
    }
}
