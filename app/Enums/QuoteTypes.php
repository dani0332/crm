<?php

namespace App\Enums;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Jobs\OCB\SendCarOCBIntroEmailJob;
use App\Jobs\OCB\SendCyberOCBIntroEmailJob;
use App\Jobs\OCB\SendDeviceOCBIntroEmailJob;
use App\Jobs\OCB\SendTravelOCBIntroEmailJob;
use App\Jobs\SendHealthOCBIntroEmailJob;
use App\Jobs\SendHomeOCBIntroEmailJob;
use App\Models\BikeQuote;
use App\Models\BikeQuoteRequestDetail;
use App\Models\BusinessQuote;
use App\Models\BusinessQuoteRequestDetail;
use App\Models\CarQuote;
use App\Models\CarQuoteRequestDetail;
use App\Models\CycleQuote;
use App\Models\DeviceQuote;
use App\Models\HealthQuote;
use App\Models\HealthQuoteRequestDetail;
use App\Models\HomeQuote;
use App\Models\HomeQuoteRequestDetail;
use App\Models\JetskiQuote;
use App\Models\LifeQuote;
use App\Models\LifeQuoteRequestDetail;
use App\Models\PersonalQuote;
use App\Models\PersonalQuoteDetail;
use App\Models\PetQuote;
use App\Models\PetQuoteRequestDetail;
use App\Models\SavingsQuote;
use App\Models\TravelQuote;
use App\Models\TravelQuoteRequestDetail;
use App\Models\User;
use App\Models\YachtQuote;
use App\Models\YachtQuoteRequestDetail;
use App\Services\BikeAllocationService;
use App\Services\Logger\LoggerService;
use App\Strategies\Allocations\BikeAllocation;
use App\Strategies\Allocations\CarAllocation;
use App\Strategies\Allocations\CorplineAllocation;
use App\Strategies\Allocations\CyberAllocation;
use App\Strategies\Allocations\CycleAllocation;
use App\Strategies\Allocations\DeviceAllocation;
use App\Strategies\Allocations\GroupMedicalAllocation;
use App\Strategies\Allocations\HealthAllocation;
use App\Strategies\Allocations\HomeAllocation;
use App\Strategies\Allocations\LifeAllocation;
use App\Strategies\Allocations\PetAllocation;
use App\Strategies\Allocations\SavingsAllocation;
use App\Strategies\Allocations\TravelAllocation;
use App\Strategies\Allocations\YachtAllocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

enum QuoteTypes: string
{
    use Enumable;

    case CAR = 'Car';
    case HOME = 'Home';
    case HEALTH = 'Health';
    case LIFE = 'Life';
    case BUSINESS = 'Business';
    case BIKE = 'Bike';
    case YACHT = 'Yacht';
    case TRAVEL = 'Travel';
    case PET = 'Pet';
    case CYCLE = 'Cycle';
    case JETSKI = 'Jetski';
    case AMT = 'Amt';
    case PERSONAL = 'Personal';
    case GROUP_MEDICAL = 'Group Medical';
    case CORPLINE = 'CorpLine';
    case CAR_REVIVAL = 'CarRevival';
    case LIFE_REVIVAL = 'LifeRevival';
    case HOME_REVIVAL = 'HomeRevival';
    case CAR_BIKE = 'Car_Bike';
    case SAVINGS = 'Savings';
    case DEVICE = 'Device';
    case CYBER = 'Cyber';
    case CAR_CAT_A = 'CAR_CAT_A';

    public function id(): string
    {
        return self::getId($this) ?? '';
    }

    public static function getId(self $value): ?int
    {
        return match ($value) {
            QuoteTypes::CAR => 1,
            QuoteTypes::HOME => 2,
            QuoteTypes::HEALTH => 3,
            QuoteTypes::LIFE => 4,
            QuoteTypes::BUSINESS => 5,
            QuoteTypes::BIKE => 6,
            QuoteTypes::YACHT => 7,
            QuoteTypes::TRAVEL => 8,
            QuoteTypes::PET => 9,
            QuoteTypes::CYCLE => 10,
            QuoteTypes::JETSKI => 11,
            QuoteTypes::CORPLINE => 101,
            QuoteTypes::GROUP_MEDICAL => 102,
            QuoteTypes::SAVINGS => 18,
            QuoteTypes::DEVICE => 20,
            QuoteTypes::CYBER => 19,
            default => null,
        };
    }

    public static function getName($value)
    {
        $types = [
            1 => QuoteTypes::CAR,
            2 => QuoteTypes::HOME,
            3 => QuoteTypes::HEALTH,
            4 => QuoteTypes::LIFE,
            5 => QuoteTypes::BUSINESS,
            6 => QuoteTypes::BIKE,
            7 => QuoteTypes::YACHT,
            8 => QuoteTypes::TRAVEL,
            9 => QuoteTypes::PET,
            10 => QuoteTypes::CYCLE,
            11 => QuoteTypes::JETSKI,
            101 => QuoteTypes::CORPLINE,
            102 => QuoteTypes::GROUP_MEDICAL,
            18 => QuoteTypes::SAVINGS,
            19 => QuoteTypes::CYBER,
            20 => QuoteTypes::DEVICE,
        ];

        return isset($types[$value]) ? $types[$value] : null;
    }

    public static function getIdFromValue(string $value): ?int
    {
        // Normalize the value - handle "Cyber Insurance" product name using TeamNameEnum constant
        $normalizedValue = match (ucfirst(trim($value))) {
            TeamNameEnum::CYBER => QuoteTypes::CYBER->value,
            default => ucfirst(trim($value)),
        };

        $quoteTypeEnum = match ($normalizedValue) {
            'Car' => QuoteTypes::CAR,
            'Home' => QuoteTypes::HOME,
            'Health' => QuoteTypes::HEALTH,
            'Life' => QuoteTypes::LIFE,
            'Business' => QuoteTypes::BUSINESS,
            'Bike' => QuoteTypes::BIKE,
            'Yacht' => QuoteTypes::YACHT,
            'Travel' => QuoteTypes::TRAVEL,
            'Pet' => QuoteTypes::PET,
            'Cycle' => QuoteTypes::CYCLE,
            'Jetski' => QuoteTypes::JETSKI,
            'CorpLine' => QuoteTypes::CORPLINE,
            'Group Medical' => QuoteTypes::GROUP_MEDICAL,
            'Savings' => QuoteTypes::SAVINGS,
            'Device' => QuoteTypes::DEVICE,
            'Cyber' => QuoteTypes::CYBER,
            default => null,
        };

        return $quoteTypeEnum ? self::getId($quoteTypeEnum) : null;
    }

    public function model(): Model
    {
        return match ($this) {
            self::CAR => checkPersonalQuotes($this->value) ? new PersonalQuote : new CarQuote,
            self::HOME => checkPersonalQuotes($this->value) ? new PersonalQuote : new HomeQuote,
            self::HEALTH => checkPersonalQuotes($this->value) ? new PersonalQuote : new HealthQuote,
            self::LIFE => checkPersonalQuotes($this->value) ? new PersonalQuote : new LifeQuote,
            self::BUSINESS, self::CORPLINE, self::GROUP_MEDICAL => checkPersonalQuotes($this->value) ? new PersonalQuote : new BusinessQuote,
            self::BIKE => checkPersonalQuotes($this->value) ? new PersonalQuote : new BikeQuote,
            self::YACHT => checkPersonalQuotes($this->value) ? new PersonalQuote : new YachtQuote,
            self::TRAVEL => checkPersonalQuotes($this->value) ? new PersonalQuote : new TravelQuote,
            self::PET => checkPersonalQuotes($this->value) ? new PersonalQuote : new PetQuote,
            self::CYCLE => checkPersonalQuotes($this->value) ? new PersonalQuote : new CycleQuote,
            self::JETSKI => checkPersonalQuotes($this->value) ? new PersonalQuote : new JetskiQuote,
            self::SAVINGS => checkPersonalQuotes($this->value) ? new PersonalQuote : new SavingsQuote,
            self::DEVICE => checkPersonalQuotes($this->value) ? new PersonalQuote : new DeviceQuote,
            self::CYBER => new PersonalQuote,
            default => new PersonalQuote,
        };
    }

    public function isPersonalQuote()
    {
        return $this->model() instanceof PersonalQuote;
    }

    public function detailModel(): Model
    {
        return match ($this) {
            self::CAR => checkPersonalQuotes($this->value) ? new PersonalQuoteDetail : new CarQuoteRequestDetail,
            self::HOME => checkPersonalQuotes($this->value) ? new PersonalQuoteDetail : new HomeQuoteRequestDetail,
            self::HEALTH => checkPersonalQuotes($this->value) ? new PersonalQuoteDetail : new HealthQuoteRequestDetail,
            self::LIFE => checkPersonalQuotes($this->value) ? new PersonalQuoteDetail : new LifeQuoteRequestDetail,
            self::BUSINESS, self::CORPLINE, self::GROUP_MEDICAL => checkPersonalQuotes($this->value) ? new PersonalQuoteDetail : new BusinessQuoteRequestDetail,
            self::BIKE => checkPersonalQuotes($this->value) ? new PersonalQuoteDetail : new BikeQuoteRequestDetail,
            self::YACHT => checkPersonalQuotes($this->value) ? new PersonalQuoteDetail : new YachtQuoteRequestDetail,
            self::TRAVEL => checkPersonalQuotes($this->value) ? new PersonalQuoteDetail : new TravelQuoteRequestDetail,
            self::PET => checkPersonalQuotes($this->value) ? new PersonalQuoteDetail : new PetQuoteRequestDetail,
            default => new PersonalQuoteDetail,
        };
    }

    public function ocbEmailJob()
    {
        return match ($this) {
            self::CAR => SendCarOCBIntroEmailJob::class,
            self::TRAVEL => SendTravelOCBIntroEmailJob::class,
            self::HOME => SendHomeOCBIntroEmailJob::class,
            self::CYBER => SendCyberOCBIntroEmailJob::class,
            self::DEVICE => SendDeviceOCBIntroEmailJob::class,
            // self::HEALTH => SendHealthOCBIntroEmailJob::class,
            default => null,
        };
    }

    public function ecomUrl()
    {
        return match ($this) {
            self::CAR => config('constants.ECOM_CAR_INSURANCE_QUOTE_URL'),
            self::TRAVEL => config('constants.ECOM_TRAVEL_INSURANCE_QUOTE_URL'),
            self::CYBER => config('constants.ECOM_CYBER_INSURANCE_QUOTE_URL'),
            default => null,
        };
    }

    public function shortCode()
    {
        return match ($this) {
            self::CAR, self::CAR_REVIVAL, self::CAR_BIKE => 'CAR-',
            self::HOME => 'HOM-',
            self::HEALTH => 'HEA-',
            self::LIFE, self::LIFE_REVIVAL => 'LIF-',
            self::BUSINESS, self::GROUP_MEDICAL, self::CORPLINE, self::AMT => 'BUS-',
            self::BIKE => 'BIK-',
            self::YACHT => 'YAC-',
            self::TRAVEL => 'TRA-',
            self::PET => 'PET-',
            self::CYCLE => 'CYC-',
            self::JETSKI => 'JSK-',
            self::SAVINGS => 'SAV-',
            self::DEVICE => 'DEV-',
            self::CYBER => 'CYB-',
            default => null,
        };
    }

    public static function getNameShortCode(string $code)
    {
        $codes = [
            'CAR' => self::CAR,
            'HOM' => self::HOME,
            'HEA' => self::HEALTH,
            'LIF' => self::LIFE,
            'BUS' => self::BUSINESS,
            'BIK' => self::BIKE,
            'YAC' => self::YACHT,
            'TRA' => self::TRAVEL,
            'PET' => self::PET,
            'CYC' => self::CYCLE,
            'JSK' => self::JETSKI,
            'SAV' => self::SAVINGS,
            'DEV' => self::DEVICE,
            'CYB' => self::CYBER,
        ];

        return $codes[$code] ?? null;
    }

    public function url(string $uuid): string
    {
        $isPersonalQuote = checkPersonalQuotes($this->value);

        return match ($this) {
            self::CAR => $isPersonalQuote ? route('car-quotes-show', $uuid) : route('car.show', $uuid),
            self::HOME => $isPersonalQuote ? route('home-quotes-show', $uuid) : route('home.show', $uuid),
            self::HEALTH => $isPersonalQuote ? route('health-quotes-show', $uuid) : route('health.show', $uuid),
            self::LIFE => $isPersonalQuote ? route('life-quotes-show', $uuid) : (Route::has('life.show') ? route('life.show', $uuid) : route('life-quotes-show', $uuid)),
            self::BUSINESS => $isPersonalQuote ? route('business-quotes-show', $uuid) : route('business.show', $uuid),
            self::BIKE => $isPersonalQuote ? route('bike-quotes-show', $uuid) : route('bike.show', $uuid),
            self::YACHT => $isPersonalQuote ? route('yacht-quotes-show', $uuid) : route('yacht.show', $uuid),
            self::TRAVEL => $isPersonalQuote ? route('travel-quotes-show', $uuid) : route('travel.show', $uuid),
            self::PET => $isPersonalQuote ? route('pet-quotes-show', $uuid) : route('pet.show', $uuid),
            self::CYCLE => $isPersonalQuote ? route('cycle-quotes-show', $uuid) : route('cycle.show', $uuid),
            self::JETSKI => $isPersonalQuote ? route('jetski-quotes-show', $uuid) : route('jetski.show', $uuid),
            self::CORPLINE => $isPersonalQuote ? route('business-quotes-show', $uuid) : route('business.show', $uuid),
            self::GROUP_MEDICAL => $isPersonalQuote ? route('gm-quotes-show', $uuid) : route('amt.show', $uuid),
            self::SAVINGS => route('savings-quotes-show', $uuid),
            self::DEVICE => route('device-quotes-show', $uuid),
            self::CYBER => route('cyber-quotes-show', $uuid),
        };
    }

    public function quoteLink(string $uuid, array $queryParams = [])
    {
        $queryParamsStr = http_build_query($queryParams);

        return match ($this) {
            self::TRAVEL,self::CAR,self::HEALTH => "{$this->ecomUrl()}{$uuid}".($queryParamsStr ? "?{$queryParamsStr}" : ''),
        };
    }

    public function allocate(string $uuid, $teamId = false, bool $overrideAdvisorId = false, bool $tierOnly = false, bool $isReAssignment = false, bool $sicAdvisorRequested = false)
    {
        LoggerService::startQuoteLogging($this->refId($uuid), LoggerFeatureEnum::ALLOCATION);

        $allocationService = match ($this) {
            self::CAR => new CarAllocation($uuid, $teamId, evaluateTierOnly: $tierOnly, overrideAdvisorId: $overrideAdvisorId, sicAdvisorRequested: $sicAdvisorRequested),
            self::HEALTH => new HealthAllocation($uuid, overrideAdvisorId: $overrideAdvisorId),
            self::BIKE => new BikeAllocation(new BikeAllocationService, $uuid, overrideAdvisorId: $overrideAdvisorId),
            self::TRAVEL => new TravelAllocation($uuid, $teamId, overrideAdvisorId: $overrideAdvisorId),
            self::CYCLE => new CycleAllocation($this, $uuid, $teamId, overrideAdvisorId: $overrideAdvisorId, isReAssignment: $isReAssignment),
            self::YACHT => new YachtAllocation($this, $uuid, $teamId, overrideAdvisorId: $overrideAdvisorId, isReAssignment: $isReAssignment),
            self::PET => new PetAllocation($this, $uuid, $teamId, overrideAdvisorId: $overrideAdvisorId, isReAssignment: $isReAssignment),
            self::LIFE => new LifeAllocation($this, $uuid, $teamId, overrideAdvisorId: $overrideAdvisorId, isReAssignment: $isReAssignment),
            self::CORPLINE => new CorplineAllocation($this, $uuid, $teamId, overrideAdvisorId: $overrideAdvisorId, isReAssignment: $isReAssignment),
            self::HOME => new HomeAllocation($this, $uuid, $teamId, overrideAdvisorId: $overrideAdvisorId, isReAssignment: $isReAssignment),
            self::SAVINGS => new SavingsAllocation($this, $uuid, $teamId, overrideAdvisorId: $overrideAdvisorId, isReAssignment: $isReAssignment),
            self::GROUP_MEDICAL => new GroupMedicalAllocation($this, $uuid, $teamId, overrideAdvisorId: $overrideAdvisorId, isReAssignment: $isReAssignment),
            self::CYBER => new CyberAllocation($uuid, $teamId, overrideAdvisorId: $overrideAdvisorId),
            self::DEVICE => new DeviceAllocation($uuid, $teamId, overrideAdvisorId: $overrideAdvisorId),
            default => null,
        };

        if ($allocationService) {
            return $allocationService->execute();
        }

        return $allocationService;
    }

    public function advisorRoles()
    {
        return match ($this) {
            self::CAR => [RolesEnum::CarAdvisor, RolesEnum::CarRevivalAdvisor],
            self::HEALTH => [RolesEnum::HealthAdvisor, RolesEnum::EBPAdvisor, RolesEnum::RMAdvisor],
            self::BIKE => [RolesEnum::BikeAdvisor],
            self::TRAVEL => [RolesEnum::TravelAdvisor],
            self::CYCLE => [RolesEnum::CycleAdvisor],
            self::YACHT => [RolesEnum::YachtAdvisor],
            self::PET => [RolesEnum::PetAdvisor],
            self::LIFE => [RolesEnum::LifeAdvisor],
            self::CORPLINE => [RolesEnum::CorpLineAdvisor],
            self::HOME => [RolesEnum::HomeAdvisor],
            self::SAVINGS => [RolesEnum::SavingsAdvisor],
            self::GROUP_MEDICAL => [RolesEnum::GMAdvisor],
            self::CAR_REVIVAL => [RolesEnum::CarRevivalAdvisor],
            self::CYBER => [RolesEnum::CyberAdvisor],
            self::BUSINESS => [RolesEnum::BusinessAdvisor, RolesEnum::CorpLineAdvisor, RolesEnum::GMAdvisor],
            self::JETSKI => [RolesEnum::JetskiAdvisor],
            self::DEVICE => [RolesEnum::SmartPhoneAdvisor],
            default => [],
        };
    }

    public static function primaryTypes(): array
    {
        return [
            self::CAR,
            self::HOME,
            self::HEALTH,
            self::LIFE,
            self::BUSINESS,
            self::BIKE,
            self::YACHT,
            self::TRAVEL,
            self::PET,
            self::CYCLE,
            self::JETSKI,
            self::SAVINGS,
            self::CYBER,
        ];
    }

    public static function primaryTypesWithIds(): array
    {
        $options = [];

        foreach (self::primaryTypes() as $quoteType) {
            $id = self::getId($quoteType);
            if ($id === null) {
                continue;
            }

            $options[] = [
                'id' => $id,
                'text' => $quoteType->value,
            ];
        }

        usort($options, static fn (array $a, array $b): int => strcasecmp($a['text'], $b['text']));

        return $options;
    }

    /**
     * Check if a user has role-based or permission-based access to this quote type.
     */
    public function userHasAccess(User $user): bool
    {
        $userRoles = $user->getRoleNames()->toArray();

        // Check admin access
        if (in_array(RolesEnum::Admin, $userRoles)) {
            return true;
        }

        // Check advisor roles
        $quoteTypeRoles = $this->advisorRoles();
        $hasAdvisorRole = ! empty(array_intersect($quoteTypeRoles, $userRoles));

        // Check manager roles
        $hasManagerRole = in_array($this->name.'_MANAGER', $userRoles);

        // Check VIEW_ALL_REPORTS permission
        $hasViewAllReportsPermission = $user->can(PermissionsEnum::VIEW_ALL_REPORTS) && userHasProduct($this, $user);

        return $hasAdvisorRole || $hasManagerRole || $hasViewAllReportsPermission;
    }

    /**
     * Get allowed quote type IDs based on user roles and selected LOB filter.
     *
     * @return array<int>
     */
    public static function allowedIdsForUser(User $user, ?int $quoteTypeId = null): array
    {
        $allowedIds = collect(self::primaryTypes())
            ->filter(fn (self $quoteType) => $quoteType->userHasAccess($user))
            ->map(fn (self $quoteType) => self::getId($quoteType))
            ->filter()
            ->values()
            ->all();

        if ($quoteTypeId !== null) {
            // Only return the requested quote type ID if the user has access to it
            if (in_array($quoteTypeId, $allowedIds, true)) {
                return [$quoteTypeId];
            }

            // User attempted to access unauthorized quote type
            return [];
        }

        return $allowedIds;
    }

    /**
     * Get all quote types with their IDs.
     */
    public static function allTypesWithIds(): array
    {
        $typesWithIds = [];
        foreach (self::cases() as $quoteType) {
            if (! $quoteType) {
                continue;
            }

            $id = $quoteType->id();

            if (! $id) {
                continue;
            }

            $typesWithIds[] = [
                'id' => $id,
                'name' => $quoteType->value,
            ];
        }

        return $typesWithIds;
    }

    public function refId(string $uuid)
    {
        return "{$this->shortCode()}{$uuid}";
    }

    public static function getQuoteTypeIdToClass($quoteType): string
    {
        switch ($quoteType) {
            case self::getId(self::CAR):
                return CarQuote::class;
            case self::getId(self::HOME):
                return HomeQuote::class;
            case self::getId(self::HEALTH):
                return HealthQuote::class;
            case self::getId(self::LIFE):
                return LifeQuote::class;
            case self::getId(self::BUSINESS):
                return BusinessQuote::class;
            case self::getId(self::TRAVEL):
                return TravelQuote::class;
            case self::getId(self::YACHT):
                return YachtQuote::class;
            case self::getId(self::BIKE):
                return BikeQuote::class;
            case self::getId(self::CYCLE):
                return CycleQuote::class;
            case self::getId(self::JETSKI):
                return JetskiQuote::class;
            default:
                return PersonalQuote::class;
        }
    }

    public function getTeams()
    {
        return match ($this) {
            self::BUSINESS => [
                TeamsEnum::CORPLINE,
                TeamsEnum::GROUP_MEDICAL,
            ],
            self::DEVICE => [
                TeamsEnum::DEVICE_INSURANCE,
            ],
            self::CYBER => [
                TeamsEnum::CYBER_INSURANCE,
            ],
            default => [$this],
        };
    }

    /**
     * Resolve the QuoteType enum for a given team name.
     */
    public static function getQuoteTypesFromTeamName(string $teamName)
    {
        return match ($teamName) {
            TeamsEnum::DEVICE_INSURANCE->value => [
                self::DEVICE,
            ],
            default => [$teamName],
        };
    }

    public function isGroupMedical(Model $quote): bool
    {
        return $quote->business_type_of_insurance_id == BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL;
    }

    public function modelClass(): string
    {
        return match ($this) {
            self::CAR => CarQuote::class,
            self::HOME => HomeQuote::class,
            self::HEALTH => HealthQuote::class,
            self::LIFE => LifeQuote::class,
            self::BUSINESS, self::CORPLINE, self::GROUP_MEDICAL => BusinessQuote::class,
            self::BIKE => BikeQuote::class,
            self::YACHT => YachtQuote::class,
            self::TRAVEL => TravelQuote::class,
            self::PET => PetQuote::class,
            self::CYCLE => CycleQuote::class,
            self::JETSKI => JetskiQuote::class,
            self::SAVINGS => SavingsQuote::class,
            self::DEVICE => DeviceQuote::class,
            default => PersonalQuote::class,
        };
    }

    public static function quoteJourneyOnCustomerDocumentUploadTypes(): array
    {
        return [
            self::SAVINGS,
        ];
    }

}
