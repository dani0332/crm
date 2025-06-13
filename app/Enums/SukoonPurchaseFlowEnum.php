<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class SukoonPurchaseFlowEnum extends Enum
{

    const STEPS_NAME = [
        self::STEP_INIT => 'init',
        self::STEP_LOGIN => 'login',
        self::STEP_SUBMIT_PERSONAL_DETAIL => 'submitPersonalDetail',
        self::STEP_SUBMIT_PLAN => 'submitPlan',
        self::STEP_REVIEW_SUBMITED_DATA => 'reviewSubmittedData',
        self::STEP_CONFIRM_SUBMITED_DATA => 'confirmSubmittedData',
        self::STEP_INITIATE_PAYMENT_PROCESS => 'initiatePaymentProcess',
        self::STEP_COMPLETE_INVOICE_PAYMENT => 'completeInvoicePayment',
        self::STEP_GET_POLICY_SCHEDULE_COI => 'getPolicyScheduleCoi',
        self::STEP_GET_CUSTOMER_TAX_INVOICE => 'getCustomerTaxInvoice',
        self::STEP_LIST_GENERATED_DOCUMENT => 'listGeneratedDocument',
        self::STEP_DOWNLOAD_DOCUMENT => 'downloadDocument',
        self::GET_VIEW_QUOTE_POLICY => 'viewQuotePolicy',
        // self::STEP_GET_FORM => 'getForm', // GET, Skiped
        // self::STEP_PRE_REVIEW_SUBMITED_DATA => 'preReviewSubmittedData', // GET, Skiped
        // self::STEP_LIST_PAYMENT_GATEWAYS => 'listPaymentGateways', // GET, Skiped
    ];

    const STEP_INIT = 1;
    const STEP_LOGIN = 2;
    const STEP_SUBMIT_PERSONAL_DETAIL = 4;
    const STEP_SUBMIT_PLAN = 6;
    const STEP_REVIEW_SUBMITED_DATA = 7;
    const STEP_CONFIRM_SUBMITED_DATA = 8;
    const STEP_INITIATE_PAYMENT_PROCESS = 10;
    const STEP_COMPLETE_INVOICE_PAYMENT = 11;
    const STEP_GET_POLICY_SCHEDULE_COI = 12;
    const STEP_GET_CUSTOMER_TAX_INVOICE = 13;
    const STEP_LIST_GENERATED_DOCUMENT = 14;
    const STEP_DOWNLOAD_DOCUMENT = 15;
    const GET_VIEW_QUOTE_POLICY = 16;
    // const STEP_GET_FORM = 3; // GET, Skiped
    // const STEP_PRE_REVIEW_SUBMITED_DATA = 5; // GET, Skiped
    // const STEP_LIST_PAYMENT_GATEWAYS = 9; // GET, Skiped


    public static function getName(int $stepNumber): string
    {
        return self::STEPS_NAME[$stepNumber] ?? "";
    }

    public static function getStepNumber(string $stepName): int
    {
        return array_search($stepName, self::STEPS_NAME); 
    }

    public static function getNextStep(int $currentStep): int
    {
        $recentAvailableSteps = self::findCurrentAvailableStep($currentStep);
        return self::findNextAvailableStep($recentAvailableSteps);
    }

    public static function findCurrentAvailableStep(int $currentStep): int
    {
        $keys = array_keys(self::STEPS_NAME);
        $recentAvailableSteps = array_filter($keys, fn($k) => $k <= $currentStep);
        return $recentAvailableSteps ? max($recentAvailableSteps) : min($keys);
    }

    public static function findNextAvailableStep(int $currentStep): int
    {
        $keys = array_keys(self::STEPS_NAME);
        $nextAvailableSteps = array_filter($keys, fn($k) => $k > $currentStep);
        return $nextAvailableSteps ? min($nextAvailableSteps) : $currentStep;
    }

}
