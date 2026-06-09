<?php

namespace App\Jobs;

use App\Enums\CarAddsOnEnum;
use App\Enums\CarPlanCode;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTagEnums;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\ApplicationStorage;
use App\Models\CarQuotePlanDetail;
use App\Models\HealthPlanCoPayment;
use App\Models\QuoteTag;
use App\Models\User;
use App\Repositories\DocumentTypeRepository;
use App\Services\ActivitiesService;
use App\Services\CentralService;
use App\Services\Logger\LoggerService;
use App\Services\QuoteDocumentService;
use App\Services\SendEmailCustomerService;
use App\Traits\GenericQueriesAllLobs;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendBookPolicyDocumentsJob implements ShouldQueue
{
    use Dispatchable, GenericQueriesAllLobs, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 100;
    public $tries = 3;
    public $backoff = 120;

    /**
     * Create a new job instance.
     */
    private $data = null;

    private $code = null;
    private $forceEmailSend = false;
    private $fromSageProcess = false;

    public function __construct($payload, $code, $forceEmailSend = false, $fromSageProcess = false)
    {
        $this->data = $payload;
        $this->code = $code;
        $this->forceEmailSend = $forceEmailSend;
        $this->fromSageProcess = $fromSageProcess;
        $this->onQueue('insly')->afterCommit();
    }

    /**
     * Execute the job.
     */
    public function handle(SendEmailCustomerService $sendEmailCustomerService, QuoteDocumentService $quoteDocumentService)
    {
        LoggerService::startQuoteLogging($this->code);
        LoggerService::startFeatureLogging(LoggerFeatureEnum::SEND_AND_BOOK_POLICY_EMAIL_JOB);

        info('Quote Code: '.$this->code.' job: SendBookPolicyDocumentsJob started');
        $insuranceType = '';
        $planName = '';
        $isWarTerrorismAddonSelected = false;
        // In case of Group Medical & Corpline, modelType is used & for rest of the LOBs model_type is used
        // Basically we are different to identify the template which will send to customer after policy booking
        $modelType = ucfirst(! empty($this->data->modelType) ? $this->data->modelType : $this->data->model_type);
        $quoteTypeId = app(ActivitiesService::class)->getQuoteTypeId(strtolower($this->data->model_type));

        $quote = $this->getQuoteObject($this->data->model_type, $this->data->quote_id);
        $quote->refresh();
        LoggerService::info('automation:SendBookPolicyDocumentsJob - Quote Code : '.$quote->code.' - check advisor id', extra: [
            'quoteAdvisorId' => data_get($quote, 'advisor_id'),
        ]);

        $isAUHHealthLead = strtolower($this->data->model_type) === strtolower(QuoteTypes::HEALTH->value) && $quote->isAUHLead();

        info('job: SendBookPolicyDocumentsJob Code: '.$quote->code.' , Quote Type: '.$this->data->model_type.', Type Id: '.$quoteTypeId);

        $isDocumentEmailSentToCustomer = QuoteTag::where([
            'quote_type_id' => $quoteTypeId,
            'quote_uuid' => $quote->uuid,
            'name' => QuoteTagEnums::POLICY_SENT_TO_CUSTOMER,
            'value' => 1,
        ])->first();

        if ($isDocumentEmailSentToCustomer && ($this->forceEmailSend == false || $this->fromSageProcess == true)) {
            info('job: SendBookPolicyDocumentsJob skipped for: '.$quote->code.' as email already sent');

            return;
        }

        $handBookDocuments = $policyWordingDoc = [];

        try {
            // This will give handbook document from relevant policy wording table only for mentioned LOB's
            if (in_array($modelType, [quoteTypeCode::Car, quoteTypeCode::Travel, quoteTypeCode::Health])) {
                $coPaymentIds = null;
                if ($modelType == quoteTypeCode::Health) {
                    $coPaymentIds = HealthPlanCoPayment::where('health_plan_id', $quote->plan_id)->where('id', '!=', $quote->health_plan_co_payment_id)->pluck('id')->toArray();
                }
                $handBookDocuments = app(QuoteDocumentService::class)->getHandBookDocuments($quote, $coPaymentIds);
            }
            // First Retrieve document types marked for sending to the customer, then fetch the corresponding uploaded documents
            $documentTypeCodes = DocumentTypeRepository::quoteDocumentsSentToCustomerCode($this->data->model_type, $quote);
            $docs = app(QuoteDocumentService::class)->getQuoteDocuments($this->data->model_type, $this->data->quote_id, $documentTypeCodes);
        } catch (Exception $ex) {
            Log::error('Send BookPolicy Documents Job Error for:  '.$quote->code.' '.$ex->getMessage());
            $docs = [];
        }

        if (strtolower($modelType) == strtolower(quoteTypeCode::CORPLINE)) {
            $quote->load('businessTypeOfInsurance');
            $insuranceType = $quote->businessTypeOfInsurance->text;
        }
        if ($modelType == quoteTypeCode::Health) {
            $planName = $quote->plan->text;
        } elseif (in_array($modelType, [quoteTypeCode::SAVINGS, quoteTypeCode::Device])) {
            $planName = $quote?->insuranceProviderPlan?->text ?? '';
            // TODO : need to discuss this, because file size is exceed.
            $policyWordingDoc = [
                'watermarked_doc_url' => $quote->insuranceProviderPlan?->policyWordings?->link,
                'document_type_text' => 'Policy Wording/handbook',
            ];
        }

        $quote->load('advisor');

        $dataAdvisor = isset($this->data?->advisorId) ? User::find($this->data?->advisorId) : null;
        $advisor = $quote->advisor_id ? $quote->advisor : $dataAdvisor;

        LoggerService::info('automation:SendBookPolicyDocumentsJob - Quote Code : '.$quote->code.' - Advisor Object', extra: [
            'advisor' => $advisor,
            'quoteAdvisorId' => data_get($quote, 'advisor_id'),
            'dataAdvisorId' => data_get($this->data, 'advisorId') ?? null,
            'dataAdvisor_Id' => data_get($this->data, 'advisor_id') ?? null,
        ]);

        $templateId = ApplicationStorage::where('key_name', strtoupper(str_replace(' ', '_', $modelType)).'_BOOK_POLICY_TEMPLATE')->first()->value ?? null;
        $roadsideAssistance = '';
        $emailData = new \stdClass;
        $emailData->code = $quote->code;
        $emailData->customerEmail = $quote->email;
        $emailData->customerId = $quote->customer_id;
        $emailData->clientFullName = $quote->first_name.' '.$quote->last_name;
        $emailData->clientFirstName = $quote->first_name;
        $emailData->policy_number = $quote->policy_number;
        $emailData->policyNumber = $quote->policy_number;
        $emailData->renewalDueDate = date('d/m/Y', strtotime($quote['policy_expiry_date']));
        $emailData->policyStartDate = date('d/m/Y', strtotime($quote['policy_start_date']));
        $emailData->quoteDocuments = $docs;
        $emailData->advisorName = '';
        $emailData->advisorEmail = '';
        $emailData->advisorMobileNo = '';
        $emailData->advisorLandlineNo = '';
        $emailData->googleMeet = '';
        $emailData->insuranceType = $insuranceType;
        $emailData->planName = $planName;
        $emailData->currentInsurer = '';
        $emailData->profilePicture = '';
        $emailData->isChsAdvisor = false;
        if (! empty($advisor)) {
            $emailData->advisorName = $advisor->name;
            $emailData->advisorEmail = $advisor->email;
            $advisorMobileNo = formatMobileNo($advisor->mobile_no);
            $emailData->advisorMobileNo = str_replace('+', '', $advisorMobileNo);
            $emailData->advisorLandlineNo = $advisor->landline_no;
            $emailData->googleMeet = $advisor->calendar_link;
            $emailData->profilePicture = $advisor->profile_photo_path;
            if ($emailData->advisorEmail === PolicyIssuanceEnum::API_POLICY_ISSUANCE_AUTOMATION_USER_EMAIL) {
                $emailData->isChsAdvisor = true;
            }
        }
        if (in_array($modelType, [quoteTypeCode::Car, quoteTypeCode::Health, quoteTypeCode::Travel])) {
            $quotePlan = $quote->plan ?? null;
            $insuranceProvider = $quotePlan?->insuranceProvider ?? null;
            if ($quotePlan && $insuranceProvider) {
                $emailData->currentInsurer = $insuranceProvider->text;
                $roadsideAssistance = $insuranceProvider->roadside_phone_number;

                $planCode = $quotePlan->code;
                $insuranceProviderCode = $insuranceProvider->code;
                $isCarQuote = $modelType === quoteTypeCode::Car;
                $isQICPlan = in_array($planCode, [CarPlanCode::COMP_QIC_PRESTIGE->value, CarPlanCode::AGEN_QIC_PRESTIGE->value]) && $insuranceProviderCode === 'QIC';

                if ($isCarQuote && $isQICPlan) {
                    $warAddonCodes = [
                        CarAddsOnEnum::MotorWARAndTerrorismExtensionOD->value,
                        CarAddsOnEnum::MotorWARAndTerrorismExtensionODPAB->value,
                    ];

                    $addsOn = CarQuotePlanDetail::where('plan_code', $planCode)
                        ->where('provider_code', $insuranceProviderCode)
                        ->where('quote_uuid', $quote->uuid)
                        ->first(['addons']);

                    $addons = json_decode($addsOn?->addons ?? '[]', true);

                    $isWarTerrorismAddonSelected = collect($addons)
                        ->whereIn('code', $warAddonCodes)
                        ->contains(fn ($addon) => collect($addon['carAddonOption'] ?? [])
                            ->contains('isSelected', true)
                        );
                }
            }
        } else {
            if (isset($quote->insuranceProvider)) {
                $emailData->currentInsurer = $quote->insuranceProvider->text;
                $roadsideAssistance = $quote->insuranceProvider->roadside_phone_number;
            }
        }

        // for Savings
        $emailData->emailTemplateId = $templateId ?? null;
        $emailData->handBookDocuments = $handBookDocuments;
        $emailData->roadsideAssistance = $roadsideAssistance;
        $emailData->quoteTypeId = $quoteTypeId;
        $emailData->quoteId = $quote->id;
        $emailData->policyWordingHandbook = $policyWordingDoc;
        $emailData->isHealthAUH = $isAUHHealthLead;
        $emailData->appDownloadLink = app(QuoteDocumentService::class)->getAppDownloadLink($modelType, $quote);
        $emailData->isWarTerrorismAddonSelected = $isWarTerrorismAddonSelected;
        if (in_array($quoteTypeId, [QuoteTypeId::Car, QuoteTypeId::Bike, QuoteTypeId::Health, QuoteTypeId::Life, QuoteTypeId::Travel, QuoteTypeId::Cycle, QuoteTypeId::Yacht, QuoteTypeId::Home, QuoteTypeId::Business, QuoteTypeId::Pet, QuoteTypeId::Cyber, QuoteTypeId::Device])) {
            // For Bird
            $emailData = app(CentralService::class)->prepareBirdData(quote: $quote, quoteTypeId: $quoteTypeId, existingEmailData: $emailData);

            if (! empty($emailData)) {
                $response = app(CentralService::class)->sendInslyEmailToCustomer($quote, $emailData, $quoteTypeId, 'Main Lead');
            }
        } else {
            // For Bravo
            $response = $sendEmailCustomerService->sendBookPolicyDocumentsEmail($emailData, 'book-policy-document');
        }
        info('Quote Code: '.$quote->code.' Send Book Policy Documents Job Response '.$quote->uuid.' : '.json_encode($response));

        if ($this->forceEmailSend == false || $this->fromSageProcess == true) {
            $quoteTag = QuoteTag::updateOrCreate([
                'quote_type_id' => $quoteTypeId,
                'quote_uuid' => $quote->uuid,
                'name' => QuoteTagEnums::POLICY_SENT_TO_CUSTOMER,
            ], [
                'value' => 1,
            ]);

            info('job: SendBookPolicyDocumentsJob Code: '.$quote->code.' , Quote Tag id: '.$quoteTag->id);
        }
    }

    public function failed(Throwable $exception)
    {
        LoggerService::info('Quote Code: '.$this->code.' SendBookPolicyDocumentsJob Error', extra: [
            'errorTraceMessage' => $exception->getTraceAsString(),
            'exception' => $exception->getMessage(),
            'line' => $exception->getLine(),
        ]);
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->code))->dontRelease()];
    }

}
