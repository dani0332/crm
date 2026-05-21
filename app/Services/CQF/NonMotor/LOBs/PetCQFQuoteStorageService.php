<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\LookupsEnum;
use App\Enums\QuoteTypeId;
use App\Models\Lookup;
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

    protected function getQuoteTypeId(): int
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
        $data = $this->alignCopiedLobRowWithRenewalPersonalQuote($data, $newQuote, $oldPetQuote);
        $data['pet_age_id'] = $this->incrementPetAgeId($oldPetQuote->pet_age_id);

        $newPetQuote = PetQuote::create($data);

        if ($oldPetQuote->petQuoteRequestDetail) {
            PetQuoteRequestDetail::create(['pet_quote_request_id' => $newPetQuote->id]);
        }

        LoggerService::info(self::class.' - Pet quote detail copied for renewal quote');
    }

    /**
     * Return the next pet-age lookup ID (one step up the ordered list).
     * Caps at the oldest entry ("10 Year old") — returns current ID when already at the top.
     */
    private function incrementPetAgeId(?int $currentId): ?int
    {
        if ($currentId === null) {
            return null;
        }

        $nextId = Lookup::where('key', LookupsEnum::PET_AGES->value)
            ->where('is_active', 1)
            ->where('id', '>', $currentId)
            ->orderBy('id')
            ->value('id');

        return $nextId ?? $currentId;
    }
}
