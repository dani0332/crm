<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\QuoteTypeId;
use App\Models\PersonalQuote;
use App\Models\PetQuote;
use App\Models\PetQuoteRequestDetail;
use App\Services\CQF\NonMotor\BaseCQFQuoteStorageService;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Eloquent\Model;

class PetCQFQuoteStorageService extends BaseCQFQuoteStorageService
{
    public function __construct(
        PetCQFQuoteMappingService $mappingService
    ) {
        parent::__construct($mappingService);
    }

    protected function getLobName(): string
    {
        return 'pet';
    }

    protected function getQuoteTypeId(): QuoteTypeId
    {
        return QuoteTypeId::Pet;
    }

    protected function copyLobQuoteDetail(PersonalQuote $newQuote, Model $oldQuote): void
    {
        if (! $oldQuote instanceof PersonalQuote) {
            return;
        }
        $this->copyPetQuoteDetail($newQuote, $oldQuote);
    }

    protected function copyPetQuoteDetail(PersonalQuote $newQuote, PersonalQuote $oldQuote): void
    {
        $oldPetQuote = $oldQuote->petQuote;
        if ($oldPetQuote === null) {
            LoggerService::info(self::class.' - No pet quote detail found for old quote');

            return;
        }

        $data = $this->copyableAttributes($oldPetQuote->getAttributes(), $newQuote->id, $newQuote->uuid, $newQuote->code);
        $newPetQuote = PetQuote::create($data);

        if ($oldPetQuote->petQuoteRequestDetail) {
            $detailAttrs = $oldPetQuote->petQuoteRequestDetail->getAttributes();
            unset($detailAttrs['id'], $detailAttrs['pet_quote_request_id'], $detailAttrs['created_at'], $detailAttrs['updated_at']);
            $detailAttrs['pet_quote_request_id'] = $newPetQuote->id;
            PetQuoteRequestDetail::create($detailAttrs);
        }

        LoggerService::info(self::class.' - Pet quote detail copied for renewal quote');
    }
}
