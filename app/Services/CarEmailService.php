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
    protected $sendEmailCustomerService;

    public function __construct(SendEmailCustomerService $sendEmailCustomerService)
    {
        $this->sendEmailCustomerService = $sendEmailCustomerService;
    }

    public function sendCarOCBIntroEmail($plans, $lead, $tierR, $previousAdvisorId)
    {
        $plans = $this->executePlansSelectionLogic($plans);

        // Determine the email template ID
        $emailTemplateId = $this->getEmailTemplateId($lead, $plans, $tierR);

        // Build email data
        $emailData = $this->buildEmailData($lead, $plans, $previousAdvisorId, $tierR->id);

        $responseCode = $this->sendEmailCustomerService->sendLMSIntroEmail($emailTemplateId, $emailData, 'lms-intro-email');

        return $responseCode;
    }
    private function buildNoPlansEmailData($carQuote, $previousAdvisor, $tierRId)
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

    private function buildPlansEmailData($carQuote, $plans, $previousAdvisor, $tierRId)
    {
        $advisor = User::where('id', $carQuote->advisor_id)->first();
        $insurerPlans = [];
        foreach ($plans as $plan) {
            $insurerPlans[] = [
                'carValue' => $plan->repairType == CarPlanType::TPL ? 'N/A' : (empty($plan->carValue) ? 'N/A' : $plan->carValue),
                'excessAed' => empty($plan->excess) ? 'N/A' : $plan->excess,
                'repairType' => $this->getUpdateRepairType($plan->repairType, $plan->providerCode),
                'discountPremium' => ! empty($plan->discountPremium) ? number_format($plan->discountPremium, 2) : '',
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
            'yearOfManufacture' => $carQuote->year_of_manufacture,
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

    private function getAssignmentTypeText($assignmentType)
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

    private function getPlanBenefits($plan)
    {
        $planAddons = []; // Initialize an empty array to store plan addons.

        foreach ($plan->addons as $addon) {
            // Initialize a flag to determine if this addon should be included.
            $shouldInclude = array_reduce($addon->carAddonOption, function ($carry, $option) {
                // Check if any option is selected; return true if found.
                return $carry || $option->isSelected;
            }, false);

            // If at least one option was selected, include this addon in the benefits.
            if ($shouldInclude) {
                $planAddons[] = [
                    'value' => $addon->text,
                ];
            }
        }

        return $planAddons;
    }

    private function getPlanBuyNowLink($plan, $uuid)
    {
        $buyNowLink = config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$uuid.'/payment/?planId=' . $plan->id. '&providerCode='.$plan->providerCode;
        return $buyNowLink;
    }

    private function getAppStorageValueByKey($keyName)
    {
        $query = ApplicationStorage::select('value')
            ->where('key_name', $keyName)
            ->first();

        if (! $query) {
            return false;
        }

        return $query->value;
    }

    private function getVehicleName($lead)
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

    private function getEmailTemplateId($lead, $plans, $tierR)
    {
        if (count($plans) == 0) {
            // No plans with available ratings, send a specific email template
            return $lead->tier_id == $tierR->id ? 492 : 494;
        } else {
            // Plans with available ratings exist, send a different email template
            return $lead->tier_id == $tierR->id ? 491 : 493;
        }
    }

    private function buildEmailData($lead, $plans, $previousAdvisor, $tierRId)
    {
        if (count($plans) == 0) {
            // No plans with available ratings, build email data for the specific case
            return $this->buildNoPlansEmailData($lead, $previousAdvisor, $tierRId);
        } else {
            // Plans with available ratings exist, build email data for the different case
            return $this->buildPlansEmailData($lead, $plans, $previousAdvisor, $tierRId);
        }
    }

    private function executePlansSelectionLogic(array $plans): array
    {
        // Sort plans from lowest to highest by discount premium
        usort($plans, function ($a, $b) {
            return $a->discountPremium <=> $b->discountPremium;
        });

        // Check if there are any 'Comp' plans
        $compPlans = array_filter($plans, function ($plan) {
            // Check if the 'repairType' and 'isRatingAvailable' properties exist and meet the conditions.
            return property_exists($plan, 'repairType') &&
                   property_exists($plan, 'isRatingAvailable') &&
                   ($plan->repairType === CarPlanType::COMP || $plan->repairType === CarPlanType::AGENCY) &&
                   $plan->isRatingAvailable === true;
        });

        if (count($compPlans) > 0) {
            // If 'Comp' plans exist, return the top 6 'Comp' plans
            $top6Plans = array_slice($compPlans, 0, 6);
        } else {
            // If there are no 'Comp' plans, return the top 6 plans
            $filteredPlans = array_filter($plans, function ($plan) {
                return $plan->repairType == CarPlanType::TPL && $plan->isRatingAvailable == true;
            });

            $top6Plans = array_slice($filteredPlans, 0, 6);
        }

        // return $top6Plans if $top6Plans is not empty otherwise return $plans
        return ! empty($top6Plans) ? $top6Plans : [];
    }
}
