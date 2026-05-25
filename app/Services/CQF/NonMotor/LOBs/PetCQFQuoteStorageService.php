<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\LookupsEnum;
use App\Enums\QuoteTypeId;
use App\Models\Lookup;
use App\Models\PersonalQuote;
use App\Models\PetQuote;
use App\Models\PetQuoteRequestDetail;
use App\Repositories\EmbeddedProductRepository;
use App\Services\CQF\NonMotor\BaseCQFQuoteStorageService;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Eloquent\Model;

class PetCQFQuoteStorageService extends BaseCQFQuoteStorageService
{
    public function __construct(
        PetCQFQuoteMappingService $mappingService,
        EmbeddedProductRepository $embeddedProductRepository
    ) {
        parent::__construct($mappingService, $embeddedProductRepository);
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

        $newPetQuote = PetQuote::create($this->mapLobRenewalDetail($oldPetQuote, $newQuote));

        if ($oldPetQuote->petQuoteRequestDetail) {
            PetQuoteRequestDetail::create(['pet_quote_request_id' => $newPetQuote->id]);
        }

        LoggerService::info(self::class.' - Pet quote detail copied for renewal quote');
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapLobRenewalDetail(PetQuote $oldLob, PersonalQuote $newQuote): array
    {
        return [
            'personal_quote_id' => $newQuote->id,
            'uuid' => $newQuote->uuid,
            'code' => $newQuote->code,
            'source' => $newQuote->source,
            'quote_status_id' => $newQuote->quote_status_id,
            'advisor_id' => $newQuote->advisor_id,
            'renewal_batch_id' => $newQuote->renewal_batch_id,
            'previous_quote_policy_number' => $newQuote->previous_quote_policy_number,
            'previous_quote_policy_premium' => $newQuote->previous_quote_policy_premium,
            'previous_quote_policy_commission' => $newQuote->previous_quote_policy_commission,
            'previous_policy_start_date' => $this->formatPolicyDate($newQuote->previous_policy_start_date),
            'previous_policy_expiry_date' => $this->formatPolicyDate($newQuote->previous_policy_expiry_date),
            'previous_quote_id' => $oldLob->id,
            'first_name' => $oldLob->first_name,
            'last_name' => $oldLob->last_name,
            'email' => $oldLob->email,
            'mobile_no' => $oldLob->mobile_no,
            'gender' => $oldLob->gender,
            'dob' => $oldLob->dob,
            'lang' => $oldLob->lang,
            'customer_id' => $oldLob->customer_id,
            'nationality_id' => $oldLob->nationality_id,
            'no_of_pets_to_insure' => $oldLob->no_of_pets_to_insure,
            'type_of_pet1' => $oldLob->type_of_pet1,
            'breed_of_pet1' => $oldLob->breed_of_pet1,
            'ilivein_accommodation_type_id' => $oldLob->ilivein_accommodation_type_id,
            'iam_possesion_type_id' => $oldLob->iam_possesion_type_id,
            'is_microchipped' => $oldLob->is_microchipped,
            'microchip_no' => $oldLob->microchip_no,
            'is_neutered' => $oldLob->is_neutered,
            'is_mixed_breed' => $oldLob->is_mixed_breed,
            'has_injury' => $oldLob->has_injury,
            'pet_type_id' => $oldLob->pet_type_id,
            'pet_age_id' => $this->incrementPetAgeId($oldLob->pet_age_id),
        ];
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
