<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTypeId;
use App\Models\ApplicationStorage;
use Exception;

class CammyService
{
    private HealthQuoteService $healthQuoteService;

    private const INTRO = 'intro';
    private const UNSUB = 'unsub';
    private const APPLICATION = 'application';

    public function __construct(HealthQuoteService $healthQuoteService)
    {
        $this->healthQuoteService = $healthQuoteService;
    }

    public function sync($lead, $trigger)
    {
        $isCammyFollowupEnabled = ApplicationStorage::where('key_name', ApplicationStorageEnums::ENABLE_CAMMY_FOLLOWUP)->first();
        if ($isCammyFollowupEnabled && $isCammyFollowupEnabled->value == 0 || ! $isCammyFollowupEnabled) {
            info('Cammy Service Disabled');

            return false;
        }

        if (! $lead || ! $trigger) {
            info('Cammy Service - Failed - Lead or Trigger not provided');

            return false;
        }

        if ($trigger != self::UNSUB) {
            $quote = $this->healthQuoteService->getQuotePlansPriority($lead->uuid);
            if (! isset($quote) || is_string($quote)) {
                info('Cammy Service - '.$lead->code.' - Failed - No Response from KEN');

                return false;
            }

            $plans = collect();
            if (isset($quote->plans) && is_array($quote->plans)) {
                foreach ($quote->plans as $plan) {
                    $features = collect();
                    if (isset($plan->benefits->feature)) {
                        foreach ($plan->benefits->feature as $feature) {
                            $features->push(
                                [
                                    'code' => $feature->code,
                                    'text' => $feature->text,
                                    'description' => $feature->description,
                                    'value' => $feature->value,
                                ]
                            );
                        }
                    }
                    $plans->push([
                        'name' => $plan->name,
                        'provider' => $plan->providerName,
                        'premium' => $plan->premium ?? '',
                        'features' => $features->toArray(),
                        'logo' => $plan->logo ?? '',
                        'planLink' => $plan->planLink ?? '',
                    ]);
                }
            } else {
                info('Cammy Service - Error - '.$lead->code.' - Plans Object Not Found');

                return false;
            }
        }
        $apiEndPoint = config('constants.CAMMY_END_POINT');
        $apiToken = config('constants.CAMMY_BASIC_AUTH_TOKEN');
        $responseMessage = null;
        $data = null;

        switch ($trigger) {
            case self::INTRO:
                $apiEndPoint .= '/api/v1/quotation';
                $responseMessage = 'Quotation Email trigerred successfully';
                $data = [
                    'quoteUID' => $lead->code,
                    'quoteTypeId' => QuoteTypeId::Health,
                    'contact' => ['firstName' => $lead->first_name, 'lastName' => $lead->last_name],
                    'fromEmail' => isset($lead->advisor) ? $lead->advisor->email : 'no-reply@alert.insurancemarket.email',
                    'toEmail' => $lead->email,
                    'cc' => '',
                    'bcc' => optional($lead->advisor)->email,
                    'advisor' => ['name' => optional($lead->advisor)->name, 'email' => optional($lead->advisor)->email, 'phone' => optional($lead->advisor)->mobile_no],
                    'comparePlansLink' => config('constants.AFIA_WEBSITE_DOMAIN').'/health-insurance/quote/'.$lead->uuid.'/compare',
                    'plans' => $plans->toArray(),
                ];
                break;
            case self::APPLICATION:
                $apiEndPoint .= '/api/v1/application';
                break;
            case self::UNSUB:
                $apiEndPoint .= '/api/v1/unsubscribe';
                $responseMessage = 'Unsubscribe Email trigerred successfully';
                $data = [
                    'quoteUID' => $lead->code,
                    'quoteTypeId' => QuoteTypeId::Health,
                    'emailAddress' => $lead->email,
                ];
                break;
            default:
                break;
        }

        try {
            $client = new \GuzzleHttp\Client;
            $cammyRequest = $client->post(
                $apiEndPoint,
                [
                    'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json', 'Authorization' => 'Basic '.$apiToken],
                    'body' => json_encode($data),
                    'timeout' => 10,
                ]
            );
            if ($cammyRequest->getStatusCode() == 200) {
                $responseContent = json_decode($cammyRequest->getBody());
                if ($responseContent->message == $responseMessage) {
                    info('Cammy Service - '.$lead->code.' - Success - '.$trigger.' Triggered');
                } else {
                    info('Cammy Service - '.$lead->code.' - Failed - '.$trigger.' Triggered');
                }
            } else {
                info('Cammy Service - '.$lead->code.' - Failed - '.$trigger.' Triggered');
            }
        } catch (Exception $exception) {
            info('Cammy Service - '.$lead->code.' - Failed - '.$trigger.' - '.$exception->getMessage());
        }
    }
}
