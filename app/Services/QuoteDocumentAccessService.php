<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\QuoteDocumentController;
use App\Models\BikeQuote;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\CycleQuote;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\JetskiQuote;
use App\Models\LifeQuote;
use App\Models\PersonalQuote;
use App\Models\PetQuote;
use App\Models\SavingsQuote;
use App\Models\SendUpdateLog;
use App\Models\TravelQuote;
use App\Models\User;
use App\Models\YachtQuote;
use Illuminate\Database\Eloquent\Model;

class QuoteDocumentAccessService
{
    public function __construct(
        private readonly SendUpdateLogService $sendUpdateLogService,
    ) {}

    /**
     * @param  Model  $quoteDocumentable  Quote model, {@see PersonalQuote}, {@see BusinessQuote}, or {@see SendUpdateLog} (morph target for send-update documents).
     * @param  bool  $forQuoteDocumentDestroy  When true (e.g. {@see QuoteDocumentController::destroy()}), users with {@see PermissionsEnum::DOCUMENT_DELETE} are allowed without LOB manager/advisor checks.
     * @param  bool  $forAdditionalContact  When true (e.g. {@see CustomerController::addAdditionalContact()}), users with {@see PermissionsEnum::ADD_ADDITIONAL_CONTACT} are allowed without LOB manager/advisor checks.
     */
    public function userCanAccessQuoteDocumentable(?User $user, Model $quoteDocumentable, bool $forQuoteDocumentDestroy = false, bool $forAdditionalContact = false): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->hasRole(RolesEnum::Admin) || $user->hasRole(RolesEnum::Engineering)) {
            return true;
        }

        if ($forQuoteDocumentDestroy && $user->can(PermissionsEnum::DOCUMENT_DELETE)) {
            return true;
        }

        if ($forAdditionalContact && $user->can(PermissionsEnum::ADD_ADDITIONAL_CONTACT)) {
            return true;
        }

        $quoteType = $this->resolveQuoteTypeFromDocumentable($quoteDocumentable);
        if (! $quoteType instanceof QuoteTypes) {
            return false;
        }

        $advisorId = $this->resolveAdvisorIdFromDocumentable($quoteDocumentable);

        return $this->userPassesLobManagerOrAssignedAdvisor(
            $user,
            $advisorId,
            $this->managerRolesForQuoteType($quoteType),
            $this->advisorRolesForQuoteType($quoteType),
        );
    }

    private function resolveQuoteTypeFromDocumentable(Model $documentable): ?QuoteTypes
    {
        if ($documentable instanceof SendUpdateLog) {
            if ($documentable->quote_type_id === null) {
                return null;
            }

            return QuoteTypes::getName((int) $documentable->quote_type_id);
        }

        if ($documentable instanceof PersonalQuote) {
            if ($documentable->quote_type_id === null) {
                return null;
            }

            return QuoteTypes::getName((int) $documentable->quote_type_id);
        }

        if ($documentable instanceof BusinessQuote) {
            return QuoteTypes::BUSINESS->isGroupMedical($documentable)
                ? QuoteTypes::GROUP_MEDICAL
                : QuoteTypes::BUSINESS;
        }

        return match (true) {
            $documentable instanceof CarQuote => QuoteTypes::CAR,
            $documentable instanceof HealthQuote => QuoteTypes::HEALTH,
            $documentable instanceof HomeQuote => QuoteTypes::HOME,
            $documentable instanceof LifeQuote => QuoteTypes::LIFE,
            $documentable instanceof TravelQuote => QuoteTypes::TRAVEL,
            $documentable instanceof PetQuote => QuoteTypes::PET,
            $documentable instanceof BikeQuote => QuoteTypes::BIKE,
            $documentable instanceof YachtQuote => QuoteTypes::YACHT,
            $documentable instanceof CycleQuote => QuoteTypes::CYCLE,
            $documentable instanceof JetskiQuote => QuoteTypes::JETSKI,
            $documentable instanceof SavingsQuote => QuoteTypes::SAVINGS,
            default => null,
        };
    }

    private function resolveAdvisorIdFromDocumentable(Model $documentable): ?int
    {
        if ($documentable instanceof SendUpdateLog) {
            $documentable->loadMissing('personalQuote');
            $advisorId = $documentable->personalQuote?->advisor_id;

            if ($advisorId !== null) {
                return (int) $advisorId;
            }

            if ($documentable->quote_uuid === null || $documentable->quote_type_id === null) {
                return null;
            }

            $quoteType = QuoteTypes::getName((int) $documentable->quote_type_id);

            if (! $quoteType instanceof QuoteTypes) {
                return null;
            }

            $linkedQuote = $this->sendUpdateLogService->getQuoteObjectBy(
                $this->quoteTypeLabelForGetQuoteObjectBy($quoteType),
                $documentable->quote_uuid,
                'uuid',
            );

            if ($linkedQuote instanceof Model && ! $linkedQuote instanceof SendUpdateLog) {
                return $this->resolveAdvisorIdFromDocumentable($linkedQuote);
            }

            return null;
        }

        try {
            if (isset($documentable->advisor_id) && $documentable->advisor_id !== null) {
                return (int) $documentable->advisor_id;
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    /**
     * {@see GenericQueriesAllLobs::getQuoteObjectBy()} loads {@see BusinessQuote} when the type string maps to the Business quote model.
     * Corp-line and group-medical send-update logs use {@see QuoteTypes::CORPLINE} / {@see QuoteTypes::GROUP_MEDICAL}; their enum values
     * would otherwise produce invalid `*Quote` class names and break linked-quote resolution.
     */
    private function quoteTypeLabelForGetQuoteObjectBy(QuoteTypes $quoteType): string
    {
        return match ($quoteType) {
            QuoteTypes::CORPLINE, QuoteTypes::GROUP_MEDICAL => QuoteTypes::BUSINESS->value,
            default => $quoteType->value,
        };
    }

    /**
     * LOB-specific access after {@see resolveQuoteTypeFromDocumentable()} and {@see resolveAdvisorIdFromDocumentable()}.
     *
     * Example: documentable is {@see CarQuote} → quote type CAR → {@see managerRolesForQuoteType()} (car managers)
     * and {@see advisorRolesForQuoteType()} (car-related advisor roles). Access when either:
     * - the user has any of those manager roles (assigned advisor id is ignored), or
     * - the user has any of those advisor roles and {@see $advisorId} equals the user id.
     *
     * @param  array<int, string>  $managerRoles
     * @param  array<int, string>  $advisorRoles
     */
    private function userPassesLobManagerOrAssignedAdvisor(User $user, ?int $advisorId, array $managerRoles, array $advisorRoles): bool
    {
        if ($user->hasAnyRole($managerRoles)) {
            return true;
        }

        if ($advisorId === null) {
            return false;
        }

        if (! $user->hasAnyRole($advisorRoles)) {
            return false;
        }

        return (int) $advisorId === (int) $user->id;
    }

    /**
     * @return array<int, string>
     */
    private function managerRolesForQuoteType(QuoteTypes $quoteType): array
    {
        return match ($quoteType) {
            QuoteTypes::CAR => [
                RolesEnum::CarManager,
                RolesEnum::CarRenewalManager,
                RolesEnum::CarRevivalManager,
            ],
            QuoteTypes::HEALTH => [
                RolesEnum::HealthManager,
                RolesEnum::HealthDeputyManager,
                RolesEnum::HealthRenewalManager,
                RolesEnum::EBPManager,
                RolesEnum::EBPDeputyManager,
                RolesEnum::RMManager,
                RolesEnum::RMDeputyManager,
            ],
            QuoteTypes::HOME => [
                RolesEnum::HomeManager,
                RolesEnum::HomeRenewalManager,
            ],
            QuoteTypes::LIFE => [
                RolesEnum::LifeManager,
                RolesEnum::LifeRenewalManager,
            ],
            QuoteTypes::TRAVEL => [
                RolesEnum::TravelManager,
            ],
            QuoteTypes::BIKE => [
                RolesEnum::BikeManager,
            ],
            QuoteTypes::YACHT => [
                RolesEnum::YachtManager,
            ],
            QuoteTypes::PET => [
                RolesEnum::PetManager,
                RolesEnum::PetNewBusinessManager,
                RolesEnum::PetRenewalManager,
            ],
            QuoteTypes::CYCLE => [
                RolesEnum::CycleManager,
            ],
            QuoteTypes::JETSKI => [
                RolesEnum::JetskiManager,
            ],
            QuoteTypes::SAVINGS => [
                RolesEnum::SavingsManager,
            ],
            QuoteTypes::DEVICE => [
                RolesEnum::SmartPhoneManager,
            ],
            QuoteTypes::CYBER => [
                RolesEnum::CyberManager,
            ],
            QuoteTypes::CORPLINE => [
                RolesEnum::CorplineManager,
                RolesEnum::CorplineDeputyManager,
                RolesEnum::CorplineRenewalManager,
            ],
            QuoteTypes::GROUP_MEDICAL => [
                RolesEnum::GMManager,
                RolesEnum::GMDeputyManager,
                RolesEnum::GMRenewalManager,
                RolesEnum::EBPManager,
                RolesEnum::EBPDeputyManager,
            ],
            QuoteTypes::BUSINESS => [
                RolesEnum::BusinessManager,
                RolesEnum::BusinessDeputyManager,
                RolesEnum::CorplineManager,
                RolesEnum::CorplineDeputyManager,
                RolesEnum::CorplineRenewalManager,
                RolesEnum::GMManager,
                RolesEnum::GMDeputyManager,
                RolesEnum::GMRenewalManager,
                RolesEnum::EBPManager,
                RolesEnum::EBPDeputyManager,
            ],
            default => [],
        };
    }

    /**
     * @return array<int, string>
     */
    private function advisorRolesForQuoteType(QuoteTypes $quoteType): array
    {
        $base = $quoteType->advisorRoles();
        $extra = match ($quoteType) {
            QuoteTypes::CAR => [
                RolesEnum::CarRenewalAdvisor,
                RolesEnum::CarAdvisor,
                RolesEnum::CarNewBusinessAdvisor,
            ],
            QuoteTypes::HOME => [RolesEnum::HomeRenewalAdvisor, RolesEnum::HomeAdvisor],
            QuoteTypes::HEALTH => [RolesEnum::HealthRenewalAdvisor, RolesEnum::HealthAdvisor],
            QuoteTypes::LIFE => [RolesEnum::LifeRenewalAdvisor, RolesEnum::LifeAdvisor],
            QuoteTypes::PET => [RolesEnum::PetRenewalAdvisor, RolesEnum::PetAdvisor],
            QuoteTypes::CORPLINE => [RolesEnum::CorpLineRenewalAdvisor, RolesEnum::CorpLineAdvisor],
            QuoteTypes::GROUP_MEDICAL => [RolesEnum::GMRenewalAdvisor, RolesEnum::GMAdvisor],
            QuoteTypes::BUSINESS => [RolesEnum::BusinessAdvisor, RolesEnum::CorpLineAdvisor, RolesEnum::GMAdvisor],
            QuoteTypes::BIKE => [RolesEnum::BikeAdvisor],
            QuoteTypes::TRAVEL => [RolesEnum::TravelAdvisor],
            QuoteTypes::CYCLE => [RolesEnum::CycleAdvisor],
            QuoteTypes::YACHT => [RolesEnum::YachtAdvisor],
            QuoteTypes::SAVINGS => [RolesEnum::SavingsAdvisor],
            QuoteTypes::CYBER => [RolesEnum::CyberAdvisor],
            QuoteTypes::JETSKI => [RolesEnum::JetskiAdvisor],
            QuoteTypes::CAR_REVIVAL => [RolesEnum::CarRevivalAdvisor],
            default => [],
        };

        return array_values(array_unique(array_merge($base, $extra)));
    }
}
