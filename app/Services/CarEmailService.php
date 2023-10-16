<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CarPlanType;
use App\Enums\UserStatusEnum;
use App\Models\ApplicationStorage;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarModelDetail;
use App\Models\User;
use Carbon\Carbon;

class CarEmailService extends BaseService
{
    public function buildNoPlansEmailData($carQuote, $previousAdvisor, $tierRId)
    {
        $advisor = User::where('id', $carQuote->advisor_id)->first();

        $emailData = $this->buildCommonEmailData($carQuote, $advisor, $previousAdvisor);
        $emailData->isReAssignment = ! empty($previousAdvisor);
        if ($carQuote->tier_id == $tierRId) {
            $emailData->isRenewal = true;
            $emailData->policyNumber = $carQuote->previous_quote_policy_number;
            $carbonDate = Carbon::parse($carQuote->previous_policy_expiry_date)->format('jS F Y');
            $emailData->renewalDueDate = $carbonDate;
        }

        return $emailData;
    }

    public function buildPlansEmailData($carQuote, $plans, $previousAdvisor, $tierRId)
    {
        $advisor = User::where('id', $carQuote->advisor_id)->first();
        $insurerPlans = [];
        foreach ($plans as $plan) {
            $insurerPlans[] = [
                'carValue' => $plan->carValue,
                'excessAed' => $plan->excess,
                'repairType' => $this->getUpdateRepairType($plan->repairType, $plan->providerCode),
                'discountPremium' => $plan->discountPremium,
                'planName' => $plan->name,
                'providerCode' => strtolower($plan->providerCode),
                'benefits' => $this->getPlanBenefits($plan),
                'buyNowLink' => $this->getPlanBuyNowLink($plan, $carQuote->uuid),
            ];
        }
        $emailData = $this->buildCommonEmailData($carQuote, $advisor, $previousAdvisor);
        $emailData->plans = $insurerPlans;
        $emailData->totalPlans = count($insurerPlans);
        $emailData->isReAssignment = ! empty($previousAdvisor);

        if ($carQuote->tier_id == $tierRId) {
            $emailData->isRenewal = true;
            $emailData->policyNumber = $carQuote->previous_quote_policy_number;
            $carbonDate = Carbon::parse($carQuote->previous_policy_expiry_date)->format('jS F Y');
            $emailData->renewalDueDate = $carbonDate;

        }

        return $emailData;
    }

    private function buildCommonEmailData($carQuote, $advisor, $previousAdvisor)
    {
        $documentUrl = $this->getAppStorageValueByKey(ApplicationStorageEnums::LMS_INTRO_EMAIL_ATTACHMENT_URL);

        $emailData = (object) [
            'clientFullName' => $carQuote->first_name.' '.$carQuote->last_name,
            'customerName' => $carQuote->first_name.' '.$carQuote->last_name,
            'customerEmail' => $carQuote->email,
            'mobilePhone' => $advisor->mobile_no,
            'whatsAppNumber' => ! empty($advisor->mobile_no) ? str_replace('+', '', $advisor->mobile_no) : '',
            'landLine' => $advisor->landline_no,
            'advisorEmail' => $advisor->email,
            'advisorName' => $advisor->name,
            'documentUrl' => [$documentUrl],
            'carQuoteId' => $carQuote->code,
            'vehicleName' => $this->getVehicleName($carQuote),
            'currentInsurer' => $carQuote->currently_insured_with,
            'quoteLink' => config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$carQuote->uuid,
            'assignmentType' => $this->getAssignmentTypeText($carQuote->assignment_type),
            'previousAdvisorName' => ! empty($previousAdvisor) ? $previousAdvisor->name : '',
            'previousAdvisorStatus' => ! empty($previousAdvisor) ? UserStatusEnum::getUserStatusText($previousAdvisor->status) : '',
            'isReAssignment' => ! empty($previousAdvisor),
        ];

        return $emailData;
    }

    public function getAssignmentTypeText($assignmentType)
    {
        $assignmentText = '';
        switch ($assignmentType) {
            case 1:
                $assignmentText = 'System Assigned';
                break;
            case 2:
                $assignmentText = 'System ReAssigned';
                break;
            case 3:
                $assignmentText = 'Manual Assigned';
                break;
            case 4:
                $assignmentText = 'Manual ReAssigned';
                break;
            default:
                break;
        }

        return $assignmentText;
    }

    public function getPlanBenefits($plan)
    {
        $planAddons = [];
        foreach ($plan->addons as $addon) {
            $planAddons[] = [
                'value' => $addon->text,
            ];
        }

        return $planAddons;
    }

    public function getPlanBuyNowLink($plan, $uuid)
    {
        $buyNowLink = config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$uuid.'/payment/providerCode='.$plan->providerCode.'%planId='.$plan->id;

        return $buyNowLink;
    }

    public function getAppStorageValueByKey($keyName)
    {
        $query = ApplicationStorage::select('value')
            ->where('key_name', $keyName)
            ->first();

        if (! $query) {
            return false;
        }

        return $query->value;
    }

    public function getVehicleName($lead)
    {

        $vehicleName = '';
        if ($lead->car_make_id != null) {

            $carMake = CarMake::find($lead->car_make_id);

            if ($carMake) {
                $vehicleName = $carMake->text;
            }
        }

        if ($lead->car_model_id != null) {
            $carModel = CarModel::find($lead->car_model_id);

            if ($carModel) {
                // Update $vehicleName with car model text
                $vehicleName .= ' '.$carModel->text;
            }
        }
        if ($lead->car_model_detail_id != null) {
            $carModelDetail = CarModelDetail::find($lead->car_model_detail_id);

            if ($carModelDetail) {
                // Update $vehicleName with car model detail text
                $vehicleName .= ' '.$carModelDetail->text;
            }
        }

        return $vehicleName;
    }

    private function getUpdateRepairType($repairType, $providerCode)
    {
        $coreInsurer = ['AXA', 'OIC', 'TM', 'QIC', 'RSA'];
        $halfLiveInsurer = ['SI', 'OI', 'Watania', 'DNIRC', 'NIA', 'UI', 'IHC', 'NT'];
        if ($repairType == CarPlanType::COMP) {
            if (in_array($providerCode, $coreInsurer)) {
                $result = 'Premium workshop';
            } elseif (in_array($providerCode, $halfLiveInsurer)) {
                $result = 'Non-Agency workshop';
            } else {
                $result = 'NON-AGENCY';
            }
        } else {
            $result = $repairType;
        }

        return $result;
    }
}
