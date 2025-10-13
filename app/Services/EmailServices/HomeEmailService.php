<?php

namespace App\Services\EmailServices;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteFlowType;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Jobs\DeleteTempOCBPDFFileJob;
use App\Models\ApplicationStorage;
use App\Models\HomeQuote;
use App\Models\PersonalQuote;
use App\Models\QuoteFlowDetails;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsBatchEmails;
use App\Models\User;
use App\Services\BaseService;
use App\Services\BirdService;
use App\Services\HomeQuoteService;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HomeEmailService extends BaseService
{
    public function sendHomeOCBIntroEmail($lead)
    {
        if (! $lead) {
            LoggerService::info('sendHomeOCBIntroEmail - Lead not found');

            return false;
        }

        LoggerService::info('sendHomeOCBIntroEmail - Initiating process');

        // Fetch the advisor
        $advisor = User::find($lead->advisor_id);
        if (! $advisor) {
            LoggerService::info('sendHomeOCBIntroEmail - Advisor not found');
        }

        // Fetch home quote
        $homeQuote = $this->getHomeQuoteData($lead->uuid);
        if (! $homeQuote) {
            LoggerService::info('sendHomeOCBIntroEmail - HomeQuote not found');

            return false;
        }

        // Build email data
        $emailData = $this->buildEmailData(
            $lead,
            $advisor,
            WorkflowTypeEnum::HOME_AUTOMATED_FOLLOWUPS,
            $homeQuote
        );

        // Fetch the automated workflow configuration
        $homeAutomatedEvent = ApplicationStorage::where('key_name', ApplicationStorageEnums::HOME_OCB_AUTOMATED_FOLLOWUPS)->first();

        if (! $homeAutomatedEvent) {
            LoggerService::info('sendHomeOCBIntroEmail - Workflow configuration not found | Time: '.now());

            return false;
        }

        try {
            $response = app(BirdService::class)->triggerWebHookRequest($homeAutomatedEvent->value, $emailData);

            if (empty($homeQuote->automated_flow_executed_at)) {
                $homeQuote->automated_flow_executed_at = now();
                $homeQuote->save();
                LoggerService::info('sendHomeOCBIntroEmail - Automated flow timestamp updated for HomeQuote');
                LoggerService::info('sendHomeOCBIntroEmail - Successfully triggered event');
                if ($response && $response->status_code === 200) {
                    $this->createQuoteFlowDetails($lead, $response);
                    LoggerService::info('sendHomeOCBIntroEmail - Quote flow details created for HomeQuote');
                } else {
                    LoggerService::info("sendHomeOCBIntroEmail - Error triggering event having response status code: {$response?->status_code}");
                }
            }

            return $response ?? null;
        } catch (\Exception $e) {
            LoggerService::info("sendHomeOCBIntroEmail - Error triggering event | Message: {$e->getMessage()} Line: {$e->getLine()}");

            return false;
        }
    }

    public function sendRenewalOCBEmail(RenewalsBatchEmails $renewalsBatchEmail, RenewalQuoteProcess $renewalQuoteProcess)
    {
        try {
            // Find Home Quote
            $lead = PersonalQuote::find($renewalQuoteProcess->quote_id);
            $homeQuote = $lead->homeQuote;

            LoggerService::startQuoteLogging($lead);

            LoggerService::info('Home Renewals OCB Email started');

            // Get Lead Advisor
            $advisor = User::where('id', $lead->advisor_id)->first();

            // Map Data for Home Renewal OCB Email
            $emailData = $this->mapDataForRenewalOCBEmail($homeQuote, $advisor, WorkflowTypeEnum::HOME_RENEWAL_OCB);

            $workflowUrl = ApplicationStorage::where('key_name', WorkflowTypeEnum::HOME_RENEWAL_OCB)->first()?->value;

            if ($workflowUrl) {
                app(BirdService::class)->triggerWebHookRequest($workflowUrl, $emailData);

                LoggerService::info('Renewals OCB Email Flow triggered', extra: [
                    'email' => $lead->email,
                ]);

                RenewalsBatchEmails::where('id', $renewalsBatchEmail->id)->update(['total_sent' => DB::raw('total_sent+1')]);
                RenewalQuoteProcess::where('id', $renewalQuoteProcess->id)->update(['email_sent' => 1]);

            } else {
                LoggerService::error('Home Renewals OCB Email failed', extra: [
                    'email' => $lead->email,
                ]);
            }

        } catch (\Exception $exception) {
            LoggerService::error('Home Renewals OCB Email failed', exception: $exception);
            RenewalsBatchEmails::where('id', $renewalsBatchEmail->id)->update(['total_failed' => DB::raw('total_failed+1')]);
        }
    }

    public function buildEmailData($lead, $advisor, $workflowType, $homeQuote)
    {
        $bccEmails = [];
        $bccEmails[] = getAppStorageValueByKey(ApplicationStorageEnums::HOME_LEAD_POOL_BCC);
        if ($lead->source === LeadSourceEnum::CPA_AUSTRALIA_HOME) {
            $bccEmails = array_merge($bccEmails, explode(',', getAppStorageValueByKey(ApplicationStorageEnums::CPA_AUSTRALIA_HOME_BCC_EMAILS)));
        }
        $data = [
            // Lead-related data
            'quoteUID' => $lead->uuid,
            'uuid' => $lead->uuid,
            'customerEmail' => $lead->email,
            'customerFullName' => trim("{$lead->first_name} {$lead->last_name}"),
            'customerName' => trim("{$lead->first_name} {$lead->last_name}"),
            'refID' => $lead->code,
            'customerMobile' => $lead->mobile_no ?? '',
            'whatsappConsent' => getWhatsappConsent(QuoteTypes::HOME, $lead->uuid),
            'flowExecutedAt' => $lead->automated_flow_executed_at ?? null,

            // Home quote-related data
            'automatedFlowExecuted' => ! empty($homeQuote?->automated_flow_executed_at),

            // Advisor-related data
            'advisorId' => $advisor?->id,
            'advisorName' => $advisor?->name ?? '',
            'advisorEmail' => $advisor?->email ?? '',
            'advisorDetails' => $advisor ?? null,
            'landLine' => $advisor?->landline_no ?? '',
            'mobilePhone' => $advisor?->mobile_no ?? '',
            'whatsAppNumber' => $advisor?->mobile_no ? formatMobileNo($advisor->mobile_no) : '',
            'mobileNoWithoutSpaces' => $advisor?->mobile_no ? removeSpaces(formatMobileNoDisplay($advisor->mobile_no)) : '',

            // Workflow-related data
            'workflowType' => $workflowType,
            'bccEmails' => $bccEmails ?? [],
        ];

        $tempUrlPDF = $this->attachHomeOCBPDFToEmail($lead->uuid);

        if (! empty($tempUrlPDF)) {
            $data['tempUrlPDF'] = $tempUrlPDF;
        }

        return (object) $data;
    }

    private function mapDataForRenewalOCBEmail($lead, $advisor, $workflowType)
    {
        $fullName = trim("{$lead->first_name} {$lead->last_name}");
        $advisorName = trim("{$advisor->name}");
        $advisorEmail = $advisor?->email ?? '';
        $advisorDetails = $advisor ?? null;
        $advisorId = $advisor?->id ?? null;
        $automatedFlowExecuted = empty($lead->automated_flow_executed_at) ? true : false;
        $flowExecutedAt = empty($lead->flow_executed_at) ? null : $lead->flow_executed_at;
        $triggerDate = $this->getOCBTriggerTimestamp($lead->previous_policy_expiry_date);
        $mobileNoWithoutSpaces = (! empty($advisor?->mobile_no) ? removeSpaces(formatMobileNoDisplay($advisor->mobile_no)) : '');
        $whatsappConsent = getWhatsappConsent(QuoteTypes::HOME, uuid: $lead->uuid);
        $landLine = (! empty($advisor?->landline_no) ? $advisor->landline_no : '');
        $mobilePhone = (! empty($advisor?->mobile_no) ? $advisor->mobile_no : '');
        $whatsAppNumber = (! empty($advisor?->mobile_no) ? formatMobileNo($advisor->mobile_no) : '');
        $customerMobile = (! empty($lead->mobile_no) ? $lead->mobile_no : '');

        $data = (object) [
            'quoteUID' => $lead->uuid,
            'customerEmail' => $lead->email,
            'refID' => $lead->code,
            'automatedFlowExecuted' => $automatedFlowExecuted,
            'uuid' => $lead->uuid,
            'customerFullName' => $fullName,
            'customerName' => $fullName,
            'advisorId' => $advisorId,
            'advisorName' => $advisorName,
            'advisorEmail' => $advisorEmail,
            'advisorDetails' => $advisorDetails,
            'flowExecutedAt' => $flowExecutedAt,
            'landLine' => $landLine,
            'mobilePhone' => $mobilePhone,
            'whatsAppNumber' => $whatsAppNumber,
            'mobileNoWithoutSpaces' => $mobileNoWithoutSpaces,
            'workflowType' => $workflowType,
            'customerMobile' => $customerMobile,
            'triggerDate' => $triggerDate,
            'whatsappConsent' => $whatsappConsent,
            'hasClaimedLosses' => $lead->has_claimed_losses ? 'Yes' : 'No',
        ];

        $tempUrlPDF = $this->attachHomeOCBPDFToEmail($lead->uuid, 64800);

        if (! empty($tempUrlPDF)) {
            $data->tempUrlPDF = $tempUrlPDF;
        }

        return (object) $data;
    }

    public function getHomeQuoteData(string $uuid): ?HomeQuote
    {
        return HomeQuote::with('subArea:id,text')
            ->where('uuid', $uuid)
            ->first();
    }

    public function attachHomeOCBPDFToEmail($quoteUID, int $pdfExpiry = 120)
    {
        try {
            LoggerService::info(self::class.' - attachHomeOCBPDFToEmail - Generating PDF');

            $quotePlans = app(HomeQuoteService::class)->getQuotePlans($quoteUID);

            // Generate the PDF
            $planIds = [];
            if (isset($quotePlans->quotes->plans)) {
                $planIds = collect($quotePlans->quotes->plans)
                    ->filter(function ($plan) {
                        return ! $plan->isDisabled && $plan->isRatingAvailable;
                    })
                    ->sortByDesc('isRenewal')
                    ->pluck('id')
                    ->take(5)
                    ->toArray() ?? [];
            }

            if (empty($planIds)) {
                LoggerService::info(self::class.' - attachHomeOCBPDFToEmail - No plans found');

                return '';
            }

            $pdfFile = app(HomeQuoteService::class)->exportPlansPdf(QuoteTypes::HOME->value, ['quote_uuid' => $quoteUID, 'plan_ids' => $planIds]);
            $pdfContent = $pdfFile['pdf']->output(); // Use output() to get raw PDF content

            LoggerService::info(self::class.' - attachHomeOCBPDFToEmail - Storing PDF temporarily');

            // Generate a unique temporary file path
            $tempFilePath = 'temp/'.uniqid().'.pdf';
            Storage::disk('azureIM')->put($tempFilePath, $pdfContent);

            // Generate a public URL
            $publicUrl = Storage::disk('azureIM')->temporaryUrl(
                $tempFilePath,
                now()->addMinutes($pdfExpiry)
            );
            // Schedule deletion after 5 minutes
            $this->scheduleFileDeletion($tempFilePath);

            LoggerService::info(self::class.' - attachHomeOCBPDFToEmail - Public URL generated');

            return $publicUrl;
        } catch (\Exception $e) {
            // Log the error details
            LoggerService::info(self::class." - Error: attachHomeOCBPDFToEmail - Error attaching PDF | Message: {$e->getMessage()} | File: {$e->getFile()} | Line: {$e->getLine()}");

            return false;
        }
    }
    protected function scheduleFileDeletion($filePath)
    {
        // Use a job to handle file deletion
        DeleteTempOCBPDFFileJob::dispatch($filePath)->delay(now()->addMinutes(120));
    }

    public function createQuoteFlowDetails($lead, $response)
    {
        try {
            $runId = collect($response->headers['Run-Id'])->first();
            if (! empty($runId)) {
                QuoteFlowDetails::create([
                    'quote_uuid' => $lead->uuid,
                    'quote_type_id' => QuoteTypeId::Home,
                    'flow_type' => QuoteFlowType::HOME_AUTOMATED_FOLLOWUPS,
                    'flow_id' => $runId,
                ]);
                LoggerService::info(self::class.' HomeAutomated | workflow run id created');
            } else {
                LoggerService::info(self::class.' HomeAutomated | workflow run id not found');
            }
        } catch (\Throwable $th) {
            $errorMessage = self::class.' - Error while creating quote flow details';
            LoggerService::info($errorMessage);
            LoggerService::info("Error: {$th->getMessage()}");
            throw $th;
        }

    }

    /**
     * Get timestamp for OCB trigger date based on policy expiry date
     * OCB date is 30 days before expiry, adjusted for weekends
     *
     * @param  string|Carbon  $expiryDate  The policy expiry date
     * @return string Timestamp for the OCB trigger date
     */
    public function getOCBTriggerTimestamp($expiryDate): string
    {
        // Ensure Carbon instance
        $expiry = Carbon::parse($expiryDate);

        // Subtract 30 days to get the OCB trigger date
        $ocbDate = $expiry->copy()->subDays(30);

        // Adjust for weekend rules
        switch ($ocbDate->dayOfWeek) {
            case Carbon::SATURDAY:
                $ocbDate->subDay();
                break;
            case Carbon::SUNDAY:
                $ocbDate->addDay();
                break;
            default:
                break;
        }

        // Get current time
        $now = Carbon::now();

        LoggerService::info('fn: getOCBTriggerTimestamp', [
            'expiryDate' => $expiryDate,
            'ocbDate' => $ocbDate,
        ]);

        // If OCB date is already in the past, return timestamp for 10 minutes from now
        if ($ocbDate->lessThanOrEqualTo($now)) {
            return (string) strtotime('+10 minutes');
        }

        // Return timestamp for the OCB date
        return (string) $ocbDate->timestamp;
    }

    /**
     * Get current plan data by calling the API with plan details from PersonalQuote relation
     */
    private function getCurrentPlanData($personalQuote)
    {
        try {
            LoggerService::info('getCurrentPlanData - Getting current plan data for quote: '.$personalQuote->uuid);

            // Get plan_id from the PersonalQuote model
            $planId = $personalQuote->plan_id;

            if (! $planId) {
                LoggerService::info('getCurrentPlanData - No plan_id found in PersonalQuote: '.$personalQuote->uuid);

                return [];
            }

            // Get insurance provider name using the relation
            $insuranceProviderCode = $personalQuote->insuranceProvider?->code ?? '';

            if (! $insuranceProviderCode) {
                LoggerService::info('getCurrentPlanData - No insurance provider found for quote: '.$personalQuote->uuid);

                return [];
            }

            LoggerService::info('getCurrentPlanData - Found plan_id: '.$planId.' and provider: '.$insuranceProviderCode);

            // Call your API here with the required parameters
            $currentPlan = $this->callCurrentPlanApi($planId, $personalQuote->uuid, $insuranceProviderCode);

            return [
                'planId' => $planId,
                'insuranceCompany' => $insuranceProviderCode,
                'currentPlanData' => $currentPlan,
            ];

        } catch (\Exception $e) {
            LoggerService::error('getCurrentPlanData - Error getting current plan data', exception: $e);

            return [];
        }
    }

    /**
     * Call the KEN API to get current plan data using fetch-home-provider-plan endpoint
     */
    private function callCurrentPlanApi($planId, $quoteUuid, $insuranceProviderCode)
    {
        try {
            LoggerService::info('callCurrentPlanApi - Calling KEN API with plan_id: '.$planId.', quote_uuid: '.$quoteUuid.', insuranceProvider: '.$insuranceProviderCode);

            // Get KEN API configuration
            $kenApiEndpoint = config('constants.KEN_API_ENDPOINT');
            $kenApiToken = config('constants.KEN_API_TOKEN');
            $kenApiTimeout = config('constants.KEN_API_TIMEOUT');
            $kenApiUser = config('constants.KEN_API_USER');
            $kenApiPassword = config('constants.KEN_API_PWD');

            // Create basic auth header
            $authBasic = base64_encode($kenApiUser.':'.$kenApiPassword);

            // Build the full endpoint URL
            $apiUrl = $kenApiEndpoint.'/fetch-home-provider-plan';

            // Prepare request data
            $requestData = [
                'planId' => $planId,
                'quoteUID' => $quoteUuid,
                'providerCode' => $insuranceProviderCode,
                'lang' => 'en',
            ];

            LoggerService::info('callCurrentPlanApi - Making request to: '.$apiUrl, $requestData);

            $client = new \GuzzleHttp\Client;
            $response = $client->post($apiUrl, [
                'json' => $requestData,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'x-api-token' => $kenApiToken,
                    'Authorization' => 'Basic '.$authBasic,
                ],
                'timeout' => $kenApiTimeout,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode === 200) {
                $responseBody = $response->getBody()->getContents();
                $responseData = json_decode($responseBody, true);

                LoggerService::info('callCurrentPlanApi - API call successful', [
                    'status_code' => $statusCode,
                    'response_data' => $responseData,
                ]);

                return $responseData;
            } else {
                LoggerService::error('callCurrentPlanApi - API call failed with status: '.$statusCode);

                return [];
            }

        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $response = $e->getResponse();
            $responseBody = $response ? $response->getBody()->getContents() : '';
            $statusCode = $response ? $response->getStatusCode() : 'unknown';

            LoggerService::error('callCurrentPlanApi - Bad response from KEN API', [
                'status_code' => $statusCode,
                'response_body' => $responseBody,
                'exception_message' => $e->getMessage(),
            ]);

            return [];
        } catch (\Exception $e) {
            LoggerService::error('callCurrentPlanApi - Error calling KEN API', [
                'exception_message' => $e->getMessage(),
                'exception_line' => $e->getLine(),
                'exception_file' => $e->getFile(),
            ]);

            return [];
        }
    }

    /**
     * Build email data specifically for Home renewal follow-ups
     * This ensures fresh premium data and renewal-specific information
     */
    public function buildRenewalEmailData($personalQuote, $advisor, $workflowType, $homeQuote)
    {
        LoggerService::info('buildRenewalEmailData - Building renewal-specific email data');

        // Get current plan data from API using PersonalQuote relation
        $currentPlanData = $this->getCurrentPlanData($personalQuote);

        $data = [
            // Base quote data
            'id' => $personalQuote->id,
            'quoteUID' => $personalQuote->uuid,
            'quoteUUID' => $personalQuote->uuid,
            'refID' => $personalQuote->code,
            'uuid' => $personalQuote->uuid,
            'customerEmail' => $personalQuote->email,
            'customerFullName' => trim("{$personalQuote->first_name} {$personalQuote->last_name}"),
            'customerName' => trim("{$personalQuote->first_name} {$personalQuote->last_name}"),
            'customerMobile' => $personalQuote->mobile_no ?? '',
            'isPolicyExpired' => $personalQuote->previous_policy_expiry_date ? Carbon::parse($personalQuote->previous_policy_expiry_date)->isPast() : false,

            // Advisor-related data
            'advisor' => $advisor ?? null,
            'advisorId' => $advisor?->id,
            'advisorName' => $advisor?->name ?? '',
            'advisorEmail' => $advisor?->email ?? '',
            'advisorLandLine' => $advisor?->landline_no ?? '',
            'advisorMobilePhone' => $advisor?->mobile_no ?? '',
            'advisorWhatsAppNumber' => $advisor?->mobile_no ? formatMobileNo($advisor->mobile_no) : '',
            'mobileNoWithoutSpaces' => $advisor?->mobile_no ? removeSpaces(formatMobileNoDisplay($advisor->mobile_no)) : '',

            // Current plan data from API
            'insuranceCompany' => $currentPlanData['currentPlanData']['providerName'] ?? '',
            'planName' => $currentPlanData['currentPlanData']['name'] ?? '',
            'planType' => $currentPlanData['currentPlanData']['planType'] ?? '',
            'currentPlan' => $currentPlanData,

            // Workflow-related data
            'workflowType' => $workflowType,
        ];

        LoggerService::info('buildRenewalEmailData - Renewal email data built successfully with fresh premium data');

        return (object) $data;
    }

    public function sendAutomatedHomeRenewalFollowup(PersonalQuote $personalQuote)
    {
        $workflowUrl = ApplicationStorage::where('key_name', ApplicationStorageEnums::HOME_RENEWAL_AUTOMATED_FOLLOWUPS)->first();

        LoggerService::info('| sendAutomatedHomeRenewalFollowup - Initiating process for Home renewal quote');

        if ($workflowUrl && ! empty($workflowUrl->value)) {
            // Fetch the advisor
            $advisor = User::find($personalQuote->advisor_id);
            if (! $advisor) {
                LoggerService::info("sendAutomatedHomeRenewalFollowup - Advisor not found for renewal quote: {$personalQuote->uuid}");
            }
            // ✅ Use NEW renewal-specific data builder
            $emailData = $this->buildRenewalEmailData(
                $personalQuote,
                $advisor,
                WorkflowTypeEnum::HOME_RENEWAL_AUTOMATED_FOLLOWUPS,
                $personalQuote->homeQuote
            );

            $response = app(BirdService::class)->triggerWebHookRequest($workflowUrl->value, $emailData);

            if ($response && $response->status_code === 200) {
                LoggerService::info("sendAutomatedHomeRenewalFollowup - Successfully triggered event for Home renewal quote: {$personalQuote->uuid}");
                app(BirdService::class)->createQuoteWorkFlowDetails($personalQuote, $response, QuoteFlowType::HOME_RENEWAL_AUTOMATED_FOLLOWUPS->value, QuoteTypes::HOME->id());
            } else {
                LoggerService::info("sendAutomatedHomeRenewalFollowup - Error triggering event having response status code: {$response?->status_code}");
            }
        } else {
            LoggerService::info(self::class." - Automated Home Renewal Followup is not set workflow url not found for quote: {$personalQuote->uuid}");
        }
    }
}
