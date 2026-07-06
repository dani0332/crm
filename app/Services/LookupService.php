<?php

namespace App\Services;

use App\Enums\CacheKeyEnum;
use App\Enums\ClaimsEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\HealthInsureEnum;
use App\Enums\HealthPolicyHolderEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\LookupsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\TiersEnum;
use App\Facades\Capi;
use App\Models\ApplicationStorage;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarModelDetail;
use App\Models\InsuranceProvider;
use App\Models\Lookup;
use App\Models\LostReasons;
use App\Models\MemberCategory;
use App\Models\Nationality;
use App\Models\PaymentMethod;
use App\Models\PaymentStatus;
use App\Models\QuoteStatus;
use App\Models\SalaryBand;
use App\Models\Tier;
use App\Models\UAELicenseHeldFor;
use App\Models\VehicleType;
use App\Models\VisaCategory;
use App\Models\YearOfManufacture;
use App\Services\Cache\CacheManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class LookupService extends BaseService
{
    public function getYearsOfManufacture()
    {
        return YearOfManufacture::select('text as id', 'text')->orderBy('sort_order')->get();
    }

    public function getVehicleTypes()
    {
        return VehicleType::select('id', 'text')->where('is_active', true)->get();
    }

    public function getTrimListByCarModel($id)
    {
        return CarModelDetail::select('id', 'text')->where('is_active', true)->where('car_model_id', $id)->get();
    }

    public function getBackHomeLicensed()
    {
        return UAELicenseHeldFor::isBackHomeActive()->where('is_active', true)->get();
    }

    public function getMemberCategories()
    {
        return MemberCategory::active()->get();
    }

    /** Returns an id → text map of all member categories for display purposes. */
    public function getAllMemberCategories(): array
    {
        return Cache::remember(
            CacheKeyEnum::MEMBER_CATEGORY_DISPLAY_MAP_KEY->value,
            CacheKeyEnum::MEMBER_CATEGORY_DISPLAY_MAP_KEY->expiry(),
            fn () => MemberCategory::select('id', 'text')->get()->pluck('text', 'id')->all()
        );
    }

    public function getSalaryBands()
    {
        return SalaryBand::active()->get();
    }

    public function getApplicationStorageValue($key)
    {
        return ApplicationStorage::where('key_name', $key)->first()->value;
    }

    public function getLostReasons()
    {
        return LostReasons::select('id', 'text')->get();
    }

    public function getAllInsuranceProviders()
    {
        return InsuranceProvider::select('id', 'text')->orderBy('text', 'asc')->get();
    }

    public function getLeadStatuses($extraExcludeStatuses = [], $removeFromExclude = [])
    {
        $generalExcludeStatus = [
            QuoteStatusEnum::Draft,
            QuoteStatusEnum::Cancelled,
            QuoteStatusEnum::AMLScreeningCleared,
            QuoteStatusEnum::AMLScreeningFailed,
            QuoteStatusEnum::TransactionDeclined,
            QuoteStatusEnum::PolicyIssued,
            QuoteStatusEnum::PolicyInvoiced,
            QuoteStatusEnum::Issued,
        ];

        $excludeStatuses = array_merge($generalExcludeStatus, $extraExcludeStatuses);
        $excludeStatus = $removeFromExclude ? array_diff($excludeStatuses, $removeFromExclude) : $excludeStatuses;

        return QuoteStatus::select('id', 'text')
            ->whereNotIn('id', $excludeStatus)
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get();
    }

    public function getPaymentMethods()
    {
        return PaymentMethod::select('code', 'name', 'parent_code', 'tool_tip')->orderBy('name')->get();
    }

    public function getActiveInsuranceProviders()
    {
        return InsuranceProvider::select('id', 'text')
            ->where('is_active', true)->orderBy('text', 'asc')->get();
    }

    public function getCarMake($id)
    {
        return CarMake::find($id);
    }

    public function getCarModel($id)
    {
        return CarModel::find($id);
    }

    public function getTierR()
    {
        return Tier::select('id', 'name')
            ->where(['is_active' => true, 'name' => TiersEnum::TIER_R])
            ->orderBy('name', 'asc')->get();
    }

    public function getNationality($id)
    {
        return Nationality::find($id);
    }

    public function getUaeLicenseHeldFor($id)
    {
        return UAELicenseHeldFor::find($id);
    }

    public function paymentMethodsWithSubMethods()
    {
        $paymentMethods = PaymentMethod::whereNull('parent_code')->with('childPaymentMethods')->get();
        $paymentOptions = [];
        $paymentOptions['methods'] = $paymentMethods->filter(function ($paymentMethod) {
            return $paymentMethod->childPaymentMethods->count() > 0;
        })->flatten(1)->toArray();

        $paymentOptions['subMethods'] = $paymentMethods->filter(function ($paymentMethod) {
            return $paymentMethod->childPaymentMethods->count() == 0;
        })->toArray();

        return $paymentOptions;
    }

    public function getNationalities()
    {
        return Nationality::select('id', 'text')->where('is_active', true)->orderBy('text')->get();
    }

    public function getLegalStructure()
    {
        return Lookup::where('key', LookupsEnum::LEGAL_STRUCTURE)->get();
    }

    public function getIndividualDocumentTypes()
    {
        return Lookup::where('key', LookupsEnum::DOCUMENT_ID_TYPE)->get();
    }

    public function getEntityDocumentTypes()
    {
        return Lookup::where('key', LookupsEnum::ENTITY_DOCUMENT_TYPE)->get();
    }

    public function getModeOfContact()
    {
        return Lookup::where('key', LookupsEnum::MODE_OF_CONTACT)->get();
    }

    public function getEmploymentSector()
    {
        return Lookup::where('key', LookupsEnum::EMPLOYMENT_SECTOR)->get();
    }

    public function getResidentialStatus()
    {
        return Lookup::where('key', LookupsEnum::RESIDENT_STATUS)->get();
    }

    public function getCompanyPosition()
    {
        return Lookup::where('key', LookupsEnum::COMPANY_POSITION)->get();
    }

    public function getIssuancePlaces()
    {
        return Lookup::where('key', LookupsEnum::ISSUANCE_PLACE)->get();
    }

    public function getIssuanceAuthorities()
    {
        return Lookup::where('key', LookupsEnum::ISSUING_AUTHORITY)->get();
    }

    public function getSendUpdateOptions($quoteTypeId)
    {
        $permissions = app(SendUpdateLogService::class)->checkSendUpdatePermissions();
        $permissionHash = md5(json_encode($permissions));

        return Cache::remember(
            "send_update_options_{$quoteTypeId}_{$permissionHash}",
            now()->addHour(),
            function () use ($quoteTypeId, $permissions) {
                return Lookup::where([
                    'code' => LookupsEnum::SEND_UPDATE_CODE,
                    'parent_id' => null,
                ])
                    ->withChildTree($quoteTypeId, $permissions)
                    ->get();
            }
        );
    }

    public function getCompanyTypes()
    {
        return Lookup::where('key', LookupsEnum::COMPANY_TYPE)->get();
    }

    public function getHomeLookUpData()
    {
        return CacheManager::remember(CacheKeyEnum::HOME_LOOKUPS, function () {
            return Capi::request('/api/v2-get-home-models', 'post');
        });
    }

    public function getSendUpdateCategories()
    {
        $parentTypes = Lookup::where('code', LookupsEnum::SEND_UPDATE_CODE)->pluck('id');

        return Lookup::whereIn('parent_id', $parentTypes)->get();
    }

    public function getSendUpdateCancelOptions()
    {
        return Lookup::where('key', LookupsEnum::SEND_UPDATE_CANCEL_OPTIONS)->get();
    }

    public function getSavingsQuoteLookUpData()
    {
        return CacheManager::remember(CacheKeyEnum::SAVINGS_QUOTE_LOOKUPS, function () {
            return Capi::request('/api/v1-get-all-savings-lookups', 'post');
        });
    }

    public function getRmCategories()
    {
        return Lookup::where('key', LookupsEnum::RM_CATEGORY)
            ->where('is_active', true)
            ->orderBy('text')
            ->get(['id', 'code', 'text']);
    }

    public function getVehicleColors($quoteTypeId, $providerId)
    {
        return Lookup::where('key', LookupsEnum::VEHICLE_COLOR)
            ->where('quote_type_id', $quoteTypeId)
            ->where('insurance_provider_id', $providerId)
            ->where('is_active', true)
            ->select('id', 'code', 'text')
            ->get();
    }

    public function getBankNames($quoteTypeId, $providerId)
    {
        return Lookup::where('key', LookupsEnum::BANK_NAME)
            ->where('quote_type_id', $quoteTypeId)
            ->where('insurance_provider_id', $providerId)
            ->where('is_active', true)
            ->select('id', 'code', 'text')
            ->get();
    }

    public function getDeviceCoverages()
    {
        return CacheManager::remember(CacheKeyEnum::DEVICE_QUOTE_LOOKUPS, function () {
            return Capi::request('/api/v1-get-all-device-lookups', 'post');
        });
    }
    public function getPaymentStatuses()
    {
        return PaymentStatus::where('is_active', 1)
            ->orderBy('text')
            ->get(['id', 'text']);
    }
    public function getSubSource()
    {
        return CacheManager::remember(CacheKeyEnum::SUB_SOURCES, function () {
            return Lookup::with(['childs'])->where([
                'key' => LookupsEnum::SUB_SOURCE,
                'is_active' => 1,
            ])->get();
        });
    }

    /**
     * Get claim request types
     */
    public function getClaimRequestTypes(): array
    {
        return CacheManager::remember(CacheKeyEnum::CLAIM_REQUEST_TYPE_KEY, function () {
            return Lookup::byKey(ClaimsEnum::CLAIM_REQUEST_TYPES_KEY->value)
                ->active()
                ->select('id', 'text', 'code')
                ->orderBySortOrder()
                ->get()
                ->toArray();
        });

    }

    /**
     * Get claim Service Type
     */
    public function getClaimServiceTypes(): array
    {
        return CacheManager::remember(CacheKeyEnum::CLAIM_SERVICE_TYPE_KEY, function () {
            return Lookup::byKey(ClaimsEnum::CLAIM_SERVICE_TYPES_KEY->value)
                ->active()
                ->select('id', 'text', 'code')
                ->orderBySortOrder()
                ->get()
                ->toArray();
        });

    }

    /**
     * Get claim types from lookup
     */
    public function getClaimTypes(): array
    {
        return CacheManager::remember(CacheKeyEnum::CLAIM_TYPE_KEY, function () {
            return Lookup::byKey(ClaimsEnum::CLAIM_TYPES_KEY->value)
                ->active()
                ->select('id', 'text', 'code')
                ->orderBySortOrder()
                ->get()
                ->toArray();
        });
    }

    public function getCyberQuoteLookUpData()
    {
        return CacheManager::remember(CacheKeyEnum::CYBER_QUOTE_LOOKUPS, function () {
            return Capi::request('/api/cyber/lookup', 'get');
        });
    }

    public function getCyberCoverages()
    {
        return Cache::remember(
            'cyber_coverages',
            now()->addHour(),
            function () {
                return Lookup::where('key', LookupsEnum::CYBER_COVERAGE)
                    ->select('id', 'code', 'text')
                    ->orderBy('sort_order')
                    ->get();
            }
        );
    }

    public function getGender()
    {
        return Cache::remember(
            CacheKeyEnum::GENDER_KEY->value,
            CacheKeyEnum::GENDER_KEY->expiry(),
            fn () => Lookup::where('key', LookupsEnum::GENDER)
                ->select('text', 'code')
                ->get()
        );
    }

    /**
     * @return Collection<int, Lookup>
     */
    public function getHealthInsureOptions(?string $leadSource = null): Collection
    {
        $rows = Cache::remember(
            CacheKeyEnum::HEALTH_INSURE_OPTIONS_KEY->value,
            CacheKeyEnum::HEALTH_INSURE_OPTIONS_KEY->expiry(),
            fn () => Lookup::where('key', LookupsEnum::HEALTH_INSURE_OPTIONS)->get()
        );

        if (! in_array($leadSource, [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD], true)) {
            return $rows;
        }

        return $rows->map(function ($item) {
            $code = $item->code ?? null;
            if (! is_string($code)) {
                return $item;
            }
            $case = HealthInsureEnum::tryFrom($code);
            if ($case === null) {
                return $item;
            }
            $row = clone $item;
            $row->text = $case->getLabel();

            return $row;
        })->values();
    }

    /**
     * @return Collection<int, Lookup>
     */
    public function getPolicyHolder(?string $leadSource = null): Collection
    {
        $rows = Cache::remember(
            CacheKeyEnum::POLICY_HOLDER_KEY->value,
            CacheKeyEnum::POLICY_HOLDER_KEY->expiry(),
            fn () => Lookup::where('key', LookupsEnum::POLICY_HOLDER_OPTIONS)->get()
        );

        if (! in_array($leadSource, [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD], true)) {
            return $rows;
        }

        return $rows->map(function ($item) {
            $code = $item->code ?? null;
            if (! is_string($code)) {
                return $item;
            }
            $case = HealthPolicyHolderEnum::tryFrom($code);
            if ($case === null) {
                return $item;
            }
            $row = clone $item;
            $row->text = $case->getLabel();

            return $row;
        })->values();
    }

    public function getPolicyHolderCategory()
    {
        return Cache::remember(
            CacheKeyEnum::POLICY_HOLDER_CATEGORY_KEY->value,
            CacheKeyEnum::POLICY_HOLDER_CATEGORY_KEY->expiry(),
            fn () => Lookup::where('key', LookupsEnum::POLICY_HOLDER_CATEGORY)->get()
        );
    }

    public function getVisaCategory()
    {
        return Cache::remember(
            CacheKeyEnum::VISA_CATEGORY_KEY->value,
            CacheKeyEnum::VISA_CATEGORY_KEY->expiry(),
            fn () => VisaCategory::active()->orderBy('sort_order')->get()
        );
    }

    public function getMemberRelations()
    {
        return Cache::remember(
            CacheKeyEnum::MEMBER_RELATIONS_KEY->value,
            CacheKeyEnum::MEMBER_RELATIONS_KEY->expiry(),
            fn () => Lookup::where('key', LookupsEnum::MEMBER_RELATION->value)
                ->where('is_active', true)->orderBy('sort_order')
                ->get()
        );
    }

    public function getHealthMemberRelations()
    {
        return Cache::remember(
            CacheKeyEnum::HEALTH_MEMBER_RELATIONS_KEY->value,
            CacheKeyEnum::HEALTH_MEMBER_RELATIONS_KEY->expiry(),
            fn () => Lookup::where('key', LookupsEnum::HEALTH_MEMBER_RELATION->value)
                ->where('is_active', true)->orderBy('sort_order')
                ->get()
        );
    }

    public function getDomesticWorkerRelations()
    {
        return Cache::remember(
            CacheKeyEnum::DOMESTIC_WORKER_RELATIONS_KEY->value,
            CacheKeyEnum::DOMESTIC_WORKER_RELATIONS_KEY->expiry(),
            fn () => Lookup::where('key', LookupsEnum::DOMESTIC_WORKER_RELATION->value)
                ->where('is_active', true)->orderBy('sort_order')
                ->get()
        );
    }

    /**
     * Returns a code → label map built from all three member relation lookup keys:
     * 'member-relation', 'health-member-relation', and 'domestic-worker-relation'.
     *
     * Use this for display only so any relation_code stored in a record resolves
     * to its label regardless of which set it originated from.
     */
    public function getMemberRelationDisplayMap(): array
    {
        return Cache::remember(
            CacheKeyEnum::MEMBER_RELATION_DISPLAY_MAP_KEY->value,
            CacheKeyEnum::MEMBER_RELATION_DISPLAY_MAP_KEY->expiry(),
            function () {
                $toMap = fn ($collection) => $collection->pluck('text', 'code')->all();

                return array_merge(
                    $toMap($this->getMemberRelations()),
                    $toMap($this->getHealthMemberRelations()),
                    $toMap($this->getDomesticWorkerRelations()),
                );
            }
        );
    }

    /**
     * Returns a code → label map for resolving the display text of gender values
     * stored in health quote records, including legacy codes written before the
     * gender lookup was introduced (M/FS/FM/Male/Female).
     *
     * Use this for display only; use getGenderOptions(QuoteTypeId::Health) for dropdowns.
     */
    public function getHealthGenderDisplayMap(): array
    {
        return Cache::remember(
            CacheKeyEnum::HEALTH_GENDER_DISPLAY_MAP_KEY->value,
            CacheKeyEnum::HEALTH_GENDER_DISPLAY_MAP_KEY->expiry(),
            function () {
                $newOptions = $this->getGender()->pluck('text', 'code')->all();

                $legacyOptions = [
                    GenericRequestEnum::MALE_SINGLE_VALUE => GenericRequestEnum::MALE_SINGLE,
                    GenericRequestEnum::FEMALE_SINGLE_VALUE => GenericRequestEnum::FEMALE_SINGLE,
                    GenericRequestEnum::FEMALE_MARRIED_VALUE => GenericRequestEnum::FEMALE_MARRIED,
                    GenericRequestEnum::FEMALE_SHORT_VALUE => GenericRequestEnum::FEMALE,
                    GenericRequestEnum::MALE_SINGLE => GenericRequestEnum::MALE_SINGLE,
                    GenericRequestEnum::FEMALE => GenericRequestEnum::FEMALE,
                ];

                // New lookup entries take precedence; legacy entries fill in any gaps.
                return array_merge($legacyOptions, $newOptions);
            }
        );
    }

    public function getCorplineLeadTypes(): Collection
    {
        return Lookup::where('key', LookupsEnum::CORPLINE_LEAD_TYPE->value)
            ->select('code', 'text')
            ->get()
            ->map(fn ($lookup) => ['value' => $lookup->code, 'label' => $lookup->text]);
    }
}
