<?php

namespace App\Enums;

use App\Jobs\OCB\SendCarOCBIntroEmailJob;
use App\Jobs\OCB\SendTravelOCBIntroEmailJob;
use App\Jobs\SendHealthOCBIntroEmailJob;
use App\Models\BikeQuote;
use App\Models\BikeQuoteRequestDetail;
use App\Models\BusinessQuote;
use App\Models\BusinessQuoteRequestDetail;
use App\Models\CarQuote;
use App\Models\CarQuoteRequestDetail;
use App\Models\CycleQuote;
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
use App\Models\TravelQuote;
use App\Models\TravelQuoteRequestDetail;
use App\Models\YachtQuote;
use App\Models\YachtQuoteRequestDetail;
use App\Services\BikeAllocationService;
use App\Services\CarAllocationService;
use App\Services\HealthAllocationService;
use App\Services\TravelAllocationService;
use App\Strategies\Allocations\BikeAllocation;
use App\Strategies\Allocations\CarAllocation;
use App\Strategies\Allocations\CorplineAllocation;
use App\Strategies\Allocations\CycleAllocation;
use App\Strategies\Allocations\HealthAllocation;
use App\Strategies\Allocations\LifeAllocation;
use App\Strategies\Allocations\PetAllocation;
use App\Strategies\Allocations\TravelAllocation;
use App\Strategies\Allocations\YachtAllocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

enum QuoteTypes: string
{
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
    case CAR_BIKE = 'Car_Bike';

    public function id(): string
    {
        return self::getId($this);
    }

    public static function getId(self $value): int
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
        ];

        return isset($types[$value]) ? $types[$value] : null;
    }

    public static function getIdFromValue(string $value): ?int
    {
        return self::getId(match (ucfirst($value)) {
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
            default => null,
        });
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
            default => new PersonalQuote,
        };
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
            self::CYCLE => new PersonalQuoteDetail,
            self::JETSKI => new PersonalQuoteDetail,
            default => new PersonalQuoteDetail,
        };
    }

    public function ocbEmailJob()
    {
        return match ($this) {
            self::CAR => SendCarOCBIntroEmailJob::class,
            self::TRAVEL => SendTravelOCBIntroEmailJob::class,
            self::HEALTH => SendHealthOCBIntroEmailJob::class,
            default => null,
        };
    }

    public function ecomUrl()
    {
        return match ($this) {
            self::CAR => config('constants.ECOM_CAR_INSURANCE_QUOTE_URL'),
            self::TRAVEL => config('constants.ECOM_TRAVEL_INSURANCE_QUOTE_URL'),
            default => null,
        };
    }

    public function shortCode()
    {
        return match ($this) {
            self::CAR, self::CAR_REVIVAL, self::CAR_BIKE => 'CAR-',
            self::HOME => 'HOM-',
            self::HEALTH => 'HEA-',
            self::LIFE => 'LIF-',
            self::BUSINESS, self::GROUP_MEDICAL, self::CORPLINE, self::AMT => 'BUS-',
            self::BIKE => 'BIK-',
            self::YACHT => 'YAC-',
            self::TRAVEL => 'TRA-',
            self::PET => 'PET-',
            self::CYCLE => 'CYC-',
            self::JETSKI => 'JSK-',
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
        ];

        return $codes[$code] ?? null;
    }

    public function url(string $uuid): string
    {
        return match ($this) {
            self::CAR => checkPersonalQuotes($this->value) ? route('car-quotes-show', $uuid) : route('car.show', $uuid),
            self::HOME => checkPersonalQuotes($this->value) ? route('home-quotes-show', $uuid) : route('home.show', $uuid),
            self::HEALTH => checkPersonalQuotes($this->value) ? route('health-quotes-show', $uuid) : route('health.show', $uuid),
            self::LIFE => checkPersonalQuotes($this->value) ? route('life-quotes-show', $uuid) : (Route::has('life.show') ? route('life.show', $uuid) : route('life-quotes-show', $uuid)),
            self::BUSINESS => checkPersonalQuotes($this->value) ? route('business-quotes-show', $uuid) : route('business.show', $uuid),
            self::BIKE => checkPersonalQuotes($this->value) ? route('bike-quotes-show', $uuid) : route('bike.show', $uuid),
            self::YACHT => checkPersonalQuotes($this->value) ? route('yacht-quotes-show', $uuid) : route('yacht.show', $uuid),
            self::TRAVEL => checkPersonalQuotes($this->value) ? route('travel-quotes-show', $uuid) : route('travel.show', $uuid),
            self::PET => checkPersonalQuotes($this->value) ? route('pet-quotes-show', $uuid) : route('pet.show', $uuid),
            self::CYCLE => checkPersonalQuotes($this->value) ? route('cycle-quotes-show', $uuid) : route('cycle.show', $uuid),
            self::JETSKI => checkPersonalQuotes($this->value) ? route('jetski-quotes-show', $uuid) : route('jetski.show', $uuid),
            self::CORPLINE => checkPersonalQuotes($this->value) ? route('business-quotes-show', $uuid) : route('business.show', $uuid),
            self::GROUP_MEDICAL => checkPersonalQuotes($this->value) ? route('gm-quotes-show', $uuid) : route('amt.show', $uuid),
        };
    }

    public function quoteLink(string $uuid, array $queryParams = [])
    {
        $queryParamsStr = http_build_query($queryParams);

        return match ($this) {
            self::TRAVEL,self::CAR,self::HEALTH => "{$this->ecomUrl()}{$uuid}".($queryParamsStr ? "?{$queryParamsStr}" : ''),
        };
    }

    public function allocate(string $uuid, $teamId = false, bool $overrideAdvisorId = false, bool $tierOnly = false)
    {
        $allocationService = match ($this) {
            self::CAR => new CarAllocation(new CarAllocationService, $uuid, $teamId, evaluateTierOnly: $tierOnly, overrideAdvisorId: $overrideAdvisorId),
            self::HEALTH => new HealthAllocation(new HealthAllocationService, $uuid, overrideAdvisorId: $overrideAdvisorId),
            self::BIKE => new BikeAllocation(new BikeAllocationService, $uuid, overrideAdvisorId: $overrideAdvisorId),
            self::TRAVEL => new TravelAllocation(new TravelAllocationService, $uuid, $teamId, overrideAdvisorId: $overrideAdvisorId),
            self::CYCLE => new CycleAllocation($this, $uuid, $teamId, overrideAdvisorId: $overrideAdvisorId),
            self::YACHT => new YachtAllocation($this, $uuid, $teamId, overrideAdvisorId: $overrideAdvisorId),
            self::PET => new PetAllocation($this, $uuid, $teamId, overrideAdvisorId: $overrideAdvisorId),
            self::LIFE => new LifeAllocation($this, $uuid, $teamId, overrideAdvisorId: $overrideAdvisorId),
            self::CORPLINE => new CorplineAllocation($this, $uuid, $teamId, overrideAdvisorId: $overrideAdvisorId),
            default => null,
        };

        if ($allocationService) {
            return $allocationService->executeSteps();
        }

        return $allocationService;
    }
}
