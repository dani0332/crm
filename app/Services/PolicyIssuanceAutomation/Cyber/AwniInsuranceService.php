<?php

namespace App\Services\PolicyIssuanceAutomation\Cyber;

use App\Enums\ApplicationStorageEnums;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Interfaces\PolicyIssuanceInterface;
use App\Services\ApplicationStorageService;

class AwniInsuranceService implements PolicyIssuanceInterface
{
    private $className = 'awniInsuranceService';
    private readonly $baseUrl;
    private mixed $authParam;

    public const TYPE = quoteTypeCode::Cyber;
    public const TYPE_ID = QuoteTypeId::Cyber;

    public mixed $vat = null;
    public $policyIssuance = null;
    public $currentInsurerApiStatus = null;


    public function __construct()
    {
        $this->baseUrl = config('constants.LIVA_API_BASE_URL');
        $this->className = 'awniInsuranceService';
    }

    /**
     * 
     *
     * @return boolean
     */
    public function isPolicyIssuanceAutomationEnabled()
    {
        return app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::ENABLE_AWNI_CYBER_POLICY_ISSUANCE);
    }

    /**
     * 
     *
     * @return boolean
     */
    public function isPolicyIssuanceAutomationRetryEnabledForTimeout(): bool
    {
        return (bool) app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::ENABLE_RETRY_TIMEOUT_LIVA_CAR_POLICY_ISSUANCE);
    }
}