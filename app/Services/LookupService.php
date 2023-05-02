<?php

namespace App\Services;

use App\Enums\QuoteStatusEnum;
use App\Enums\TiersEnum;
use App\Models\ApplicationStorage;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarModelDetail;
use App\Models\InsuranceProvider;
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
        return YearOfManufacture::select('text as id', 'text')->get();
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
        return UAELicenseHeldFor::isBackHomeActive()->get();
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

    public function getLeadStatuses()
    {
        return QuoteStatus::select('id', 'text')
            ->whereNotIn('id', [
                QuoteStatusEnum::AMLScreeningCleared, QuoteStatusEnum::Draft, QuoteStatusEnum::Cancelled, QuoteStatusEnum::AMLScreeningFailed,
                QuoteStatusEnum::TransactionDeclined, QuoteStatusEnum::PolicyIssued, QuoteStatusEnum::PolicyInvoiced,
                QuoteStatusEnum::Issued,
            ])
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get();
    }

    public function getPaymentMethods()
    {
        return PaymentMethod::select('code', 'name', 'parent_code')->orderBy('name')->get();
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
}
