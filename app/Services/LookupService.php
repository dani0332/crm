<?php

namespace App\Services;

use App\Enums\LookupsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\TiersEnum;
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
use App\Models\QuoteStatus;
use App\Models\SalaryBand;
use App\Models\Tier;
use App\Models\UAELicenseHeldFor;
use App\Models\VehicleType;
use App\Models\YearOfManufacture;

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
        return Lookup::where([
            'code' => LookupsEnum::SEND_UPDATE_CODE,
            'parent_id' => null,
        ])
            ->withChildTree($quoteTypeId, app(SendUpdateLogService::class)->checkSendUpdatePermissions())
            ->get();
    }

    public function getCompanyTypes()
    {
        return Lookup::where('key', LookupsEnum::COMPANY_TYPE)->get();
    }

    public function getSendUpdateCategories()
    {
        $parentTypes = Lookup::where('code', LookupsEnum::SEND_UPDATE_CODE)->pluck('id');

        return Lookup::whereIn('parent_id', $parentTypes)->get();
    }
}
