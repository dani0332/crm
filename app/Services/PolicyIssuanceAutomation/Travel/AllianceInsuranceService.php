<?php

namespace App\Services\PolicyIssuanceAutomation\Travel;

use App\Enums\TravelQuoteEnum;
use App\Interfaces\PolicyIssuanceInterface;
use Illuminate\Support\Facades\Http;

class AllianceInsuranceService implements PolicyIssuanceInterface
{
    private $baseUrl;
    private $agencyId;
    private $agencyCode;

    public function __construct()
    {
        $this->baseUrl = config('constants.ALLIANCE_API_BASE_URL');
        $this->agencyId = config('constants.ALLIANCE_AGENCY_ID');
        $this->agencyCode = config('constants.ALLIANCE_AGENCY_CODE');
    }
    public function handle($process)
    {
        $response = ['status' => false, 'error' => null, 'message' => null];

        $travelType = TravelQuoteEnum::IN_BOUND;
        $quote = $process->model;
        $directionCode = $quote->direction_code;
        if ($directionCode === TravelQuoteEnum::TRAVEL_UAE_OUTBOUND) {
            $travelType = TravelQuoteEnum::OUT_BOUND;
        }

        info(__CLASS__.' fn:'.__FUNCTION__.' started ');

        $this->issuePolicyAndfillPolicyDetails($quote, $travelType);
        $this->policyPurchase($quote , $travelType);
        $this->fetchAndUploadDocument($quote);

        return $response;
    }

    public function issuePolicyAndfillPolicyDetails($quote, $travelType)
    {
        $endpoint = $this->baseUrl.'/v1/quote/'.$travelType.'/finalise';
        $payLoad = [
            'agency_id' => $this->agencyId,
            'agency_code' => $this->agencyCode,
            'quote_id' => $quote->id,
            'scheme_id' => '',
            'title_customer' => '',
            'first_name_customer' => '',
            'last_name_customer' => '',
            'title_traveller' => ['Mr'],
            'first_name_traveller' => ['Muhammad'],
            'last_name_traveller' => ['Ali'],
            'dob' => ['1990-01-01'],
            'passport_number' => ['1122334455'],
            'nationality_traveller' => [12],
            'email' => 'faizan.ahmed@myalfred.ae',
            'mobile' => '48347583049',
            'agency_reference' => 'asc',
        ];
        $issuePolicy = Http::post($endpoint, $payLoad);
        $response = $issuePolicy->object();
        if ($response?->status === 'success') {
            info(json_encode($response));
            dd($response);
        }
    }

    public function policyPurchase($quote, $travelType)
    {
        $payLoad = [
            'agency_id' => $this->agencyId,
            'agency_code' => $this->agencyCode,
            'policy_id' => $quote->insurer_policy_id ?? 217243,
        ];
        $endpoint = $this->baseUrl.'/v1/quote/'.$travelType.'/purchase';
        $policyPurchase = Http::post($endpoint, $payLoad);
        $response = $policyPurchase->object();
        if ($response?->status === 'success') {
            info(json_encode($response));
            dd($response);
        }

    }

    public function fetchAndUploadDocument($quote)
    {
        $payLoad = [
            'agency_id' => $this->agencyId,
            'agency_code' => $this->agencyCode,
            'policy_id' => $quote->insurer_policy_id ?? 217243,
        ];
        $endpoint = $this->baseUrl.'/v1/policy/inbound/documents';
        $policyDocuments = Http::post($endpoint, $payLoad);
        $response = $policyDocuments->object();
        if ($response?->status === 'success') {
            info(json_encode($response));
            dd($response);
        }

    }

    public function fillBookingDetails($process)
    {
        $data = [
            'policy_number' => '123456',
            'policy_url' => 'https://www.allianceinsurance.com/policy/123456',
        ];

        return $data;
    }

    public function triggerBookPolicyProcess($process)
    {
        $data = [
            'policy_number' => '123456',
            'policy_url' => 'https://www.allianceinsurance.com/policy/123456',
        ];

        return $data;
    }

    public function documentMapping($process)
    {
        $data = [
            'policy_number' => '123456',
            'policy_url' => 'https://www.allianceinsurance.com/policy/123456',
        ];

        return $data;
    }

}
