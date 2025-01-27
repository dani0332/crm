<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CarPlanType;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Enums\UserStatusEnum;
use App\Models\ApplicationStorage;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarModelDetail;
use App\Models\User;
use App\Traits\CentralTrait;
use Carbon\Carbon;

class BikeEmailService extends BaseService
{
    use CentralTrait;

    protected $sendEmailCustomerService;

    public function __construct(SendEmailCustomerService $sendEmailCustomerService)
    {
        $this->sendEmailCustomerService = $sendEmailCustomerService;
    }

    public function sendBikeOCBIntroEmail($plans, $lead, $tierR, $previousAdvisorId, $bikeQuoteService)
    {
        $plans = $this->executePlansSelectionLogic($plans);

        // Determine the email template ID
        $emailTemplateId = $this->getEmailTemplateId($lead, $plans, $tierR);

        // Build email data
        $emailData = $this->buildEmailData($lead, $plans, $previousAdvisorId, $tierR->id);
        $quotePlansCount = is_countable($plans) ? count($plans) : 0;
        if ($quotePlansCount > 0) {
            info('Inside plans of count: '.$lead->uuid.'    ');
            $pdfData = [
                'plan_ids' => collect($plans)->take(5)->pluck('id')->toArray(),
                'quote_uuid' => $lead->uuid,
            ];
            $pdf = $bikeQuoteService->exportPlansPdf(quoteTypeCode::Bike, $pdfData, json_decode(json_encode(['quotes' => ['plans' => $plans], 'isDataSorted' => true])));
            if (isset($pdf['error'])) {
                info('Failed to generate PDF for UUID in bike email service: '.$lead->uuid.' Error: '.$pdf['error']);
            } else {
                $emailData->pdfAttachment = (object) $pdf;
                info('attaching pdf: '.$lead->uuid.'    ');
            }
        }

        $responseCode = $this->sendEmailCustomerService->sendLMSIntroEmail($emailTemplateId, $emailData, 'lms-intro-email', QuoteTypes::BIKE);

        return $responseCode;
    }

    private function buildNoPlansEmailData($lead, $previousAdvisor, $tierRId)
    {
        $advisor = User::where('id', $lead->advisor_id)->first();

        $emailData = $this->buildCommonEmailData($lead, $advisor, $previousAdvisor);
        $emailData->isReAssignment = ! empty($previousAdvisor);
        if ($lead->tier_id == $tierRId) {
            $emailData->isRenewal = true;
            $emailData->policyNumber = $lead->previous_quote_policy_number;
            $carbonDate = Carbon::parse($lead->previous_policy_expiry_date)->format('jS F Y');
            $emailData->renewalDueDate = $carbonDate;
        }

        return $emailData;
    }

    private function buildPlansEmailData($lead, $plans, $previousAdvisor, $tierRId)
    {
        $advisor = User::where('id', $lead->advisor_id)->first();
        $insurerPlans = [];
        foreach ($plans as $plan) {
            $insurerPlans[] = [
                'bikeValue' => $plan->repairType == CarPlanType::TPL ? 'N/A' : (empty($plan->bikeValue) ? 'N/A' : $plan->bikeValue),
                'excessAed' => empty($plan->excess) ? 'N/A' : $plan->excess,
                'repairType' => $this->getUpdateRepairType($plan->repairType, $plan->providerCode),
                'discountPremium' => ! empty($plan->discountPremium) ? number_format($plan->discountPremium, 2) : '',
                'planName' => $plan->name,
                'providerCode' => strtolower($plan->providerCode),
                'benefits' => $this->getPlanBenefits($plan),
                'buyNowLink' => $this->getEcomQuoteLink(QuoteTypes::BIKE, $lead->uuid, $plan),
                'isRenewal' => ($plan->isRenewal ?? false),
            ];
        }

        $emailData = $this->buildCommonEmailData($lead, $advisor, $previousAdvisor);
        $emailData->plans = $insurerPlans;
        $emailData->totalPlans = count($insurerPlans);
        $emailData->isReAssignment = ! empty($previousAdvisor);

        if ($lead->tier_id == $tierRId) {
            $emailData->isRenewal = true;
            $emailData->policyNumber = $lead->previous_quote_policy_number;
            $carbonDate = Carbon::parse($lead->previous_policy_expiry_date)->format('jS F Y');
            $emailData->renewalDueDate = $carbonDate;
        }

        return $emailData;
    }

    private function buildCommonEmailData($lead, $advisor, $previousAdvisor)
    {
        $documentUrl = $this->getAppStorageValueByKey(ApplicationStorageEnums::LMS_INTRO_EMAIL_ATTACHMENT_URL);
        // $whatsAppNumber = ! empty($advisor->mobile_no) ? str_replace(['+', ' ', '0'], '', $advisor->mobile_no) : '';
        // $whatsAppNumber = '971'.ltrim($whatsAppNumber, '0');
        $whatsAppNumber = ! empty($advisor->mobile_no) ? formatMobileNo($advisor->mobile_no) : '';
        $bikeQuoteId = $lead->code;
        $emailData = (object) [
            'clientFullName' => $lead->first_name.' '.$lead->last_name,
            'customerName' => $lead->first_name.' '.$lead->last_name,
            'customerEmail' => $lead->email,
            'mobilePhone' => (! empty($advisor->mobile_no) ? formatMobileNoDisplay($advisor->mobile_no) : ''),
            'mobileNoWithoutSpaces' => (! empty($advisor->mobile_no) ? removeSpaces(formatMobileNoDisplay($advisor->mobile_no)) : ''),
            'whatsAppNumber' => $whatsAppNumber,
            'landLine' => (! empty($advisor->landline_no) ? formatLandlineDisplay($advisor->landline_no) : ''),
            'landlineNoWithoutSpaces' => (! empty($advisor->landline_no) ? removeSpaces(formatLandlineDisplay($advisor->landline_no)) : ''),
            'advisorEmail' => $advisor->email,
            'advisorName' => $advisor->name,
            'documentUrl' => $documentUrl ? [$documentUrl] : [],
            'bikeQuoteId' => $bikeQuoteId,
            'yearOfManufacture' => $lead->bikeQuote->year_of_manufacture,
            'vehicleName' => $this->getVehicleName($lead),
            'currentInsurer' => $lead->bikeQuote->currently_insured_with,
            'quoteLink' => $this->getEcomQuoteLink(QuoteTypes::BIKE, $lead->uuid),
            'assignmentType' => getAssignmentTypeText($lead->assignment_type),
            'previousAdvisorName' => ! empty($previousAdvisor) ? $previousAdvisor->name : '',
            'previousAdvisorStatus' => ! empty($previousAdvisor) ? UserStatusEnum::getUserStatusText($previousAdvisor->status) : '',
            'isReAssignment' => ! empty($previousAdvisor),
        ];

        return $emailData;
    }

    private function getPlanBenefits($plan)
    {
        $planAddons = []; // Initialize an empty array to store plan addons.

        foreach ($plan->addons as $addon) {
            // Initialize a flag to determine if this addon should be included.
            $shouldInclude = array_reduce($addon->bikeAddonOption, function ($carry, $option) {
                // Check if any option is selected; return true if found.
                return $carry || $option->isSelected;
            }, false);

            // If at least one option was selected, include this addon in the benefits.
            if ($shouldInclude && $addon->text !== 'Priority repair, 12 free bike washes, VIP lane for RTA testing and more with AG bikes') {
                $planAddons[] = [
                    'value' => $addon->text,
                ];
            }
        }

        return $planAddons;
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
        if ($lead->bikeQuote->make_id != null) {
            $bikeMake = CarMake::find($lead->bikeQuote->make_id);

            if ($bikeMake) {
                $vehicleName = $bikeMake->text;
            }
        }

        if ($lead->bikeQuote->model_id != null) {
            $bikeModel = CarModel::find($lead->bikeQuote->model_id);

            if ($bikeModel) {
                // Update $vehicleName with car model text
                $vehicleName .= ' '.$bikeModel->text;
            }
        }
        if ($lead->bikeQuote->model_detail_id != null) {
            $bikeModelDetail = CarModelDetail::find($lead->bikeQuote->model_detail_id);

            if ($bikeModelDetail) {
                // Update $vehicleName with car model detail text
                $vehicleName .= ' '.$bikeModelDetail->text;
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
        info('Getting Template Id for Bike OCB');
        if (count($plans) == 0) {
            info('Zero Plan Template Id for Bike OCB against UUID: '.$lead->uuid.'and Template Id: '.$lead->tier_id == $tierR->id ? 492 : 626);

            // No plans with available ratings, send a specific email template
            return $lead->tier_id == $tierR->id ? 492 : 723;
        } elseif (count($plans) == 1) {
            info('One Plan Template Id for Bike OCB against UUID: '.$lead->uuid.'and Template Id: '.$lead->tier_id == $tierR->id ? 492 : 627);

            return $lead->tier_id == $tierR->id ? 492 : 724;
        } else {
            // Plans with available ratings exist, send a different email template
            info('Multiple Plan Template Id for Bike OCB against UUID: '.$lead->uuid.'and Template Id: '.$lead->tier_id == $tierR->id ? 491 : 628);

            return $lead->tier_id == $tierR->id ? 491 : 725;
        }
    }

    public function buildEmailData($lead, $plans, $previousAdvisor, $tierRId)
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
        $top5Plans = [];
        $remainingSlots = 5;
        // first get agency plans sorted on discount premium (max 3)
        $agencyPlans = array_filter($plans, function ($plan) {
            return property_exists($plan, 'repairType') &&
                property_exists($plan, 'isRatingAvailable') &&
                ($plan->repairType === CarPlanType::AGENCY) &&
                $plan->isRatingAvailable === true;
        });
        usort($agencyPlans, function ($a, $b) {
            return $a->discountPremium <=> $b->discountPremium;
        });

        // count of agency plans to use for sorting
        $agencyPlansCount = count($agencyPlans);
        if ($agencyPlansCount > 1) {
            $agencyPlans = array_slice($agencyPlans, 0, 1);
            $agencyPlansCount = count($agencyPlans);
        } elseif ($agencyPlansCount == 0) {
            $agencyPlans = [];
            $agencyPlansCount = count($agencyPlans);
        } else {
            $agencyPlans = array_slice($agencyPlans, 0, $agencyPlansCount);
        }
        $remainingSlots -= $agencyPlansCount;

        $top5Plans = array_merge($top5Plans, $agencyPlans);

        // second get comp plans sorted on discount premium
        $compPlans = array_filter($plans, function ($plan) {
            return property_exists($plan, 'repairType') &&
                property_exists($plan, 'isRatingAvailable') &&
                ($plan->repairType === CarPlanType::COMP) &&
                $plan->isRatingAvailable === true;
        });
        usort($compPlans, function ($a, $b) {
            return $a->discountPremium <=> $b->discountPremium;
        });

        // count of comp plans to use for sorting
        $compPlansCount = count($compPlans);
        if ($compPlansCount > 3) {
            $compPlans = array_slice($compPlans, 0, 3);
            $compPlansCount = count($compPlans);
        } elseif ($compPlansCount == 0) {
            $compPlans = [];
            $compPlansCount = count($compPlans);
        } else {
            $compPlans = array_slice($compPlans, 0, $compPlansCount);
        }

        $top5Plans = array_merge($top5Plans, array_slice($compPlans, 0, $remainingSlots));
        $remainingSlots -= $compPlansCount;

        // third get tpl plans sorted on discount premium
        $tplPlans = array_filter($plans, function ($plan) {
            return property_exists($plan, 'repairType') &&
                property_exists($plan, 'isRatingAvailable') &&
                ($plan->repairType === CarPlanType::TPL) &&
                $plan->isRatingAvailable === true;
        });
        usort($tplPlans, function ($a, $b) {
            return $a->discountPremium <=> $b->discountPremium;
        });

        $tplPlansCount = count($tplPlans);

        $top5Plans = array_merge($top5Plans, array_slice($tplPlans, 0, $remainingSlots));
        $remainingSlots -= count(array_slice($tplPlans, 0, $remainingSlots));

        return ! empty($top5Plans) ? $top5Plans : [];
    }
}
