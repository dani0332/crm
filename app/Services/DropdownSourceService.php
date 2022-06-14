<?php

namespace App\Services;

use App\Enums\quoteTypeCode;
use App\Models\BusinessInsuranceType;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarPlan;
use App\Models\CarTypeInsurance;
use App\Models\ClaimHistory;
use App\Models\CurrencyType;
use App\Models\Emirate;
use App\Models\HealthCoverFor;
use App\Models\HomeAccomodationType;
use App\Models\HomePossessionType;
use App\Models\InsuranceProvider;
use App\Models\LeadStatus;
use App\Models\LifeChildren;
use App\Models\LifeInsuranceTenure;
use App\Models\LifeNumberOfYears;
use App\Models\LifePurposeOfInsurance;
use App\Models\MartialStatus;
use App\Models\Nationality;
use App\Models\PaymentStatus;
use App\Models\Regions;
use App\Models\TravelCoverFor;
use App\Models\UAELicenseHeldFor;
use App\Models\User;
use App\Models\VehicleType;
use App\Models\YearOfManufacture;
use App\Models\CarAddOn;
use App\Models\Team;
use DB;
use Illuminate\Support\Facades\Auth;

class DropdownSourceService extends BaseService
{
    public function getCustomDropdownList($type, $id)
    {
        $data = '';
        switch ($type) {
            case 'team_managers':
                $data = DB::select("select u.id, u.name as names from users u
                                    inner join user_team tm on tm.manager_id is null
                                    where tm.team_id = " . $id . " and tm.user_id = u.id");
                break;
            case 'team_users':
                $data = DB::select("select u.id, u.name from users u
                inner join user_team ut on ut.user_id = u.id
                where ut.team_id =" . $id);
                break;
            default:
                break;
        }
        return $data;
    }

    public function getOnlySelectedItemName($type, $id)
    {
        $data = '';
        switch ($type) {
            case 'team_managers':
                $data = DB::select("select group_concat(u.name) as names from users u
                                    inner join user_team tm on tm.manager_id is null
                                    where tm.team_id = " . $id . " and tm.user_id = u.id");
                break;
            case 'team_users':
                $data = DB::select("select group_concat(u.name) as names from users u
                inner join user_team ut on ut.user_id = u.id
                where ut.team_id =" . $id);
                break;
            default:
                break;
        }
        return $data;
    }

    public function getDropdownSource($type)
    {
        $advisorType = strtoupper(explode('/', $_SERVER["REQUEST_URI"])[2]);
        $data = '';
        switch ($type) {
            case 'parent_team_id':
                $data = Team::whereNull('parent_team_id')->get();
                break;
            case 'marital_status_id':
                $data = MartialStatus::select('id', 'text')->where("is_active", true)->get();
                break;
            case 'nationality_id':
                $data = Nationality::select('id', 'text')->where("is_active", true)->get();
                break;
            case 'quote_status_id':
                $data = DB::table('quote_status')->select('id', 'text')->orderBy('sort_order', 'asc')
                    ->whereNotIn('text', [
                        'AML Screening Cleared', 'Draft', 'Cancelled', 'AML Screening Failed', 'Transaction Declined', 'Policy Issued', 'Policy Invoiced',
                        'Completed', 'Pending', 'Rejected', 'Issued', 'Approved', 'Approval required', 'Resubmit for approval'
                    ])
                    ->get();
                break;
            case 'cover_for_id':
                $data = HealthCoverFor::select('id', 'text')->where("is_active", true)->get();
                break;
            case 'emirate_of_your_visa_id':
                $data = Emirate::select('id', 'text')->where("is_active", true)->get();
                break;
            case 'team_users':
                $data = User::select('id', 'name')->get();
                break;
            case 'car_make_id':
                $data = CarMake::select('code as id', 'text')->where("is_active", true)->get();
                break;
            case 'car_model_id':
                $data = [];
                break;
            case 'region_cover_for_id':
                $data = Regions::select('id', 'text')->where("is_active", true)->get();
                break;
            case 'travel_cover_for_id':
                $data = TravelCoverFor::select('id', 'text')->where("is_active", true)->get();
                break;
            case 'sum_insured_currency_id':
                $data = CurrencyType::select('id', 'text')->where("is_active", true)->get();
                break;
            case 'lead_type_id':
                $data = DB::table('health_lead_type')->select('id', 'text')->where("is_active", true)->get();
                break;
            case 'purpose_of_insurance_id':
                $data = LifePurposeOfInsurance::select('id', 'text')->where("is_active", true)->get();
                break;
            case 'children_id':
                $data = LifeChildren::select('id', 'text')->where("is_active", true)->get();
                break;
            case 'tenure_of_insurance_id':
                $data = LifeInsuranceTenure::select('id', 'text')->where("is_active", true)->get();
                break;
            case 'number_of_years_id':
                $data = LifeNumberOfYears::select('id', 'text')->where("is_active", true)->get();
                break;
            case 'year_of_manufacture':
                $data = YearOfManufacture::select('text as id', 'text')->get();
                break;
            case 'advisor_id':
                if (Auth::user()->isRenewalUser() || Auth::user()->isRenewalManager() || Auth::user()->isRenewalAdvisor()) {
                    $advisorType = $advisorType . '_RENEWAL';
                }
                if (Auth::user()->isNewBusinessManager() || Auth::user()->isNewBusinessAdvisor()) {
                    $advisorType = $advisorType . '_NEW_BUSINESS_';
                }
                if (strtolower($advisorType) == strtolower(quoteTypeCode::Health)) {
                    $data = DB::table('users as u')->select('u.id', DB::raw("CONCAT(u.name,' - ',r.name) AS name"))
                        ->join('model_has_roles as mhr', 'mhr.model_id', '=', 'u.id')
                        ->join('roles as r', 'mhr.role_id', '=', 'r.id')
                        ->whereIn('r.name', ['RM_ADVISOR', 'EBP_ADVISOR', 'HEALTH_WCU_ADVISOR'])->get();
                } else if (strtolower($advisorType) == strtolower(quoteTypeCode::Business)) {
                    $data = DB::table('users as u')->select('u.id', DB::raw("CONCAT(u.name,' - ',r.name) AS name"))
                        ->join('model_has_roles as mhr', 'mhr.model_id', '=', 'u.id')
                        ->join('roles as r', 'mhr.role_id', '=', 'r.id')
                        ->whereIn('r.name', ['CORPLINE_ADVISOR'])->get();
                } else {
                    if (!empty($advisorType)) {
                        $data = DB::table('users as u')->select('u.id',  DB::raw("CONCAT(u.name,' - ',r.name) AS name"))
                            ->join('model_has_roles as mhr', 'mhr.model_id', '=', 'u.id')
                            ->join('roles as r', 'mhr.role_id', '=', 'r.id')
                            ->whereIn('r.name',[$advisorType . '_ADVISOR', $advisorType . '_NEW_BUSINESS_ADVISOR', $advisorType . '_RENEWAL_ADVISOR'])->get();
                    } else {
                        $data = User::select('id', 'name')->get();
                    }
                }
                break;
            case 'iam_possesion_type_id':
                $data = HomePossessionType::select('id', 'text')->where("is_active", true)->get();
                break;
            case 'ilivein_accommodation_type_id':
                $data = HomeAccomodationType::select('id', 'text')->where("is_active", true)->get();
                break;
            case 'business_type_of_insurance_id':
                $data = BusinessInsuranceType::select('id', 'text')->where("is_active", true)->get();
                break;
            case 'uae_license_held_for_id':
                $data = UAELicenseHeldFor::select('id', 'text')->where("is_active", true)->get();
                break;
            case 'emirate_of_registration_id':
                $data = Emirate::select('id', 'text')->where("is_active", true)->get();
                break;
            case 'currently_insured_with':
                $data = InsuranceProvider::select('text as id', 'text')->where('is_active', 1)->get();
                break;
            case 'car_type_insurance_id':
                $data = CarTypeInsurance::select('id', 'text')->where("is_active", true)->get();
                break;
            case 'claim_history_id':
                $data = ClaimHistory::select('id', 'text')->where("is_active", true)->get();
                break;
            case 'plan_id':
                $data = CarPlan::select('id', 'text')->where("is_active", true)->get();
                break;
            case 'provider_id':
                $data = InsuranceProvider::select('id', 'text')->where('is_active', true)->get();
                break;
            case 'vehicle_type_id':
                $data = VehicleType::select('id', 'text')->where("is_active", true)->get();
                break;
            case 'car_plan_provider_id':
                $data = InsuranceProvider::select('id', 'text')->where("is_active", true)->get();
                break;
            case 'team_managers':
                $data = User::select('users.id', 'users.name')
                    ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->where('roles.name', '=', 'MANAGER')->get();
                break;
            case 'payment_status_id':
                $data = PaymentStatus::select('id', 'text')->where("is_active", true)->get();
                break;
            case 'addon_id':
                $data = CarAddOn::select('id', 'text')->get();
                break;
            case 'code':
                $data = DB::table("car_plan_coverage")->distinct()->select('car_plan_coverage.code as id', 'car_plan_coverage.code as text')->whereNotNull('code')->whereNotNull('text')->get();
                break;
            case 'currently_located_in_id':
                $data = DB::table("currently_located_in")->select('id', 'text')->where("is_active", true)->orderBy('sort_order', 'asc')->get();
                break;
            case 'destination_id':
                $data = DB::table("nationality")->select('id', 'country_name as text')->where("is_active", true)->orderBy('sort_order', 'asc')->get();
                break;
            case 'salary_band_id':
                $data = DB::table("salary_band")->select('id', 'text')->where("is_active", true)->orderBy('sort_order', 'asc')->get();
                break;
            case 'member_category_id':
                $data = DB::table("member_category")->select('id', 'text')->where("is_active", true)->orderBy('sort_order', 'asc')->get();
                break;
            case 'trim':
                $data = [];
                break;
            default:
                break;
        }
        return $data;
    }

    public function getCarModel($makeCode)
    {
        return CarModel::select('id', 'text')->where('code', $makeCode)->get();
    }
}
