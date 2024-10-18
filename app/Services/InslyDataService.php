<?php

namespace App\Services;

use App\Enums\quoteBusinessTypeCode;
use App\Enums\QuoteTypes;
use App\Models\InslyBatchLog;
use App\Models\InslyDataMapping;

class InslyDataService extends BaseService
{
    public static function GetDataFromInsly($nextStartDate, $nextEndDate)
    {
        $client = new \GuzzleHttp\Client;
        $user = config('constants.INSLY_API_RENEWAL_USERNAME');
        $pass = config('constants.INSLY_API_RENEWAL_PASSWORD');
        $uri = config('constants.INSLY_API_RENEWAL_URI');
        $timeout = config('constants.INSLY_REQUEST_TIMEOUT_IN_SECONDS');

        $requestBody = [
            'username' => $user,
            'password' => $pass,
            'policy_date_begin' => $nextStartDate,
            'policy_date_end' => $nextEndDate,
        ];

        $inslyRequest = $client->post(
            $uri,
            [
                'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body' => json_encode($requestBody),
                'timeout' => $timeout, // Response timeout
            ]
        );

        return $inslyRequest->getBody();
    }

    public static function GetLastInslyBatchLog()
    {
        return InslyBatchLog::orderBy('created_at', 'desc')->get()->first();
    }

    public static function AddInslyBatchLog($start, $end, $count)
    {
        $batchLog = new InslyBatchLog;
        $batchLog->batch_start_date = $start;
        $batchLog->batch_end_date = $end;
        $batchLog->batch_records_processed = $count;
        $batchLog->save();
    }

    public static function AddInslyRecordInDatabase($customer_name, $customer_email, $policy, $is_corrupt_data)
    {
        $dataMapping = new InslyDataMapping;
        $dataMapping->customer_name = $customer_name;
        $dataMapping->customer_email = $customer_email;
        $dataMapping->insly_data = json_encode($policy);
        $dataMapping->is_corrupt_data = $is_corrupt_data;
        $dataMapping->save();
    }

    public function inslyInsurances()
    {
        return [
            QuoteTypes::BIKE->value => ['Bike insurance'],
            QuoteTypes::BUSINESS->value => [
                'business interruption insurance', 'contractors all risks', 'Cyber liability', 'directors and officers liability insurance',
                'Engineering and plant insurance', 'fidelity guarantee', 'group life', 'group medical insurance', 'holiday homes',
                'livestock insurance', 'machinery breakdown insurance', 'marine cargo (individual shipment) insurance',
                'marine hull insurance', 'medical malpractice insurance', 'money insurance', 'motor fleet',
                'open cover - marine cargo insurance', 'professional indemnity insurance', 'property insurance',
                'public liability insurance', 'road transit (international)', 'road transit (UAE only)',
                'sme packaged insurance', 'trade credit insurance', 'workmens compensation insurance',
            ],
            QuoteTypes::CAR->value => ['casco', 'motor insurance - Comprehensive', 'motor insurance - TPL'],
            QuoteTypes::LIFE->value => ['Critical illness', 'Individual life insurance'],
            QuoteTypes::HOME->value => ['Home insurance', 'personal accident', 'home insurance'],
            QuoteTypes::TRAVEL->value => ['Inbound travel insurance', 'Outbound travel insurance'],
            QuoteTypes::HEALTH->value => ['Individual or family medical'],
            QuoteTypes::CYCLE->value => ['Pedal cycle insurance'],
            QuoteTypes::PET->value => ['Pet insurance'],
            QuoteTypes::YACHT->value => ['Yacht insurance'],
        ];
    }

    public function inslyBusinessTypeOfInsurance()
    {
        return [
            quoteBusinessTypeCode::property => ['property insurance'],
            quoteBusinessTypeCode::publicLiability => ['public liability insurance'],
            quoteBusinessTypeCode::groupMedical => ['group medical insurance'],
            quoteBusinessTypeCode::groupLife => ['group life'],
            quoteBusinessTypeCode::proIndemnity => ['professional indemnity insurance'],
            quoteBusinessTypeCode::carFleet => ['motor fleet'],
            quoteBusinessTypeCode::marineCargoIndividual => ['marine cargo (individual shipment) insurance'],
            quoteBusinessTypeCode::marineHull => ['marine hull insurance', 'yacht insurance'],
            quoteBusinessTypeCode::marineCargoOpenCover => ['open cover - marine cargo insurance'],
            quoteBusinessTypeCode::businessInterruption => ['business interruption insurance'],
            quoteBusinessTypeCode::machineryBreakdown => ['machinery breakdown insurance'],
            quoteBusinessTypeCode::tradeCredit => ['trade credit insurance'],
            quoteBusinessTypeCode::directorsOfficers => ['directors and officers liability insurance'],
            quoteBusinessTypeCode::cyber => ['cyber liability'],
            quoteBusinessTypeCode::workmens => ['workmens compensation insurance'],
            quoteBusinessTypeCode::contractorsRisk => ['contractors all risks', 'engineering and plant insurance'],
            quoteBusinessTypeCode::holidayHomes => ['holiday homes'],
            quoteBusinessTypeCode::liveStock => ['livestock insurance'],
            quoteBusinessTypeCode::moneyInsurance => ['money insurance'],
            quoteBusinessTypeCode::smeInsurance => ['sme packaged insurance'],
            quoteBusinessTypeCode::fidelityGuarantee => ['fidelity guarantee'],
            quoteBusinessTypeCode::goodsInTransit => ['road transit (uae only)'],
            quoteBusinessTypeCode::medicalMalpractices => ['medical malpractice insurance'],
        ];
    }
}
