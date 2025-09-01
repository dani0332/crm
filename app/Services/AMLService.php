<?php

namespace App\Services;

use App\Enums\AMLDecisionStatusEnum;
use App\Enums\AMLScreeningTypeEnum;
use App\Enums\AMLStatusCode;
use App\Enums\CarRegistrationType;
use App\Enums\CustomerTypeEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\EnvEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\Kyc;
use App\Enums\LeadSourceEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\LookupsEnum;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\WorkflowTypeEnum;
use App\Facades\Ken;
use App\Http\Controllers\V2\AMLController;
use App\Http\Requests\AMLCheckRequest;
use App\Jobs\AutomationFailedJob;
use App\Models\AML;
use App\Models\BikeQuote;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\CarQuoteRequestDetail;
use App\Models\Customer;
use App\Models\CustomerDetail;
use App\Models\CustomerInsured;
use App\Models\CycleQuote;
use App\Models\Entity;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\Insured;
use App\Models\InsuredKyc;
use App\Models\JetskiQuote;
use App\Models\KycLog;
use App\Models\LifeQuote;
use App\Models\Lookup;
use App\Models\ManualAMLLog;
use App\Models\Nationality;
use App\Models\Payment;
use App\Models\PersonalQuote;
use App\Models\PetQuote;
use App\Models\QuoteMemberDetail;
use App\Models\QuoteRequestEntityMapping;
use App\Models\QuoteStatusLog;
use App\Models\SavingsQuote;
use App\Models\TravelQuote;
use App\Models\User;
use App\Models\YachtQuote;
use App\Repositories\CarQuoteRepository;
use App\Repositories\CustomerMembersRepository;
use App\Repositories\LookupRepository;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\Car\GIGInsuranceService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use PDF;

class AMLService
{
    use GenericQueriesAllLobs;

    public static function isDataMigrated($quoteTypeId, $quoteRequestId = '', $parseDate = ''): bool
    {
        $createdDate = $parseDate;
        if (empty($parseDate)) {
            $record = AML::where(['quote_request_id' => $quoteRequestId, 'quote_type_id' => $quoteTypeId])->first();
            if ($record) {
                $createdDate = $record->created_at;
            } else {
                $createdDate = Carbon::createFromFormat('Y-m-d', '2023-11-30');
            }
        }
        $dateForNonMigratedPersonalQuotes = Carbon::parse($parseDate)->format(config('constants.DATE_FORMAT_ONLY'));
        $dataMigrationDate = match ((int) $quoteTypeId) {
            (int) QuoteTypes::BIKE->id() => Carbon::createFromFormat('Y-m-d', '2023-08-12'),
            (int) QuoteTypes::YACHT->id() => Carbon::createFromFormat('Y-m-d', '2023-08-15'),
            (int) QuoteTypes::PET->id() => Carbon::createFromFormat('Y-m-d', '2023-08-14'),
            (int) QuoteTypes::CYCLE->id() => Carbon::createFromFormat('Y-m-d', '2023-08-14'),
            (int) QuoteTypes::JETSKI->id() => Carbon::createFromFormat('Y-m-d', '2023-08-14'),
            (int) QuoteTypes::LIFE->id() => Carbon::createFromFormat('Y-m-d', '2023-08-14'),
            (int) QuoteTypes::SAVINGS->id() => Carbon::createFromFormat('Y-m-d', '2025-02-14'),
            (int) QuoteTypes::HOME->id() => Carbon::createFromFormat('Y-m-d', $dateForNonMigratedPersonalQuotes),
        };

        return Carbon::createFromFormat(
            config('constants.DATE_FORMAT_ONLY'),
            Carbon::parse($createdDate)->format(config('constants.DATE_FORMAT_ONLY'))
        )->gte($dataMigrationDate);
    }

    public static function getPersonalQuoteId($quoteTypeId, $quoteRequestId)
    {
        return match ($quoteTypeId) {
            QuoteTypes::BIKE->id() => $quoteRequestId,
            QuoteTypes::CYCLE->id() => $quoteRequestId,
            QuoteTypes::JETSKI->id() => $quoteRequestId,
            QuoteTypes::PET->id() => PetQuote::where('id', $quoteRequestId)->firstOrFail()->personal_quote_id,
            QuoteTypes::YACHT->id() => $quoteRequestId,
            QuoteTypes::LIFE->id() => $quoteRequestId,
            QuoteTypes::SAVINGS->id() => $quoteRequestId,
            QuoteTypes::HOME->id() => $quoteRequestId,
        };
    }

    public static function updatePaIdForPersonalQuotes($quoteTypeId, $quoteRequestId, $isMigrated, $updateData = '')
    {
        LoggerService::info('fn:updatePaIdForPersonalQuotes - AMLService');

        $filterColumn = $isMigrated ? 'personal_quote_id' : 'id';
        $updateData = empty($updateData) ? ['pa_id' => auth()->id()] : $updateData;

        return match ($quoteTypeId) {
            QuoteTypes::BIKE->id() => BikeQuote::where($filterColumn, $quoteRequestId)->update($updateData),
            QuoteTypes::CYCLE->id() => CycleQuote::where($filterColumn, $quoteRequestId)->touch(),
            QuoteTypes::JETSKI->id() => JetskiQuote::where($filterColumn, $quoteRequestId)->touch(),
            QuoteTypes::PET->id() => PetQuote::where($filterColumn, $quoteRequestId)->update($updateData),
            QuoteTypes::YACHT->id() => YachtQuote::where($filterColumn, $quoteRequestId)->update($updateData),
            QuoteTypes::LIFE->id() => LifeQuote::where($filterColumn, $quoteRequestId)->update($updateData),
            QuoteTypes::SAVINGS->id() => SavingsQuote::where($filterColumn, $quoteRequestId)->touch(),
            QuoteTypes::HOME->id() => HomeQuote::where($filterColumn, $quoteRequestId)->update($updateData),
        };
    }

    public static function getQuoteDetails($quoteTypeId, $quoteRequestId)
    {
        if ($quoteTypeId == QuoteTypes::CAR->id()) {
            $quoteRequestDetails = CarQuote::with([
                'quoteStatus',
                'payments.paymentMethod',
                'payments.getCustomerPaymentInstrument',
                'paymentStatus',
                'customer.detail',
                'uaeLicenseHeldFor',
                'carMake',
                'carModel',
                'emirate',
                'carTypeInsurance',
                'claimHistory',
                'nationality',
                'carQuoteRequestDetail',
                'plan.insuranceProvider',
                'vehicleDriverDetail',
            ])->where('id', $quoteRequestId)->firstOrFail();
        } elseif ($quoteTypeId == QuoteTypes::HOME->id()) {
            $quoteRequestDetails = PersonalQuote::byQuoteTypeId(QuoteTypes::HOME->id())->with([
                'quoteDetail',
                'homeQuote',
                'homeQuote.possessionType',
                'homeQuote.accommodationType',
                'customer.detail',
                'quoteStatus',
                'payments.paymentMethod',
                'payments.getCustomerPaymentInstrument',
                'paymentStatus',
            ])->where('id', $quoteRequestId)->firstOrFail();
        } elseif ($quoteTypeId == QuoteTypes::HEALTH->id()) {
            $quoteRequestDetails = HealthQuote::with([
                'quoteStatus',
                'payments.paymentMethod',
                'payments.getCustomerPaymentInstrument',
                'paymentStatus',
                'customer.detail',
                'healthCoverFor',
                'maritalStatus',
                'emirate',
                'nationality',
            ])->where('id', $quoteRequestId)->firstOrFail();
        } elseif ($quoteTypeId == QuoteTypes::LIFE->id()) {
            $quoteRequestDetails = PersonalQuote::byQuoteTypeId(QuoteTypes::LIFE->id())->with([
                'quoteStatus',
                'paymentStatus',
                'customer.detail',
                'nationality',
                'payments' => function ($q) {
                    $q->with([
                        'paymentMethod',
                        'getCustomerPaymentInstrument',
                        'paymentStatus',
                    ]);
                },
                'lifeQuote' => function ($q) {
                    $q->with([
                        'children',
                        'currency',
                        'maritalStatus',
                        'purposeOfInsurance',
                        'insuranceTenure',
                        'numberOfYears',
                    ]);
                },

            ])->where('id', $quoteRequestId)->firstOrFail();
        } elseif ($quoteTypeId == QuoteTypes::BUSINESS->id()) {
            $quoteRequestDetails = BusinessQuote::with([
                'quoteStatus',
                'payments.paymentMethod',
                'payments.getCustomerPaymentInstrument',
                'paymentStatus',
                'customer.detail',
                'businessTypeOfInsurance',
            ])->where('id', $quoteRequestId)->firstOrFail();
        } elseif ($quoteTypeId == QuoteTypes::TRAVEL->id()) {
            $quoteRequestDetails = TravelQuote::with([
                'quoteStatus',
                'payments.paymentMethod',
                'payments.getCustomerPaymentInstrument',
                'paymentStatus',
                'customer.detail',
                'regionCoverFor',
                'travelCoverFor',
                'nationality',
            ])->where('id', $quoteRequestId)->firstOrFail();
        } elseif ($quoteTypeId == QuoteTypes::PET->id()) {
            $quoteRequestDetails = PersonalQuote::byQuoteTypeId(QuoteTypes::PET->id())->with([
                'petQuote',
                'customer.detail',
                'quoteStatus',
                'payments.paymentMethod',
                'payments.getCustomerPaymentInstrument',
                'paymentStatus',
            ])->where('id', $quoteRequestId)->firstOrFail();
        } elseif ($quoteTypeId == QuoteTypes::BIKE->id()) {
            $quoteRequestDetails = PersonalQuote::byQuoteTypeId(QuoteTypes::BIKE->id())->with([
                'quoteDetail',
                'bikeQuote',
                'customer.detail',
                'quoteStatus',
                'payments.paymentMethod',
                'payments.getCustomerPaymentInstrument',
                'paymentStatus',
            ])->where('id', $quoteRequestId)->firstOrFail();
        } elseif ($quoteTypeId == QuoteTypes::CYCLE->id()) {
            $quoteRequestDetails = PersonalQuote::byQuoteTypeId(QuoteTypes::CYCLE->id())->with([
                'cycleQuote',
                'customer.detail',
                'quoteStatus',
                'payments.paymentMethod',
                'payments.getCustomerPaymentInstrument',
                'paymentStatus',
            ])->where('id', $quoteRequestId)->firstOrFail();
        } elseif ($quoteTypeId == QuoteTypes::YACHT->id()) {
            $quoteRequestDetails = PersonalQuote::byQuoteTypeId(QuoteTypes::YACHT->id())->with([
                'yachtQuote',
                'customer.detail',
                'quoteStatus',
                'payments.paymentMethod',
                'payments.getCustomerPaymentInstrument',
                'paymentStatus',
            ])->where('id', $quoteRequestId)->firstOrFail();
        } elseif ($quoteTypeId == QuoteTypes::JETSKI->id()) {
            $quoteRequestDetails = PersonalQuote::byQuoteTypeId(QuoteTypes::JETSKI->id())->with([
                'jetskiQuote',
                'customer.detail',
                'quoteStatus',
                'payments.paymentMethod',
                'payments.getCustomerPaymentInstrument',
                'paymentStatus',
            ])->where('id', $quoteRequestId)->firstOrFail();
        } elseif ($quoteTypeId == QuoteTypes::SAVINGS->id()) {
            $quoteRequestDetails = PersonalQuote::byQuoteTypeId(QuoteTypes::SAVINGS->id())->with([
                'savingsQuote',
                'customer.detail',
                'quoteStatus',
                'payments.paymentMethod',
                'payments.getCustomerPaymentInstrument',
                'paymentStatus',
            ])->where('id', $quoteRequestId)->firstOrFail();
        }

        return $quoteRequestDetails;
    }

    public static function sendAMLErrorEmailtoEngTeam($amlQuoteUrl, $apiResponseMessage, $amlDataForEmail, $getStatusCode)
    {
        $emailSystem = config('constants.APP_ENV');
        $errorEmailRecipients = explode(',', config('constants.ERROR_EMAIL_RECIPIENTS'));

        $subject = $emailSystem.' BRIDGER SEARCH API ERROR | '.\Request::url().' | '.date(config('constants.DB_DATE_FORMAT_MATCH'));
        MailService::sendEmail('AmlErrorMail', [
            'amlUrl' => $amlQuoteUrl,
            'emailAmlData' => $amlDataForEmail,
            'chAmlStatus' => $getStatusCode,
            'requestMessage' => $apiResponseMessage,
        ], $subject, $errorEmailRecipients);
    }

    public static function sendAMLMatchedEmailtoComplianceTeam($amlQuoteUrl, $quoteRefId, $amlResultCount, $customerOrEntityName, $quoteType, $loginUserEmail, $forComplianceSuperUser = false, $isAutomation = false)
    {
        $emailRecipients = [];
        $emailSystem = config('constants.APP_ENV');
        $complianceRole = $forComplianceSuperUser ? [RolesEnum::ComplianceSuperUser] : [RolesEnum::COMPLIANCE, RolesEnum::ComplianceSuperUser];
        $recipients = User::select('users.email as user_email')
            ->leftjoin('model_has_roles', 'users.id', 'model_has_roles.model_id')
            ->leftjoin('roles', 'model_has_roles.role_id', 'roles.id')
            ->where('users.is_active', 1)
            ->whereIn('roles.name', $complianceRole)->get();

        foreach ($recipients as $recipient) {
            $emailRecipients[] = $recipient->user_email;
        }

        if (strtolower($emailSystem) == EnvEnum::PRODUCTION) {
            $fromEmail = config('constants.MAIL_FROM_ADDRESS_AML');
            $fromName = config('constants.MAIL_FROM_NAME_AML');
            $emailSubject = 'IMCRM | New AML Matches Found for Ref-ID : '.$quoteRefId;
        } else {
            $fromEmail = config('constants.MAIL_FROM_ADDRESS');
            $fromName = config('constants.MAIL_FROM_NAME');
            $emailSubject = $emailSystem.' | IMCRM | New AML Matches Found for Ref-ID : '.$quoteRefId;
        }

        Mail::send(
            ['html' => 'AmlComplianceMail'],
            [
                'amlUrl' => $amlQuoteUrl,
                'resultsFound' => $amlResultCount,
                'fullName' => $customerOrEntityName,
                'quoteTypeName' => $quoteType,
                'quoteCdbId' => $quoteRefId,
            ],
            function ($message) use ($emailSubject, $emailRecipients, $fromName, $fromEmail, $loginUserEmail, $forComplianceSuperUser, $isAutomation) {
                $message->to($emailRecipients);
                if (! $isAutomation && (in_array($loginUserEmail, $emailRecipients)) || ! $forComplianceSuperUser) {
                    $message->cc($loginUserEmail);
                }
                $message->subject($emailSubject);
                $message->from($fromEmail, $fromName);
            }
        );

        //        self::sendAmlComplianceMail($amlQuoteUrl, $amlResultCount, $customerOrEntityName, $quoteType, $quoteRefId, $emailSubject, $emailRecipients, $fromName, $fromEmail, $loginUserEmail, $forComplianceSuperUser);
    }

    //    private static function sendAmlComplianceMail($amlQuoteUrl, $amlResultCount, $customerOrEntityName, $quoteType, $quoteRefId, $emailSubject, $emailRecipients, $fromName, $fromEmail, $loginUserEmail, $forComplianceSuperUser)
    //    {
    //        try {
    //            $headers = [
    //                'Accept' => 'application/json',
    //                'api-key' => config('constants.SENDINBLUE_KEY'),
    //                'Content-Type' => 'application/json',
    //            ];
    //            $url = config('constants.SIB_URL');
    //            $amlUrl = $amlQuoteUrl ? $amlQuoteUrl : 'N/A';
    //            $resultsFound = $amlResultCount ? $amlResultCount : 0;
    //            $fullName = $customerOrEntityName ? $customerOrEntityName : 'N/A';
    //            $quoteTypeName = $quoteType;
    //            $quoteCdbId = $quoteRefId;
    //            $htmlContent = View::make('AmlComplianceMail', compact('amlUrl', 'resultsFound', 'fullName', 'quoteTypeName', 'quoteCdbId'))->render();
    //
    //            $toEmails = array_map(function ($email) {
    //                return ['email' => $email];
    //            }, $emailRecipients);
    //
    //            $ccEmail = [];
    //            if (in_array($loginUserEmail, $emailRecipients) || ! $forComplianceSuperUser) {
    //                $ccEmail[] = ['email' => $loginUserEmail];
    //            }
    //
    //            $bodyData = [
    //                'sender' => ['name' => $fromName, 'email' => $fromEmail],
    //                'to' => $toEmails,
    //                'subject' => $emailSubject,
    //                'htmlContent' => $htmlContent,
    //            ];
    //
    //            if (! empty($ccEmail)) {
    //                $bodyData['cc'] = $ccEmail;
    //            }
    //            $body = json_encode($bodyData, JSON_UNESCAPED_SLASHES);
    //            $client = new \GuzzleHttp\Client;
    //            $clientRequest = $client->post(
    //                $url,
    //                [
    //                    'headers' => $headers,
    //                    'body' => $body,
    //                    'timeout' => 10,
    //                ]
    //            );
    //
    //            $responseCode = $clientRequest->getStatusCode();
    //            LoggerService::info('sendAmlComplianceMail ---- Received Code : '.$responseCode);
    //        } catch (Exception $ex) {
    //            $responseCode = $ex->getCode();
    //            $responseDetail = 'sendAmlComplianceMail: Code/Message: '.$responseCode.'/'.$ex->getMessage();
    //        }
    //    }

    /**
     * Get insured person details.
     *
     * @return object containing properties:
     *                - 'status' (bool)
     *                - 'message' (string)
     *                - 'response' (object|null) may not exists
     */
    public function getInsuredPersonDetails(string $idType, string $idNumber): ?object
    {
        $insuredPersonDetails = Insured::where([
            'id_type' => $idType,
            'id_number' => $idNumber,
        ])->first();

        if (! $insuredPersonDetails) {
            $customerDetails = CustomerDetail::with(['customer:id,code,dob,gender,insured_first_name as first_name,insured_last_name as last_name,nationality_id'])
                ->where(['id_type' => $idType, 'id_number' => str_replace('-', '', $idNumber)])->first();
            $insuredPersonDetails = $customerDetails?->customer;
        }

        return $insuredPersonDetails;
    }

    /**
     * Get insured person details.
     *
     * @param array AMLCheckRequest $amlRequestData
     * @return object containing properties:
     *                - 'status' (bool)
     *                - 'message' (string)
     *
     * Need to refactor this code to not call controller from here
     */
    public function quoteAmlProcessCall(array $amlRequestData, int $quoteTypeId, int $quoteRequestId): object
    {
        // Create AML check request object
        $amlCheckRequest = new AMLCheckRequest($amlRequestData);

        // Call the AML quote update method
        return app(AMLController::class)->quoteUpdate($amlCheckRequest, $quoteTypeId, $quoteRequestId)->getData();
    }

    public function saveManualAuditLog($quoteDetails, User $processByUser): bool
    {
        if (! $quoteDetails instanceof TravelQuote) {
            return false;
        }

        $dirty = $quoteDetails->getDirty();

        if (empty($dirty)) {
            return false;
        }

        $changes = [];
        foreach ($dirty as $attribute => $value) {
            $changes['old_values'][$attribute] = $quoteDetails->getOriginal($attribute);
            $changes['new_values'][$attribute] = $value;
        }

        $quoteDetails->audits()->create([
            'user_type' => get_class($processByUser),
            'user_id' => $processByUser->id ?? null,
            'event' => 'updated',
            'old_values' => $changes['old_values'],
            'new_values' => $changes['new_values'],
        ]);

        return true;
    }

    public function getLatestScreening($quoteRequestId, $quoteTypeId)
    {
        return KycLog::withTrashed()->where([
            'quote_type_id' => $quoteTypeId,
            'quote_request_id' => $quoteRequestId,
        ])->where(function ($ryuFilter) {
            $ryuFilter->whereNotIn('decision', [AMLDecisionStatusEnum::RYU]);
            $ryuFilter->orWhereNull('decision');
        })->where(function ($aml) {
            $aml->whereNotIn('screening_type', [AMLScreeningTypeEnum::INSURER_AXA]);
            $aml->orWhereNull('screening_type');
        })->whereNull('screenshot')->get()->last() ?? [];
    }

    public function handleResponse(bool $status, string $message, bool $isAutomation = false)
    {
        return $isAutomation ? response()->json(['status' => $status, 'message' => $message]) : redirect()->back()->with($status ? 'success' : 'error', $message);
    }

    public function prepareScreeningData($AMLCheckRequest, $quoteType, $updateQuote)
    {
        $getMemberOrUBODetails = $this->getMemberOrUBODetails($AMLCheckRequest, $quoteType, $updateQuote->id);
        $getLastScreening = $this->getLatestScreening($updateQuote->id, $quoteType->id);
        $membersDetails = $getMemberOrUBODetails;

        if (! empty($getMemberOrUBODetails->toArray())) {
            LoggerService::info('AML Screening Bridger - Validation check - Members found against quote');
            $memberValidateCheck = collect($getMemberOrUBODetails)->pluck('first_name')->toArray();
            if (in_array(null, $memberValidateCheck)) {
                LoggerService::info('AML Screening Bridger - Validation check - Member First Name missing');

                return [false, 'First Name missing', [], $getLastScreening];
            }

            // Filter members that need screening based on their updated_at date
            $getMemberOrUBODetails = collect($getMemberOrUBODetails)->filter(function ($member) use ($getLastScreening) {
                $lastScreeningDate = $getLastScreening->created_at ?? '';

                // Include members with null updated_at (replicated members that need screening)
                if (is_null($member->updated_at)) {
                    LoggerService::info('AML Screening Bridger - Including member with null updated_at (replicated member)', extra: [
                        'member_id' => $member->id ?? 'unknown',
                        'member_name' => ($member->first_name ?? '').' '.($member->last_name ?? ''),
                    ]);

                    return true;
                }

                // Include members that were updated after the last screening
                return $member->updated_at >= $lastScreeningDate;
            });

            $membersDetails = $getMemberOrUBODetails;
        }

        return [true, '', $membersDetails, $getLastScreening];
    }

    public static function getMemberOrUBODetails($request, $quoteType, $quoteRequestId)
    {
        LoggerService::info('fn:getMemberOrUBODetails - AMLService');

        $membersFor = ($request->customer_type == CustomerTypeEnum::Entity) ? CustomerTypeEnum::Entity : CustomerTypeEnum::Individual;

        return CustomerMembersRepository::getBy($quoteRequestId, $quoteType->code, $membersFor);
    }

    public static function updateAMLDecisionLexisNexis($request)
    {
        if (! $request->result_id) {
            LoggerService::warning('AML Screening Bridger - Bridger Decision update API Call - Result Id not found');

            return false;
        }

        $bridgerInsightService = new BridgerInsightService;
        $bridgerAPIToken = $bridgerInsightService->getJWTToken();

        $matchResultsForUpdate = [];
        $decisionValues = (array) json_decode($request->decisonsForUpdatePortal)[0] ?? [];
        foreach ($decisionValues as $matchKey => $matchValue) {
            $matchResultsForUpdate[] = [
                'MatchID' => $matchKey,
                'Type' => $matchValue,
            ];
        }

        return $bridgerInsightService->updateDecisionOnLexisNexis($bridgerAPIToken, $request, $matchResultsForUpdate);
    }

    public static function getKycType($quoteTypeId, $quoteRequestId)
    {
        $status = KycLog::withTrashed()->select(DB::raw('LEFT(customer_code, 3) AS splitted_customer_code'))
            ->where(['quote_request_id' => $quoteRequestId, 'quote_type_id' => $quoteTypeId])
            ->where(function ($ryuFilter) {
                $ryuFilter->whereNotIn('decision', [AMLDecisionStatusEnum::RYU]);
                $ryuFilter->orWhereNull('decision');
            })
            ->where(function ($aml) {
                $aml->whereNotIn('screening_type', [AMLScreeningTypeEnum::INSURER_AXA]);
                $aml->orWhereNull('screening_type');
            })
            ->whereNull('screenshot')
            ->orderBy('id', 'desc')
            ->value('splitted_customer_code');

        if ($status == null && $quoteTypeId == QuoteTypes::BUSINESS->id()) {
            $status = CustomerTypeEnum::EntityShort;
        } elseif ($status == null && $quoteTypeId == QuoteTypes::CAR->id()) {

            $quote = CarQuoteRepository::where('id', $quoteRequestId)->select('registration_type')->first();
            if ($quote->registration_type == CarRegistrationType::COMPANY) {
                $status = CustomerTypeEnum::EntityShort;
            } else {
                $status = CustomerTypeEnum::IndividualShort;
            }
        } elseif ($status == null && $quoteTypeId != QuoteTypes::BUSINESS->id()) {
            $status = CustomerTypeEnum::IndividualShort;
        }

        return $status;
    }

    public static function checkAMLStatusFailed($quoteTypeId, $quoteRequestId)
    {
        LoggerService::info('fn:checkAMLStatusFailed - AMLService');

        $failedScreeningDecisions = [
            null,
            AMLDecisionStatusEnum::ESCALATED,
            AMLDecisionStatusEnum::SENT_FOR_REVIEW,
            AMLDecisionStatusEnum::TRUE_MATCH,
            AMLDecisionStatusEnum::TRUE_MATCH_REJECT_RISK,
        ];

        $fetchAMLRecords = KycLog::withTrashed()->where([
            'quote_type_id' => $quoteTypeId,
            'quote_request_id' => $quoteRequestId,
        ])->where(function ($ryuFilter) {
            $ryuFilter->whereNotIn('decision', [AMLDecisionStatusEnum::RYU]);
            $ryuFilter->orWhereNull('decision');
        })->where(function ($aml) {
            $aml->whereNotIn('screening_type', [AMLScreeningTypeEnum::INSURER_AXA]);
            $aml->orWhereNull('screening_type');
        })->whereNull('screenshot')->pluck('decision');

        if ($fetchAMLRecords->count() == 0) {
            return true;
        }

        return collect($fetchAMLRecords)->contains(function ($value) use ($failedScreeningDecisions) {
            return in_array($value, $failedScreeningDecisions);
        });
    }

    public function sendAMLQuoteStatusChangeNotification($quoteTypeId, $quoteRequestId, $quoteStatusText, $quoteCdbId, $quoteTypeText, $quotePaID, $clientFullName, $forComplianceSuperUser = false)
    {
        $complianceRole = $forComplianceSuperUser ? [RolesEnum::ComplianceSuperUser] : [RolesEnum::COMPLIANCE, RolesEnum::ComplianceSuperUser];
        $complianceUsersEmails = User::select('users.email as user_email')
            ->leftjoin('model_has_roles', 'users.id', 'model_has_roles.model_id')
            ->leftjoin('roles', 'model_has_roles.role_id', 'roles.id')
            ->whereIn('roles.name', $complianceRole)->get();

        $complianceEmailRecipients = [];
        foreach ($complianceUsersEmails as $complianceUsersEmail) {
            $complianceEmailRecipients[] = $complianceUsersEmail->user_email;
        }

        if ($quotePaID != '') {
            // TO will be quotePaID
            $paUserEmailId = User::where('id', '=', $quotePaID)->value('email');
            $toRecipient = $paUserEmailId;

            // CC will be all users compliance
            $ccRecipients = $complianceEmailRecipients;
        } else {
            // TO will be currentUserID
            $currentUserEmailId = User::where('id', '=', auth()->user()->id)->value('email');
            $toRecipient = $currentUserEmailId;

            // CC will be all users compliance
            $ccRecipients = $complianceEmailRecipients;
        }

        $emailL_sys = config('constants.APP_ENV');
        if ($emailL_sys == EnvEnum::PRODUCTION) {
            $emailSubject = 'IMCRM | New AML Matches Found for Ref-ID : '.$quoteCdbId;
        } else {
            $emailSubject = $emailL_sys.' | IMCRM | New AML Matches Found for Ref-ID : '.$quoteCdbId;
        }

        $appUrl = config('constants.APP_URL');
        $amlUrl = $appUrl.'/kyc/aml/'.$quoteTypeId.'/details/'.$quoteRequestId;

        $this->amlQuoteStatusUpdateMail('AmlQuoteStatusUpdateMail', [
            'amlUrl' => $amlUrl,
            'amlQuoteStatus' => $quoteStatusText,
            'clientFullName' => $clientFullName,
            'quoteTypeName' => $quoteTypeText,
            'quoteCdbId' => $quoteCdbId,
        ], $emailSubject, $toRecipient, $ccRecipients);

        //        $this->amlQuoteStatusUpdateMail($amlUrl, $quoteStatusText, $clientFullName, $quoteTypeText, $quoteCdbId, $toRecipient, $ccRecipients);
    }

    private function amlQuoteStatusUpdateMail($templateName, $templateParams, $emailSubject, $toRecipient, $ccRecipients)
    {
        $emailL_sys = config('constants.APP_ENV');
        if ($emailL_sys == EnvEnum::PRODUCTION) {
            $fromEmail = config('constants.MAIL_FROM_ADDRESS_AML');
            $fromName = config('constants.MAIL_FROM_NAME_AML');
        } else {
            $fromEmail = config('constants.MAIL_FROM_ADDRESS');
            $fromName = config('constants.MAIL_FROM_NAME');
        }

        Mail::send(
            ['html' => $templateName],
            $templateParams,
            function ($message) use ($emailSubject, $toRecipient, $ccRecipients, $fromName, $fromEmail) {
                $message->to($toRecipient)->cc($ccRecipients)->subject($emailSubject);
                $message->from($fromEmail, $fromName);
            }
        );

        //        if (config('constants.APP_ENV') == EnvEnum::PRODUCTION) {
        //            $emailSubject = 'IMCRM | New AML Matches Found for Ref-ID : '.$quoteCdbId;
        //        } else {
        //            $emailSubject = config('constants.APP_ENV').' | IMCRM | New AML Matches Found for Ref-ID : '.$quoteCdbId;
        //        }
        //
        //        try {
        //            $headers = [
        //                'Accept' => 'application/json',
        //                'api-key' => config('constants.SENDINBLUE_KEY'),
        //                'Content-Type' => 'application/json',
        //            ];
        //            $url = config('constants.SIB_URL');
        //            $emailL_sys = config('constants.APP_ENV');
        //            if ($emailL_sys == EnvEnum::PRODUCTION) {
        //                $fromEmail = config('constants.MAIL_FROM_ADDRESS_AML');
        //                $fromName = config('constants.MAIL_FROM_NAME_AML');
        //            } else {
        //                $fromEmail = config('constants.MAIL_FROM_ADDRESS');
        //                $fromName = config('constants.MAIL_FROM_NAME');
        //            }
        //            $amlUrl = $amlUrl ? $amlUrl : 'N/A';
        //            $amlQuoteStatus = $quoteStatusText ? $quoteStatusText : 'N/A';
        //            $clientFullName = $clientFullName ? $clientFullName : 'N/A';
        //            $quoteTypeName = $quoteTypeText ? $quoteTypeText : 'N/A';
        //            $quoteCdbId = $quoteCdbId ? $quoteCdbId : 'N/A';
        //            $htmlContent = View::make('AmlQuoteStatusUpdateMail', compact('amlUrl', 'amlQuoteStatus', 'clientFullName', 'quoteTypeName', 'quoteCdbId'))->render();
        //
        //            $ccEmail = array_map(function ($email) {
        //                return ['email' => $email];
        //            }, $ccRecipients);
        //
        //            $bodyData = [
        //                'sender' => ['name' => $fromName, 'email' => $fromEmail],
        //                'to' => [['email' => $toRecipient]],
        //                'subject' => $emailSubject,
        //                'htmlContent' => $htmlContent,
        //            ];
        //
        //            if (! empty($ccEmail)) {
        //                $bodyData['cc'] = $ccEmail;
        //            }
        //            $body = json_encode($bodyData, JSON_UNESCAPED_SLASHES);
        //            $client = new \GuzzleHttp\Client;
        //            $clientRequest = $client->post(
        //                $url,
        //                [
        //                    'headers' => $headers,
        //                    'body' => $body,
        //                    'timeout' => 10,
        //                ]
        //            );
        //
        //            $responseCode = $clientRequest->getStatusCode();
        //        } catch (Exception $ex) {
        //            $responseCode = $ex->getCode();
        //            Log::error('sendAmlQuoteStatusUpdateMail: '.$responseCode.'/'.$ex->getMessage());
        //        }
    }

    public static function getInsurerAMLStatuses(): array
    {
        return collect(AMLStatusCode::getStatuses())->filter(function ($value, $key) {
            return in_array($key, [
                AMLStatusCode::InsurerAMLScreeningPending,
                AMLStatusCode::InsurerAMLScreeningCleared,
                AMLStatusCode::InsurerAMLScreeningFailed,
            ]);
        })->toArray();
    }

    public function amlScreeningGIG($request, $quoteTypeId, $quoteDetails, $customerType)
    {
        LoggerService::info('__class__: '.self::class.' fn: '.__FUNCTION__);

        $modelObjectAgainstQuoteType = $this->getModelObject(QuoteTypes::getName($quoteTypeId)->value);
        $paymentDetails = Payment::with('insuranceProvider')->where([
            'paymentable_type' => $quoteDetails->getMorphClass(),
            'paymentable_id' => $quoteDetails->id,
        ])->first();

        // $rtaTransactionType = null;
        // if ($quoteTypeId == QuoteTypes::CAR->id()) {
        //     $carQuoteRequestDetails = CarQuoteRequestDetail::where('car_quote_request_id', $quoteDetails->id)->first();
        //     $rtaTransactionType = $carQuoteRequestDetails->rta_transaction_type ?? null;
        // }

        // $isRenewalUpload = $quoteTypeId == QuoteTypes::CAR->id() && $paymentDetails?->insuranceProvider?->code == InsuranceProvidersEnum::AXA && ($quoteDetails->source == LeadSourceEnum::RENEWAL_UPLOAD || $rtaTransactionType == 'RTT04');
        $isRenewalUpload = $quoteTypeId == QuoteTypes::CAR->id() && $paymentDetails?->insuranceProvider?->code == InsuranceProvidersEnum::AXA && $quoteDetails->source == LeadSourceEnum::RENEWAL_UPLOAD;

        if ($isRenewalUpload) {
            LoggerService::info('__class__: '.self::class.' fn: '.__FUNCTION__.' - Renewal upload quote. Ref-ID: '.$quoteDetails->code.' - Customer Type: '.$customerType.' - Processing without Update Quote API call');
        }

        if ($paymentDetails?->insuranceProvider?->code !== InsuranceProvidersEnum::AXA) {
            LoggerService::info('__class__: '.self::class.' fn: '.__FUNCTION__.' - Insurance provider is not ('.InsuranceProvidersEnum::AXA.'). Ref-ID: '.$quoteDetails->code.' - Customer Type: '.$customerType);

            return false;
        }

        LoggerService::info('__class__: '.self::class.' fn: '.__FUNCTION__.' - Ref-ID: '.$quoteDetails->code.' - Insurance Provider ID: '.$paymentDetails->insurance_provider_id);

        if ($paymentDetails->payment_methods_code !== PaymentMethodsEnum::CreditCard || $paymentDetails->payment_status_id !== PaymentStatusEnum::AUTHORISED) {
            LoggerService::info('__class__: '.self::class.' fn: '.__FUNCTION__.' - Payment Method is not CREDIT CARD or Payment Status is not AUTHORIZED. Ref-ID: '.$quoteDetails->code);

            return false;
        }

        LoggerService::info('__class__: '.self::class.' fn: '.__FUNCTION__.' - Payment Method is CREDIT CARD and Payment Status is AUTHORIZED- Ref-ID: '.$quoteDetails->code);
        $insuredPersonDetails = CustomerInsured::where([
            'quote_type_id' => $quoteTypeId,
            'quote_request_id' => $quoteDetails->id,
            'customer_id' => $quoteDetails->customer_id,
        ])->with(['customer', 'insured'])->latest('updated_at')->first();

        $screeningType = constant(AMLScreeningTypeEnum::class.'::'.'INSURER_'.$paymentDetails?->insuranceProvider?->code);

        // Handle renewal upload cases - By pass UpdateQuote API and call GetQuote API to filled data and proceed with auto capture
        if ($isRenewalUpload) {
            LoggerService::info('__class__: '.self::class.' fn: '.__FUNCTION__.' - Renewal upload - Bypassing Update Quote API call and calling GetQuote API to filled data and proceeding to auto capture - Ref-ID: '.$quoteDetails->code);

            try {
                $getQuoteResponse = $this->getQuoteDetailsFromInsurer($quoteTypeId, $quoteDetails->uuid);

                if ($getQuoteResponse['success']) {
                    LoggerService::info('__class__: '.self::class.' fn: '.__FUNCTION__.' - Successfully retrieved and updated quote details from insurer - Ref-ID: '.$quoteDetails->code);
                    $insurerAMLStatusForRenewalUpload = $getQuoteResponse['data']['uwApprovalStatus'] == 'Y' ? AMLStatusCode::AMLScreeningCleared : AMLStatusCode::AMLScreeningFailed;
                    $screeningResponse = [
                        'status' => $insurerAMLStatusForRenewalUpload,
                        'message' => 'Renewal upload - check insurer AML status after GetQuote API call',
                        'screening_type' => $screeningType,
                    ];
                } else {
                    LoggerService::info('__class__: '.self::class.' fn: '.__FUNCTION__.' - Failed to retrieve quote details from insurer - Ref-ID: '.$quoteDetails->code.' - Error: '.($getQuoteResponse['message'] ?? 'Unknown error'));
                    $screeningResponse = [
                        'status' => AMLStatusCode::AMLPending,
                        'message' => 'Renewal upload - Check Insurer AML status after GetQuote API call (GetQuote API failed: '.($getQuoteResponse['message'] ?? 'Unknown error').')',
                        'screening_type' => $screeningType,
                    ];
                }
            } catch (\Exception $e) {
                LoggerService::info('__class__: '.self::class.' fn: '.__FUNCTION__.' - Exception while calling getQuote API for renewal upload - Ref-ID: '.$quoteDetails->code.' - Error: '.$e->getMessage());
                $screeningResponse = [
                    'status' => AMLStatusCode::AMLPending,
                    'message' => 'Renewal upload - Check Insurer AML status after GetQuote API call (GetQuote API exception: '.$e->getMessage().')',
                    'screening_type' => $screeningType,
                ];
            }

            $this->updateInsurerKYCLogs($quoteTypeId, $quoteDetails, $modelObjectAgainstQuoteType, $customerType, $insuredPersonDetails, $screeningResponse);

            return true;
        }

        try {
            $insuredDetails = $insuredPersonDetails?->insured;
            $insuredKycDetails = $insuredDetails?->insuredKyc;

            $insurerScreeningPayload = [
                'quoteUID' => $quoteDetails->uuid,
                'quoteTypeId' => (int) $quoteTypeId,
                'emirateDetails' => [
                    'emirateId' => $insuredDetails?->id_type == 'emiratesId' ? str_replace('-', '', $insuredDetails?->id_number) : null,
                    'expiryDate' => ($insuredPersonDetails?->customer?->emirates_id_expiry_date ?? $request['id_expiry_date']) ?? null,
                ],
                'passportNumber' => $insuredDetails?->id_type == 'passport' ? $insuredDetails?->id_number : null,
                'chassisNumber' => $request['chassis_number'] ?? '',
                'gender' => $this->formatGender($insuredDetails?->gender),
                'dateOfBirth' => $insuredDetails?->dob,
                'getQuoteEmail' => $request['get_quote_email_gig'] ?? null,
                'insuredFirstName' => $insuredDetails?->first_name,
                'insuredLastName' => $insuredDetails?->last_name,
            ];

            if ($quoteTypeId == QuoteTypes::HOME->id()) {
                $insurerScreeningPayload['nationalityId'] = $request['nationality_id'] ?? null;
            }

            if ($quoteTypeId == QuoteTypes::CAR->id()) {
                $carQuoteRequestDetails = CarQuoteRequestDetail::where('car_quote_request_id', $quoteDetails->id)->first();
                $nationality = Nationality::where('code', $carQuoteRequestDetails?->home_country_license_issuance)->first();
                $rtaTransactionType = Lookup::where([
                    'key' => LookupsEnum::RTA_TRANSACTION_TYPE,
                    'insurance_provider_id' => $paymentDetails->insurance_provider_id,
                    'code' => $carQuoteRequestDetails->rta_transaction_type,
                ])->first();

                $rtaPlateCategory = Lookup::where([
                    'key' => LookupsEnum::RTA_PLATE_CATEGORY,
                    'insurance_provider_id' => $paymentDetails->insurance_provider_id,
                    'code' => $carQuoteRequestDetails->rta_plate_category,
                ])->first();

                $vehicleColor = Lookup::where([
                    'key' => LookupsEnum::VEHICLE_COLOR,
                    'insurance_provider_id' => $paymentDetails->insurance_provider_id,
                ])->whereIn('code', [$carQuoteRequestDetails->vehicle_color, $carQuoteRequestDetails->plate_color])->get()->pluck('text', 'code');

                $bankName = Lookup::where([
                    'key' => LookupsEnum::BANK_NAME,
                    'insurance_provider_id' => $paymentDetails->insurance_provider_id,
                    'code' => $carQuoteRequestDetails->bank_name,
                ])->first();

                $issuancePlace = Lookup::where([
                    'key' => LookupsEnum::ISSUANCE_PLACE,
                    'code' => $carQuoteRequestDetails->driver_license_issue_place,
                ])->first();

                $insurerScreeningPayload['rtaTransactionType'] = [
                    'code' => $carQuoteRequestDetails->rta_transaction_type ?? null,
                    'value' => $rtaTransactionType?->text ?? null,
                    'authority' => 'RTA',
                ];

                $insurerScreeningPayload['plateCodeNumber'] = $carQuoteRequestDetails->plate_code.$carQuoteRequestDetails->plate_number ?? null;
                $insurerScreeningPayload['trafficCodeNumber'] = $carQuoteRequestDetails->traffic_code_number ?? null;
                $insurerScreeningPayload['engineNumber'] = $carQuoteRequestDetails->engine_number ?? null;
                $insurerScreeningPayload['rtaPlateCategory'] = $rtaPlateCategory?->text ?? null;
                $insurerScreeningPayload['vehicleColor'] = [
                    'code' => $carQuoteRequestDetails->vehicle_color ?? null,
                    'value' => $vehicleColor[$carQuoteRequestDetails->vehicle_color] ?? null,
                ];
                $insurerScreeningPayload['plateColor'] = [
                    'code' => $carQuoteRequestDetails->plate_color ?? null,
                    'value' => $vehicleColor[$carQuoteRequestDetails->plate_color] ?? null,
                ];
                $insurerScreeningPayload['bankLoan'] = $carQuoteRequestDetails->bank_loan !== null ? (bool) $carQuoteRequestDetails->bank_loan : null;
                $insurerScreeningPayload['bankName'] = [
                    'code' => $carQuoteRequestDetails->bank_name ?? null,
                    'value' => $bankName?->text ?? null,
                ];
                $insurerScreeningPayload['firstRegistrationDate'] = $carQuoteRequestDetails->first_registration_date ?? null;
                $insurerScreeningPayload['policyEffectiveDate'] = $carQuoteRequestDetails->policy_effective_date ?? null;
                $insurerScreeningPayload['policyExpiryDate'] = $carQuoteRequestDetails->policy_expiry_date ?? null;
                $insurerScreeningPayload['certificateStartDate'] = $carQuoteRequestDetails->certificate_start_date ?? null;
                $insurerScreeningPayload['certificateEndDate'] = $carQuoteRequestDetails->certificate_end_date ?? null;
                $insurerScreeningPayload['annualMilageEstimation'] = $carQuoteRequestDetails->annual_mileage_estimate ?? null;
                $insurerScreeningPayload['driverName'] = trim(($carQuoteRequestDetails->driver_first_name ?? '').' '.($carQuoteRequestDetails->driver_last_name ?? '')) ?: null;
                $insurerScreeningPayload['driverDob'] = $carQuoteRequestDetails->driver_dob ?? null;
                $insurerScreeningPayload['driverGender'] = strtolower($this->formatGender($carQuoteRequestDetails->driver_gender)) ?? null;
                $insurerScreeningPayload['driverLicenseNumber'] = $carQuoteRequestDetails->driver_license_number ?? null;
                $insurerScreeningPayload['licenseIssuePlace'] = $issuancePlace?->text ?? null;
                $insurerScreeningPayload['licenseIssueDate'] = $carQuoteRequestDetails->driver_license_issue_date ?? null;
                $insurerScreeningPayload['licenseExpiryDate'] = $carQuoteRequestDetails->driver_license_expiry_date ?? null;
                $insurerScreeningPayload['uaeDrivingExperience'] = $carQuoteRequestDetails->driver_uae_driving_experience ?? null;
                $insurerScreeningPayload['homeCountryLicenseInsurance'] = $nationality?->text ?? null;
                $insurerScreeningPayload['homeCountryDrivingExperience'] = $carQuoteRequestDetails->home_country_driving_experience ?? null;
                $insurerScreeningPayload['insuredAndDriverSame'] = $carQuoteRequestDetails->is_insured_and_driver_same !== null ? (bool) $carQuoteRequestDetails->is_insured_and_driver_same : null;
            }

            LoggerService::info('__class__: '.self::class.' fn: '.__FUNCTION__.' - Insurer AML Screening API called - Ref-ID: '.$quoteDetails->code);
            $screeningResponse = Ken::request('/process-insurer-aml-screening', 'put', $insurerScreeningPayload);
            LoggerService::info('__class__: '.self::class.' fn: '.__FUNCTION__.' - GIG Screening Response - Ref-ID: '.$quoteDetails->code.' - response: '.json_encode($screeningResponse));
            $screeningResponse['screening_type'] = $screeningType;
            $this->updateInsurerKYCLogs($quoteTypeId, $quoteDetails, $modelObjectAgainstQuoteType, $customerType, $insuredPersonDetails, $screeningResponse);
        } catch (Exception $exception) {
            LoggerService::error('fn:amlScreeningGIG - GIG Screening failed - Ref-ID: '.$quoteDetails->code.' - Customer Type: '.$customerType.' - Error: '.$exception->getMessage());
            $screeningResponse = ['status' => AMLStatusCode::AMLPending, 'message' => $exception->getMessage(), 'screening_type' => $screeningType];
            $this->updateInsurerKYCLogs($quoteTypeId, $quoteDetails, $modelObjectAgainstQuoteType, $customerType, $insuredPersonDetails, $screeningResponse);

            return false;
        }
    }

    private function updateInsurerKYCLogs($quoteTypeId, $quoteDetails, $quoteObject, $customerType, $insuredPersonDetails, $screeningResponse): void
    {
        session()->push('insurerAMLScreeningResponse', $screeningResponse);
        $isScreeningCleared = $screeningResponse['status'] == AMLStatusCode::AMLScreeningCleared;
        $insurePersonName = $insuredPersonDetails?->insured?->first_name.($insuredPersonDetails?->insured?->last_name == 'NULL' || $insuredPersonDetails?->insured?->last_name == null ? '' : ' '.$insuredPersonDetails?->insured?->last_name);
        $kycLogDetails = [
            'quote_request_id' => $quoteDetails->id,
            'results' => json_encode($screeningResponse),
            'results_found' => (bool) $isScreeningCleared,
            'created_at' => Carbon::now(),
            'quote_type_id' => $quoteTypeId,
            'input' => $insurePersonName,
            'screening_type' => $screeningResponse['screening_type'],
            'search_type' => $customerType,
            'customer_code' => $insuredPersonDetails?->customer?->code ?? '',
        ];

        if ($isScreeningCleared) {
            LoggerService::info('fn:amlScreeningGIG - GIG AML Screening Cleared - Ref-ID: '.$quoteDetails->code.' - response: '.$screeningResponse['message'] ?? '');
            $kycLogDetails['match_found'] = 0;
            $kycLogDetails['decision'] = AMLDecisionStatusEnum::PASS;
            $insurerAMLStatus = ['insurer_aml_status' => AMLStatusCode::InsurerAMLScreeningCleared];
        } else {
            if ($screeningResponse['status'] == AMLStatusCode::AMLPending) {
                LoggerService::info('fn:amlScreeningGIG - GIG AML Screening Pending - Ref-ID: '.$quoteDetails->code.' - response: '.$screeningResponse['message'] ?? '');
                $kycLogDetails['match_found'] = 0;
                $kycLogDetails['decision'] = AMLDecisionStatusEnum::UNKNOWN;
                $insurerAMLStatus = ['insurer_aml_status' => AMLStatusCode::InsurerAMLScreeningPending];
            } else {
                LoggerService::info('fn:amlScreeningGIG - GIG AML Screening Failed - Ref-ID: '.$quoteDetails->code.' - response: '.$screeningResponse['message'] ?? '');
                $kycLogDetails['match_found'] = 1;
                $kycLogDetails['decision'] = AMLDecisionStatusEnum::ESCALATED;
                $insurerAMLStatus = ['insurer_aml_status' => AMLStatusCode::InsurerAMLScreeningFailed];

                LoggerService::info('fn:amlScreeningGIG - Going to dispatch AutomationFailedJob');
                AutomationFailedJob::dispatch(
                    $quoteDetails,
                    QuoteTypeId::Car,
                    'Please liaise with the Insurer UW or Insurar Portal to resolve the rejection',
                    'Quote Finalized But Premium Not Matched',
                    'Quote Finalization',
                    WorkflowTypeEnum::CAR_AUTOMATION_FAILED
                )->onQueue('policy-issuance-automation');
            }
        }

        KycLog::insert($kycLogDetails);
        LoggerService::info('fn:amlScreeningGIG - AML Screening GIG Potential Matches inserted into kyc_logs table - Ref-ID: '.$quoteDetails->code.' - Customer Type: '.$customerType);

        $quoteObject::where('id', $quoteDetails->id)->update($insurerAMLStatus);
        LoggerService::info('fn:amlScreeningGIG - Insurer AML Status updated in quote table - Ref-ID: '.$quoteDetails->code.' - Customer Type: '.$customerType);
        $quoteDetails->refresh();

        if ($quoteTypeId == QuoteTypes::CAR->id() && ($insurerAMLStatus['insurer_aml_status'] == AMLStatusCode::InsurerAMLScreeningCleared)) {
            $insurerAMLScreeningResponse = collect(session()->get('insurerAMLScreeningResponse', []))->first();
            $insurerAMLScreeningResponse['autoCaptureStatus'] = GenericRequestEnum::FAILED;

            LoggerService::info(__FUNCTION__.' - Auto Capture Payment Process Triggered - Ref-ID: '.$quoteDetails->code.' - Customer Type: '.$customerType);
            if (app(PolicyIssuanceService::class)->checkAllowedAutomations(QuoteTypes::getName($quoteTypeId)->value, $quoteDetails)) {
                $isAutoCaptureStarted = app(CentralService::class)->autoCapturePaymentProcess($quoteTypeId, $quoteDetails);

                $insurerAMLScreeningResponse['autoCaptureStatus'] = $isAutoCaptureStarted['autoCaptureStatus'];
                $insurerAMLScreeningResponse['autoCaptureMessage'] = $isAutoCaptureStarted['autoCaptureMessage'];
            }

            session()->put('insurerAMLScreeningResponse', [$insurerAMLScreeningResponse]);
        }
    }

    private function formatGender($gender)
    {
        if (in_array($gender, [GenericRequestEnum::MALE_SINGLE, GenericRequestEnum::MALE_SINGLE_VALUE, strtolower(GenericRequestEnum::MALE_SINGLE), strtolower(GenericRequestEnum::MALE_SINGLE_VALUE)])) {
            $gender = GenericRequestEnum::MALE_SINGLE;
        }
        if (in_array($gender, [GenericRequestEnum::FEMALE, GenericRequestEnum::FEMALE_SHORT_VALUE, strtolower(GenericRequestEnum::FEMALE), strtolower(GenericRequestEnum::FEMALE_SHORT_VALUE)])) {
            $gender = GenericRequestEnum::FEMALE;
        }

        return $gender;
    }

    public function getKYCLogs($quoteTypeId, $quoteRequestId)
    {
        LoggerService::info('fn:getKYCLogs - AMLService');

        return AML::with('quotetype')->where(['quote_request_id' => $quoteRequestId, 'quote_type_id' => $quoteTypeId])
            ->where(function ($aml) {
                $aml->whereNotIn('decision', [AMLDecisionStatusEnum::RYU]);
                $aml->orWhereNull('decision');
            })->whereNull('screenshot')->whereNotIn('screening_type', [AMLScreeningTypeEnum::INSURER_AXA])
            ->orderBy('created_at', 'asc')->get();
    }

    public function getAMLLookups($insuranceProviderId = null, $lookupsKeys = [])
    {
        LoggerService::info('fn:getAMLLookups - AMLService');

        if ($insuranceProviderId && ! empty($lookupsKeys)) {
            return Lookup::whereIn('key', $lookupsKeys)
                ->where('insurance_provider_id', $insuranceProviderId)
                ->get()
                ->groupBy('key')
                ->mapWithKeys(fn ($item, $key) => [str_replace('-', '_', $key) => $item]);
        }

        $lookupsForAML = [
            LookupsEnum::RESIDENT_STATUS,
            LookupsEnum::DOCUMENT_ID_TYPE,
            LookupsEnum::ENTITY_DOCUMENT_TYPE,
            LookupsEnum::MODE_OF_CONTACT,
            LookupsEnum::MODE_OF_DELIVERY,
            LookupsEnum::EMPLOYMENT_SECTOR,
            LookupsEnum::LEGAL_STRUCTURE,
            LookupsEnum::ISSUANCE_PLACE,
            LookupsEnum::ISSUING_AUTHORITY,
            LookupsEnum::COMPANY_POSITION,
            LookupsEnum::PROFESSIONAL_TITLE,
            LookupsEnum::UBO_RELATION,
            LookupsEnum::COMPANY_TYPE,
            LookupsEnum::MEMBER_RELATION,
        ];

        return Lookup::whereIn('key', $lookupsForAML)->get()->groupBy('key')
            ->mapWithKeys(fn ($item, $key) => [str_replace('-', '_', $key) => $item]);
    }

    public function getInsuredDetails($customerId, $quoteTypeId, $quoteRequestId)
    {
        LoggerService::info(self::class.' fn: '.__FUNCTION__);

        $customerInsured = CustomerInsured::where([
            'quote_type_id' => $quoteTypeId,
            'quote_request_id' => $quoteRequestId,
            'customer_id' => $customerId,
        ])
            ->with(['customer', 'insured', 'insured.insuredKyc'])
            ->latest('updated_at')
            ->first();

        if (! $customerInsured) {
            LoggerService::info('No CustomerInsured record found', [
                'customer_id' => $customerId,
                'quote_type_id' => $quoteTypeId,
                'quote_request_id' => $quoteRequestId,
            ]);
        }

        return $customerInsured;
    }

    // TODO:: This will remove when customer members mapping updated with insured id, this is also impacting on entity kyc form members data
    public function getEntityDetails($quoteTypeId, $quoteRequestId)
    {
        LoggerService::info(self::class.' fn: '.__FUNCTION__);

        return QuoteRequestEntityMapping::with(['entity', 'entity.quoteMember'])
            ->where(['quote_type_id' => $quoteTypeId, 'quote_request_id' => $quoteRequestId])
            ->first() ?? [];
    }

    public function prepareInsuredKycFormData($insuredKycRequest, $quote, $quoteType): bool
    {
        LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::AML_SCREENING);
        LoggerService::info(self::class.' fn: '.__FUNCTION__);

        try {
            if ($insuredKycRequest->customer_type == CustomerTypeEnum::Entity) {
                $data['corporation_country'] = Nationality::where('id', $insuredKycRequest->country_of_corporation)->value('country_name');
                $data['manager_country'] = Nationality::where('id', $insuredKycRequest->manager_nationality)->value('text');
                $data['industry_type_text'] = LookupRepository::where('code', $insuredKycRequest->industry_type)->where('key', LookupsEnum::COMPANY_TYPE)->value('text');
                $data['legal_structure_text'] = LookupRepository::where('code', $insuredKycRequest->legal_structure)->where('key', LookupsEnum::LEGAL_STRUCTURE)->value('text');
                $data['issuance_place_text'] = LookupRepository::where('code', $insuredKycRequest->place_of_issue)->where('key', LookupsEnum::ISSUANCE_PLACE)->value('text');
                $data['document_type_text'] = LookupRepository::where('code', $insuredKycRequest->id_type)->where('key', LookupsEnum::ENTITY_DOCUMENT_TYPE)->value('text');
                $data['issuing_authority_text'] = LookupRepository::where('code', $insuredKycRequest->issuing_authority)->where('key', LookupsEnum::ISSUING_AUTHORITY)->value('text');
                $data['manager_position_text'] = LookupRepository::where('code', $insuredKycRequest->manager_position)->where('key', LookupsEnum::UBO_RELATION)->value('text');
                $data['product_type'] = $quoteType.' Insurance';
                $data['document_type_code'] = DocumentTypeCode::KYCDOC;
                $data = array_merge($data, $insuredKycRequest->toArray());
                $pdf = PDF::loadView('pdf.kyc_entity_document', compact('data'))->setOptions(['defaultFont' => 'DejaVu Sans']);
                $pdf->setPaper('A4');
                $pdfFile = $pdf->output();

                $document = app(QuoteDocumentService::class)->uploadQuoteDocument($pdfFile, $data, $quote, true, false);
                $quoteTypeId = $insuredKycRequest->quote_type_id;
                if ($document) {
                    LoggerService::info('KYC Entity Document Uploaded Successfully');
                    Insured::find($insuredKycRequest->insured_id)->update([
                        'industry_type_code' => $insuredKycRequest->industry_type ?? null,
                    ]);

                    InsuredKyc::updateOrCreate(
                        ['insured_id' => $insuredKycRequest->insured_id], // Condition to find the record
                        [
                            'first_name' => $insuredKycRequest->first_name ?? null,
                            'last_name' => $insuredKycRequest->last_name ?? null,
                            'insured_id' => $insuredKycRequest->insured_id,
                            'website' => $insuredKycRequest->website ?? null,
                            'legal_structure' => $insuredKycRequest->legal_structure ?? null,
                            'country_of_corporation' => $insuredKycRequest->country_of_corporation ?? null,
                            'registered_address' => $insuredKycRequest->residential_address ?? null,
                            'communication_address' => $insuredKycRequest->communication_address ?? null,
                            'id_type' => $insuredKycRequest->id_type ?? null,
                            'id_number' => $insuredKycRequest->id_number ?? null,
                            'id_issuance_date' => $insuredKycRequest->id_issue_date ?? null,
                            'id_expiry_date' => $insuredKycRequest->id_expiry_date ?? null,
                            'issuance_place' => $insuredKycRequest->place_of_issue ?? null,
                            'id_issuance_authority' => $insuredKycRequest->issuing_authority ?? null,
                            'pep' => $insuredKycRequest->pep ?? null,
                            'financial_sanctions' => $insuredKycRequest->financial_sanctions ?? null,
                            'dual_nationality' => $insuredKycRequest->dual_nationality ?? null,
                            'in_sanction_list' => $insuredKycRequest->in_sanction_list ?? null,
                            'is_sanction_match' => $insuredKycRequest->is_sanction_match ?? null,
                            'in_fatf' => $insuredKycRequest->in_fatf ?? null,
                            'deal_sanction_list' => $insuredKycRequest->deal_sanction_list ?? null,
                            'is_operation_high_risk' => $insuredKycRequest->is_operation_high_risk ?? null,
                            'customer_tenure' => $insuredKycRequest->customer_tenure ?? null,
                            'transaction_pattern' => $insuredKycRequest->transaction_pattern ?? null,
                            'transaction_activities' => $insuredKycRequest->transaction_activities ?? null,
                            'mode_of_contact' => $insuredKycRequest->mode_of_contact ?? null,
                            'mode_of_delivery' => $insuredKycRequest->mode_of_delivery ?? null,
                            'transaction_volume' => $insuredKycRequest->transaction_volume ?? null,
                            'is_owner_high_risk' => $insuredKycRequest->is_owner_high_risk ?? null,
                        ]
                    );

                    QuoteMemberDetail::updateOrCreate([
                        'code' => $quote->quoteRequestEntityMapping->entity->code,
                        'customer_entity_id' => $quote->quoteRequestEntityMapping->entity->id,
                    ], [
                        'code' => $quote->quoteRequestEntityMapping->entity->code,
                        'customer_type' => CustomerTypeEnum::Entity,
                        'customer_entity_id' => $quote->quoteRequestEntityMapping->entity->id,
                        'quote_type_id' => $quoteTypeId,
                        'quote_request_id' => $quote->id,
                        'first_name' => $data['manager_name'],
                        'dob' => $data['manager_dob'],
                        'nationality_id' => $data['manager_nationality'],
                        'relation_code' => $data['manager_position'],
                    ]);
                    LoggerService::info('KYC Entity Details updated Successfully');
                }
            } else {
                $data['nationality_text'] = Nationality::where('id', $insuredKycRequest->nationality_id)->value('text');
                $data['country_name'] = Nationality::where('id', $insuredKycRequest->country_of_residence)->value('country_name');
                $data['birth_place'] = Nationality::where('id', $insuredKycRequest->place_of_birth)->value('country_name');
                $data['resident_status_text'] = LookupRepository::where('code', $insuredKycRequest->resident_status)->where('key', LookupsEnum::RESIDENT_STATUS)->value('text');
                $data['id_type_text'] = LookupRepository::where('code', $insuredKycRequest->id_type)->where('key', LookupsEnum::DOCUMENT_ID_TYPE)->value('text');
                $data['mode_of_contact_text'] = LookupRepository::where('code', $insuredKycRequest->mode_of_contact)->where('key', LookupsEnum::MODE_OF_CONTACT)->value('text');
                $data['mode_of_delivery_text'] = LookupRepository::where('code', $insuredKycRequest->mode_of_delivery)->where('key', LookupsEnum::MODE_OF_DELIVERY)->value('text');
                $data['employment_sector_text'] = LookupRepository::where('code', $insuredKycRequest->employment_sector)->where('key', LookupsEnum::EMPLOYMENT_SECTOR)->value('text');
                $data['company_position_text'] = LookupRepository::where('code', $insuredKycRequest->company_position)->where('key', LookupsEnum::COMPANY_POSITION)->value('text');
                $data['professional_title_text'] = LookupRepository::where('code', $insuredKycRequest->professional_title)->where('key', LookupsEnum::PROFESSIONAL_TITLE)->value('text');
                $data['premium'] = $quote->premium;
                $data['payment_method'] = isset($quote->payments[0]) ? $quote->payments[0]->paymentMethod->name : '';
                $data['product_type'] = ucfirst($quoteType).' Insurance';
                $data['document_type_code'] = DocumentTypeCode::KYCDOC;
                $data = array_merge($data, $insuredKycRequest->toArray());

                $pdf = PDF::loadView('pdf.kyc_individual_document', compact('data'))->setOptions(['defaultFont' => 'DejaVu Sans']);
                $pdf->setPaper('A4');
                $pdfFile = $pdf->output();

                $document = app(QuoteDocumentService::class)->uploadQuoteDocument($pdfFile, $data, $quote, true, false);

                if ($document) {
                    LoggerService::info('KYC Individual Document Uploaded Successfully');
                    InsuredKyc::updateOrCreate(
                        ['insured_id' => $insuredKycRequest->insured_id],
                        [
                            'first_name' => $insuredKycRequest->first_name ?? null,
                            'last_name' => $insuredKycRequest->last_name ?? null,
                            'insured_id' => $insuredKycRequest->insured_id,
                            'country_of_residence' => $insuredKycRequest->country_of_residence,
                            'place_of_birth' => $insuredKycRequest->place_of_birth,
                            'residential_status' => $insuredKycRequest->resident_status,
                            'residential_address' => $insuredKycRequest->residential_address,
                            'customer_tenure' => $insuredKycRequest->customer_tenure,
                            'id_type' => $insuredKycRequest->id_type,
                            'id_number' => $insuredKycRequest->id_number,
                            'id_issuance_date' => $insuredKycRequest->id_issue_date,
                            'id_expiry_date' => $insuredKycRequest->id_expiry_date,
                            'source_of_income' => $insuredKycRequest->income_source,
                            'employer_company_name' => $insuredKycRequest->company_name,
                            'job_title' => $insuredKycRequest->professional_title ?? null,
                            'employment_sector' => $insuredKycRequest->employment_sector ?? null,
                            'trade_license_no' => $insuredKycRequest->trade_license ?? null,
                            'position_in_company' => $insuredKycRequest->company_position ?? null,
                            'mode_of_contact' => $insuredKycRequest->mode_of_contact ?? null,
                            'mode_of_delivery' => $insuredKycRequest->mode_of_delivery ?? null,
                            'pep' => $insuredKycRequest->pep ?? null,
                            'financial_sanctions' => $insuredKycRequest->financial_sanctions ?? null,
                            'dual_nationality' => $insuredKycRequest->dual_nationality ?? null,
                            'transaction_pattern' => $insuredKycRequest->transaction_pattern ?? null,
                            'premium_tenure' => $insuredKycRequest->premium_tenure ?? null,
                            'in_sanction_list' => $insuredKycRequest->in_sanction_list ?? null,
                            'deal_sanction_list' => $insuredKycRequest->deal_sanction_list ?? null,
                            'is_operation_high_risk' => $insuredKycRequest->is_operation_high_risk ?? null,
                            'is_partner' => $insuredKycRequest->is_partner ?? null,
                        ]
                    );
                    LoggerService::info('KYC Individual Details updated Successfully');
                }
            }

            $quote->kyc_decision = Kyc::COMPLETE;
            $quote->save();
            LoggerService::info('Quote Kyc Decision updated Successfully');

            return true;
        } catch (\Exception $ex) {
            LoggerService::error($ex->getMessage());
        }

        return false;
    }

    public function tempSkipBridgerAML($skipBridgerScreeningRequest)
    {
        $return = ['status' => false, 'response' => 'AML Screening skipped process failed'];

        try {
            DB::transaction(function () use ($skipBridgerScreeningRequest) {
                $quoteDetails = $this->getQuoteObject($skipBridgerScreeningRequest->quote_type_code, $skipBridgerScreeningRequest->quote_request_id);
                LoggerService::info('fn:tempSkipBridgerAML - AML Screening skip process start - Ref-ID:'.$quoteDetails->code);

                QuoteStatusLog::create([
                    'quote_type_id' => $skipBridgerScreeningRequest->quote_type_id,
                    'quote_request_id' => $skipBridgerScreeningRequest->quote_request_id,
                    'current_quote_status_id' => QuoteStatusEnum::AMLScreeningCleared,
                    'previous_quote_status_id' => $quoteDetails->quote_status_id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                $quoteDetails->aml_status = AMLStatusCode::AMLScreeningCleared;
                $quoteDetails->save();

                ManualAMLLog::updateOrCreate([
                    'quote_type_id' => $skipBridgerScreeningRequest->quote_type_id,
                    'quote_uuid' => $skipBridgerScreeningRequest->quote_uuid,
                ], [
                    'created_by' => auth()->id(),
                ]);

                LoggerService::info('fn:tempSkipBridgerAML - AML Screening skip process completed - Ref-ID:'.$quoteDetails->code);
            });

            $return = ['status' => true, 'response' => 'AML Screening skipped for this quote'];
        } catch (\Exception $exception) {
            LoggerService::error('fn:tempSkipBridgerAML - AML Screening skip process failed - error - '.$exception->getMessage());

            $return = ['status' => true, 'response' => 'AML Screening skip process failed'];
        }

        return $return;
    }

    /**
     * Clears the AML status for non-AXA insurance providers.
     */
    public function clearAmlStatusForNonGIG($quoteType, $code, $providerCode)
    {
        LoggerService::info("Clearing AML status called. Quote Type: {$quoteType}, Code: {$code}, Insurance Provider: {$providerCode}");

        if ($providerCode == InsuranceProvidersEnum::AXA) {
            LoggerService::info("Insurance Provider is GIG(AXA). Skipping AML status clearing for Quote Code: {$code}");

            return false;
        }

        // Retrieve the quote details by quote type and code
        $quoteDetails = $this->getQuoteObjectBy($quoteType, $code, 'code');

        // Update the insurer AML status to null if it is not already null
        if ($quoteDetails->insurer_aml_status !== null) {
            $oldInsurerAmlStatus = $quoteDetails->insurer_aml_status;
            $quoteDetails->insurer_aml_status = null;
            $quoteDetails->save();

            LoggerService::info("AML status change from {$oldInsurerAmlStatus} to null for Quote Code: {$code}");
        }

        return true;
    }

    public function getAMLData($requestParams = [])
    {
        // This method is kept for backward compatibility
        $query = $this->getAMLQueryBuilder($requestParams);

        return $this->processAMLDataFromQuery($query);
    }

    public function getAMLQueryBuilder($requestParams = [])
    {
        if (! Auth::check()) {
            $user = $requestParams['user'] ?? null;
            unset($requestParams['user']);
            if ($user) {
                Auth::login($user);
            }
            DB::setDefaultConnection('mysql_read');
            request()->merge($requestParams);
        }

        return AML::select([
            'id',
            'quote_request_id',
            'quote_type_id',
            'input',
            'search_type',
            'match_found',
            'results_found',
            'created_at',
            'decision',
        ])
            ->where('decision', '!=', AMLDecisionStatusEnum::RYU)
            ->whereBetween('created_at', dateQueryFilter(request('amlCreatedStartDate'), request('amlCreatedEndDate')));
    }

    public function processAMLDataFromQuery($query)
    {
        $data = collect();

        $query->chunk(1000, function ($chunk) use (&$data) {
            $quoteTypeGroup = $chunk->groupBy('quote_type_id');
            foreach ($quoteTypeGroup as $quoteTypeId => $quoteTypeData) {
                $quoteType = QuoteTypes::getName($quoteTypeId);

                // Skip if quote type is not found
                if (! $quoteType) {
                    // LoggerService::warning("Quote type not found for ID: {$quoteTypeId}");

                    continue;
                }

                $nameSpace = '\\App\\Models\\';
                $model = checkPersonalQuotes(ucwords($quoteType->value)) ? $nameSpace.'PersonalQuote' : $nameSpace.ucwords($quoteType->value).'Quote';

                $distinctQuoteTypeIds = $quoteTypeData->pluck('quote_request_id')->unique();
                $quoteRequestData = $model::whereIn('id', $distinctQuoteTypeIds)->select(['id', 'uuid', 'aml_status'])->get();
                foreach ($quoteRequestData as $quoteRequest) {
                    $amlData = $chunk->where('quote_type_id', $quoteTypeId)->where('quote_request_id', $quoteRequest->id);
                    foreach ($amlData as $index => $value) {
                        $chunk[$index]['uuid'] = $quoteType->shortCode().$quoteRequest->uuid;
                        $chunk[$index]['aml_status'] = $quoteRequest->aml_status;
                    }
                }
            }
            $data = $data->merge($chunk);
        });

        return $data;
    }

    public function processInsuredDataForScreening($request, $quoteTypeId, $quote, $getLastScreening)
    {
        LoggerService::info(self::class.' fn: '.__FUNCTION__);

        $isEntity = $request->customer_type == CustomerTypeEnum::Entity;

        $insured = $this->createOrUpdateInsured($request, $isEntity);
        $this->updateInsuredInPersonalQuote($quoteTypeId, $quote, $insured);

        $isCustomerInsuredAssociationUpdated = $this->handleCustomerInsuredMappings($request, $quoteTypeId, $quote, $insured);
        $shouldApplicableForScreening = $this->shouldApplyScreening($insured, $isCustomerInsuredAssociationUpdated, $getLastScreening, $isEntity);

        $entityId = $this->handleLegacyEntityCustomerData($request, $quoteTypeId, $quote, $isEntity);

        return [$shouldApplicableForScreening, $insured, $entityId];
    }

    private function createOrUpdateInsured($request, bool $isEntity): Insured
    {
        if ($isEntity) {
            $insured = Insured::updateOrCreate([
                'customer_type' => CustomerTypeEnum::Entity,
                'trade_license_no' => $request->trade_license_no,
            ], [
                'company_name' => $request->company_name,
                'company_address' => $request->company_address,
                'industry_type_code' => $request->industry_type_code,
                'emirate_of_registration_id' => $request->emirate_of_registration_id,
            ]);
        } else {
            $insured = Insured::updateOrCreate([
                'customer_type' => CustomerTypeEnum::Individual,
                'id_type' => $request->screening_id_type,
                'id_number' => $request->screening_id_number,
            ], [
                'first_name' => $request->insured_first_name,
                'last_name' => $request->insured_last_name,
                'dob' => $request->dob,
                'nationality_id' => $request->nationality_id,
                'gender' => $request->screening_gender,
            ]);
        }

        $insured->refresh();

        return $insured;
    }

    public function updateInsuredInPersonalQuote($quoteTypeId, $quote, $insured)
    {
        $getPersonalQuote = PersonalQuote::where(['uuid' => $quote->uuid, 'quote_type_id' => $quoteTypeId])->first();
        if ($getPersonalQuote) {
            $getPersonalQuote->insured_id = $insured->id;
            $getPersonalQuote->save();
        }

        return $getPersonalQuote;
    }

    private function handleCustomerInsuredMappings($request, $quoteTypeId, $quote, $insured): bool
    {
        $isCustomerInsuredAssociationUpdated = false;

        // Check for orphaned record (without quote mapping) first
        $orphanedRecord = CustomerInsured::where([
            'customer_id' => $request->customer_id,
            'insured_id' => $insured->id,
        ])->whereNull('quote_type_id')
            ->whereNull('quote_request_id')
            ->first();

        // Create or update the customer-insured mapping
        if ($orphanedRecord) {
            // Update the existing orphaned record instead of deleting and creating new
            $isCustomerInsuredAssociationUpdated = true;
            $orphanedRecord->update([
                'quote_type_id' => $quoteTypeId,
                'quote_request_id' => $quote->id,
                'updated_at' => now(),
            ]);

            LoggerService::info('AML Screening Bridger - Updated orphaned customer_insured record', [
                'customer_insured_id' => $orphanedRecord->id,
                'customer_id' => $request->customer_id,
                'insured_id' => $insured->id,
                'quote_type_id' => $quoteTypeId,
                'quote_request_id' => $quote->id,
            ]);
        } else {
            // Check existing quote mapping
            $existingQuoteMapping = CustomerInsured::where([
                'customer_id' => $request->customer_id,
                'quote_type_id' => $quoteTypeId,
                'quote_request_id' => $quote->id,
            ])->orderBy('updated_at', 'desc')->first();

            if ($existingQuoteMapping && $existingQuoteMapping->insured_id !== $insured->id) {
                // Create new record or update existing quote mapping
                $isCustomerInsuredAssociationUpdated = true;
                CustomerInsured::updateOrCreate([
                    'customer_id' => $request->customer_id,
                    'insured_id' => $insured->id,
                    'quote_type_id' => $quoteTypeId,
                    'quote_request_id' => $quote->id,
                ], ['updated_at' => now()]);

                // Update quote status
                $quote->update(['kyc_decision' => Kyc::PENDING]);

                LoggerService::info('AML Screening Bridger - Insured association changed for quote', [
                    'old_insured_id' => $existingQuoteMapping->insured_id,
                    'new_insured_id' => $insured->id,
                    'quote_id' => $quote->id,
                ]);

            } elseif (! $existingQuoteMapping) {
                // This is a completely new quote-insured association
                $isCustomerInsuredAssociationUpdated = true;
                CustomerInsured::updateOrCreate([
                    'customer_id' => $request->customer_id,
                    'insured_id' => $insured->id,
                    'quote_type_id' => $quoteTypeId,
                    'quote_request_id' => $quote->id,
                ], ['updated_at' => now()]);

                LoggerService::info('AML Screening Bridger - New insured association created for quote', [
                    'insured_id' => $insured->id,
                    'quote_id' => $quote->id,
                ]);
            }
        }

        return $isCustomerInsuredAssociationUpdated;
    }

    private function shouldApplyScreening($insured, bool $isCustomerInsuredAssociationUpdated, $getLastScreening, bool $isEntity): bool
    {
        if ($insured->wasRecentlyCreated) {
            LoggerService::info('AML Screening Bridger - Insured '.($isEntity ? 'Entity' : 'Person').' profile created');

            return true;
        }

        if ($isCustomerInsuredAssociationUpdated) {
            LoggerService::info('AML Screening Bridger - Insured '.($isEntity ? 'Entity' : 'Person').' profile association changed for quote');

            return true;
        }

        if ($insured->isDirty() ||
            ! isset($getLastScreening->created_at) ||
            Carbon::parse($insured->updated_at) >= Carbon::parse($getLastScreening->created_at ?? '')) {
            LoggerService::info('AML Screening Bridger - Insured '.($isEntity ? 'Entity' : 'Person').' profile details updated');

            return true;
        }

        return false;
    }

    // TODO:: this function is added because universal search and customer members have dependency on customer and entity details.
    private function handleLegacyEntityCustomerData($request, $quoteTypeId, $quote, bool $isEntity): ?int
    {
        if ($isEntity) {
            return $this->handleEntityData($request, $quoteTypeId, $quote);
        }

        $this->updateCustomerData($request);

        return null;
    }

    private function handleEntityData($request, $quoteTypeId, $quote): int
    {
        $entityData = [
            'trade_license_no' => $request->trade_license_no,
            'company_name' => $request->company_name,
            'company_address' => $request->company_address,
            'industry_type_code' => $request->industry_type_code,
            'emirate_of_registration_id' => $request->emirate_of_registration_id,
        ];

        $entity = Entity::firstOrNew(['trade_license_no' => $request->trade_license_no]);
        $entity->fill($entityData);

        if (! $entity->exists) {
            $entity->save();
            $entity->update(['code' => CustomerTypeEnum::EntityShort.'-'.$entity->id]);
        } elseif ($entity->isDirty()) {
            $entity->save();
        }

        $entity->refresh();

        QuoteRequestEntityMapping::updateOrCreate([
            'quote_type_id' => $quoteTypeId,
            'quote_request_id' => $quote->id,
        ], [
            'entity_id' => $entity->id,
            'entity_type_code' => $request->entity_type_code,
        ]);

        return $entity->id;
    }

    private function updateCustomerData($request): void
    {
        $customer = Customer::with('nationality')->findOrFail($request->customer_id);

        $customer->fill([
            'nationality_id' => $request->nationality_id,
            'dob' => $request->dob,
            'insured_first_name' => $request->insured_first_name,
            'insured_last_name' => $request->insured_last_name,
        ]);

        if ($customer->isDirty()) {
            $customer->save();
        }
    }

    public function updatePAId($payload, $updateQuote)
    {
        // Update PA ID if user has appropriate roles
        if (auth()->user()?->hasAnyRole([RolesEnum::AML, RolesEnum::PA]) || ($payload['isAutomation'] && $payload['systemUser']?->hasAnyRole([RolesEnum::AML, RolesEnum::PA]))) {
            if (checkPersonalQuotes(ucwords($payload['quoteType']->code))) {
                AMLService::updatePaIdForPersonalQuotes((string) $payload['quoteType']->id, $payload['quoteRequestId'], true, ['pa_id' => $payload['processbyUser']->id]);
            } else {
                $updateQuote->pa_id = $payload['processbyUser']->id;
                $updateQuote->save();
            }
        }
    }

    public function generateAmlCftReport(array $requestParams = [])
    {
        LoggerService::info('fn:amlCtfReportExport - AMLController');

        // Debug: Log the received parameters
        \Illuminate\Support\Facades\Log::info('AMLService generateAmlCftReport Parameters:', $requestParams);

        // Create request object from parameters or use global request as fallback
        if (! empty($requestParams)) {
            $request = new \Illuminate\Http\Request($requestParams);
        } else {
            $request = request();
        }

        // Use get() method to access request parameters properly
        $startDate = $request->get('amlCreatedStartDate');
        $endDate = $request->get('amlCreatedEndDate');

        // Debug: Log the extracted dates and other filters
        \Illuminate\Support\Facades\Log::info('AMLService Extracted Filters:', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'searchType' => $request->get('searchType'),
            'searchField' => $request->get('searchField'),
            'quoteType' => $request->get('quoteType'),
        ]);

        $personalQuotes = $this->buildAmlCftReportQuery($request, $startDate, $endDate);

        // Initialize counters for incremental calculation
        $totalCustomers = 0;
        $highRisk = 0;   // 0-25 (lower scores = higher risk)
        $mediumRisk = 0; // 26-34
        $lowRisk = 0;    // 35+ (higher scores = lower risk)
        $data = collect();

        // Process chunks and calculate counts incrementally for better performance
        $personalQuotes->chunk(1000, function ($chunk) use (&$data, &$totalCustomers, &$highRisk, &$mediumRisk, &$lowRisk) {
            // Process each chunk and add data from different tables
            $processedChunk = $this->processAmlCftReportChunk($chunk);

            // Calculate risk counts for this chunk (optimized with single loop)
            foreach ($processedChunk as $record) {
                $totalCustomers++;
                $riskScore = $record->risk_score ?? null;

                // Categorize risk scores efficiently
                if ($riskScore !== null) {
                    match (true) {
                        $riskScore >= 0 && $riskScore <= 25 => $lowRisk++,
                        $riskScore >= 26 && $riskScore <= 34 => $mediumRisk++,
                        $riskScore >= 35 => $highRisk++,
                        default => null
                    };
                }
            }

            $data = $data->merge($processedChunk);
        });

        // Sort by customer name with nulls at the end (optimized single-pass sorting)
        $data = $data->sortBy(function ($item) {
            // Create a composite sort key for efficient sorting with null handling
            $firstName = $item->first_name ?? 'zzz_null';
            $lastName = $item->last_name ?? 'zzz_null';

            return strtolower($firstName.'|'.$lastName);
        })->values(); // Re-index the collection

        return [
            'collection' => $data,
            'summary' => [
                'total_customers' => $totalCustomers,
                'high_risk' => $highRisk,
                'medium_risk' => $mediumRisk,
                'low_risk' => $lowRisk,
            ],
        ];
    }

    public function buildAmlCftReportQuery(Request $request, ?string $startDate, ?string $endDate)
    {
        // Main select
        $personalQuotes = DB::table('personal_quotes as pqr')->select(
            'pqr.id',
            'pqr.uuid',
            'pqr.code',
            'pqr.quote_type_id',
            'pqr.aml_status',
            'pqr.policy_start_date',
            'pqr.policy_expiry_date',
            'pqr.premium',
            'pqr.price_with_vat',
            'pqr.policy_number',
            'pqr.quote_status_id',
            'pqr.insurance_provider_id',
            'pqr.risk_score as risk_score',
            'pqr.quote_id',
            'pqr.quote_type_id'
        )
            ->where('pqr.quote_status_id', QuoteStatusEnum::PolicyBooked)
            ->when(isset($startDate) && isset($endDate) && $startDate != 'null' && $endDate != 'null', function ($query) use ($startDate, $endDate) {
                $startDate = Carbon::parse($startDate)->startOfDay();
                $endDate = Carbon::parse($endDate)->endOfDay();
                $query->whereBetween('pqr.created_at', [$startDate, $endDate]);
            })
            ->when($request->get('searchType') && $request->get('searchType') === 'customerEmail', function ($query) use ($request) {
                $query->where('pqr.email', $request->get('searchField'));
            })
            ->when($request->get('searchType') && $request->get('searchType') === 'cdbId', function ($query) use ($request) {
                $query->where('pqr.code', $request->get('searchField'));
            })
            ->when($request->get('quoteType'), function ($query) use ($request) {
                $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($request->get('quoteType')));
                $query->where('pqr.quote_type_id', $quoteTypeId);
            })
            ->orderBy('pqr.id'); // Required for chunk() method

        return $personalQuotes;
    }

    private function processAmlCftReportChunk($chunk)
    {
        // Group chunk by quote_type_id for efficient processing
        $quoteTypeGroup = $chunk->groupBy('quote_type_id');

        foreach ($quoteTypeGroup as $quoteTypeId => $quoteTypeData) {
            $quoteType = QuoteTypes::getName($quoteTypeId);

            // Skip if quote type is not found
            if (! $quoteType) {
                continue;
            }

            // Get distinct quote request IDs for this quote type
            // Check if it's a personal quote type to determine which field to pluck
            $isPersonalQuote = checkPersonalQuotes(ucwords($quoteType->value));
            $distinctQuoteTypeIds = $quoteTypeData->pluck($isPersonalQuote ? 'id' : 'quote_id')->unique();

            // Add additional data from related tables
            $this->addQuoteStatusData($chunk, $distinctQuoteTypeIds, $quoteTypeId);
            $this->addInsuranceProviderData($chunk, $distinctQuoteTypeIds, $quoteTypeId);
            $this->addCustomerData($chunk, $distinctQuoteTypeIds, $quoteTypeId);
            $this->addInsuredKycData($chunk, $distinctQuoteTypeIds, $quoteTypeId);
            $this->addKycLogData($chunk, $distinctQuoteTypeIds, $quoteTypeId);

            // Add quote type specific data
            $this->addQuoteTypeSpecificData($chunk, $distinctQuoteTypeIds, $quoteTypeId, $quoteType);
        }

        return $chunk;
    }

    public function saveKYCComplianceQuestions($complianceQuestions)
    {
        LoggerService::info('fn:saveKYCComplianceQuestions - AMLService', extra: [
            'insured_id' => $complianceQuestions['insured_id'],
        ]);

        try {
            $sameFields = [
                'pep' => $complianceQuestions['pep'] ?? null,
                'financial_sanctions' => $complianceQuestions['financial_sanctions'] ?? null,
                'dual_nationality' => $complianceQuestions['dual_nationality'] ?? null,
                'in_sanction_list' => $complianceQuestions['in_sanction_list'] ?? null,
                'deal_sanction_list' => $complianceQuestions['deal_sanction_list'] ?? null,
                'is_operation_high_risk' => $complianceQuestions['is_operation_high_risk'] ?? null,
                'transaction_pattern' => $complianceQuestions['transaction_pattern'] ?? null,
                'is_partner' => $complianceQuestions['is_partner'] ?? null,
            ];

            $kycData = array_merge($sameFields, [
                'insured_id' => $complianceQuestions['insured_id'],
                'is_sanction_match' => $complianceQuestions['is_sanction_match'] ?? null,
                'in_fatf' => $complianceQuestions['in_fatf'] ?? null,
                'is_owner_high_risk' => $complianceQuestions['is_owner_high_risk'] ?? null,
                'transaction_volume' => $complianceQuestions['transaction_volume'] ?? null,
                'transaction_activities' => $complianceQuestions['transaction_activities'] ?? null,
            ]);

            if ($insuredKyc = InsuredKyc::where('insured_id', $complianceQuestions['insured_id'])->first()) {
                $insuredKyc->update($kycData);
            } else {
                InsuredKyc::create($kycData);
            }

            LoggerService::info('KYCComplianceQuestions updated');
        } catch (Exception $exception) {
            LoggerService::error('KYCComplianceQuestions failed to update', exception: $exception);
        }
    }

    public function updateAMLStatusAgainstDecision($request, $quoteObject)
    {
        LoggerService::info(self::class.' - '.__FUNCTION__);

        $fetchKycLog = KycLog::where('id', $request['aml_id'])->withTrashed();
        $fetchKycLog->update([
            'decision' => $request['aml_decision'] ?? '',
            'notes' => isset($request['notes']) ? trim($request['notes']) : '',
            'in_adverse_media' => isset($request['in_adverse_media']) ? trim($request['in_adverse_media']) : '',
            'is_owner_pep' => isset($request['is_owner_pep']) ? trim($request['is_owner_pep']) : '',
            'is_controlling_pep' => isset($request['is_controlling_pep']) ? trim($request['is_controlling_pep']) : '',
        ]);

        $kycLog = $fetchKycLog->first();
        $amlStatus = (AMLService::checkAMLStatusFailed($kycLog->quote_type_id, $kycLog->quote_request_id)) ? AMLStatusCode::AMLScreeningFailed : AMLStatusCode::AMLScreeningCleared;

        $quoteObject->aml_status = $amlStatus;
        $quoteObject->save();

        return $amlStatus == AMLStatusCode::AMLScreeningCleared ?
                            AMLStatusCode::getName(AMLStatusCode::AMLScreeningCleared) : AMLStatusCode::getName(AMLStatusCode::AMLScreeningFailed);
    }

    private function addQuoteStatusData($chunk, $quoteIds, $quoteTypeId)
    {
        $quoteStatuses = DB::table('quote_status')
            ->whereIn('id', $chunk->where('quote_type_id', $quoteTypeId)->pluck('quote_status_id')->unique())
            ->pluck('text', 'id');

        foreach ($chunk as $index => $record) {
            if ($record->quote_type_id == $quoteTypeId) {
                $chunk[$index]->lead_status = $quoteStatuses[$record->quote_status_id] ?? null;
            }
        }
    }

    private function addInsuranceProviderData($chunk, $quoteIds, $quoteTypeId)
    {
        $insuranceProviders = DB::table('insurance_provider')
            ->whereIn('id', $chunk->where('quote_type_id', $quoteTypeId)->pluck('insurance_provider_id')->unique())
            ->pluck('text', 'id');

        foreach ($chunk as $index => $record) {
            if ($record->quote_type_id == $quoteTypeId) {
                $chunk[$index]->insurance_provider = $insuranceProviders[$record->insurance_provider_id] ?? null;
            }
        }
    }

    private function addCustomerData($chunk, $quoteIds, $quoteTypeId)
    {
        // Get customer insured data with insured_id (optimized with single query)
        $customerInsured = DB::table('customer_insured as ci')
            ->select(['ci.quote_request_id', 'ci.insured_id', 'ci.customer_id', 'ci.quote_type_id'])
            ->whereIn('ci.quote_request_id', $quoteIds)
            ->where('ci.quote_type_id', $quoteTypeId)
            ->get()
            ->keyBy('quote_request_id');

        // Check if this quote type is a personal quote
        $quoteType = QuoteTypes::getName($quoteTypeId);
        $isPersonalQuote = $quoteType ? checkPersonalQuotes(ucwords($quoteType->value)) : false;

        // Add insured_id to chunk records with batch processing
        foreach ($chunk as $index => $record) {
            if ($record->quote_type_id == $quoteTypeId) {
                // Use appropriate field based on quote type
                $lookupKey = $isPersonalQuote ? $record->id : $record->quote_id;
                $customerInsuredRecord = $customerInsured[$lookupKey] ?? null;
                $chunk[$index]->insured_id = $customerInsuredRecord->insured_id ?? null;
                $chunk[$index]->customer_id = $customerInsuredRecord->customer_id ?? null;
            }
        }
    }

    private function addInsuredKycData($chunk, $quoteIds, $quoteTypeId)
    {
        // Get insured IDs from chunk (after customer data has been added)
        $insuredIds = $chunk->where('quote_type_id', $quoteTypeId)
            ->whereNotNull('insured_id')
            ->pluck('insured_id')
            ->unique()
            ->filter(); // Remove null values

        if ($insuredIds->isEmpty()) {
            return;
        }

        // Get data from insured_kyc table using insured_ids
        $insuredKycData = DB::table('insured_kyc as ik')
            ->select([
                'ik.insured_id', 'ik.first_name', 'ik.last_name', 'ik.id_number',
                'ik.residential_status', 'ik.premium_tenure', 'ik.transaction_volume',
                'ik.pep', 'ik.country_of_residence', 'ik.place_of_birth',
                'ik.dual_nationality', 'ik.financial_sanctions', 'ik.in_sanction_list',
            ])
            ->whereIn('ik.insured_id', $insuredIds)
            ->get()
            ->keyBy('insured_id');

        // Get customer_type from insured table
        $insuredData = DB::table('insured as i')
            ->select(['i.id', 'i.customer_type', 'i.first_name', 'i.last_name'])
            ->whereIn('i.id', $insuredIds)
            ->get()
            ->keyBy('id');

        // Add insured_kyc data to chunk records
        foreach ($chunk as $index => $record) {
            if ($record->quote_type_id == $quoteTypeId) {
                $kycRecord = null;
                $insuredRecord = null;

                if (isset($record->insured_id) && $record->insured_id) {
                    $kycRecord = $insuredKycData[$record->insured_id] ?? null;
                    $insuredRecord = $insuredData[$record->insured_id] ?? null;
                }

                $chunk[$index]->first_name = $kycRecord->first_name ?? $insuredRecord->first_name ?? null;
                $chunk[$index]->last_name = $kycRecord->last_name ?? $insuredRecord->last_name ?? null;
                $chunk[$index]->emirates_id = $kycRecord->id_number ?? null;
                $chunk[$index]->customer_type = $insuredRecord->customer_type ?? CustomerTypeEnum::Individual;
                $chunk[$index]->residential_status = $kycRecord->residential_status ?? null;
                $chunk[$index]->premium_tenure = $kycRecord->premium_tenure ?? null;
                $chunk[$index]->transaction_volume = $kycRecord->transaction_volume ?? null;
                $chunk[$index]->is_owner_pep = $kycRecord->pep ?? null;
            }
        }
    }

    private function addKycLogData($chunk, $quoteIds, $quoteTypeId)
    {
        // Get latest KYC log data using quote_request_id and quote_type_id
        $kycLogs = DB::table('kyc_logs as kl')
            ->select([
                'kl.quote_request_id', 'kl.quote_type_id', 'kl.created_at',
                'kl.notes', 'kl.decision', 'kl.match_found', 'kl.results_found',
                'kl.is_owner_pep', 'kl.screening_type',
            ])
            ->whereIn('kl.quote_request_id', $quoteIds)
            ->where('kl.quote_type_id', $quoteTypeId)
            ->where('kl.decision', '!=', AMLDecisionStatusEnum::RYU)
            ->where('kl.decision', '!=', AMLDecisionStatusEnum::INSURER_AXA)
            ->orderBy('kl.created_at', 'desc')
            ->get()
            ->groupBy('quote_request_id')
            ->map->first(); // Get the latest record for each quote

        // Check if it's a personal quote type to determine which field to use
        $quoteType = QuoteTypes::getName($quoteTypeId);
        $isPersonalQuote = $quoteType ? checkPersonalQuotes(ucwords($quoteType->value)) : false;

        // Add KYC log data to chunk records
        foreach ($chunk as $index => $record) {
            if ($record->quote_type_id == $quoteTypeId) {
                // Use appropriate field based on quote type
                $lookupKey = $isPersonalQuote ? $record->id : $record->quote_id;
                $kycLog = $kycLogs[$lookupKey] ?? null;

                $chunk[$index]->last_aml_screening_date = $kycLog->created_at ?? null;
                $chunk[$index]->remarks = $kycLog->notes ?? null;
            }
        }
    }

    private function addQuoteTypeSpecificData($chunk, $quoteIds, $quoteTypeId, $quoteType)
    {
        // Add quote type specific data based on the quote type
        // This can be extended for specific quote types
        foreach ($chunk as $index => $record) {
            if ($record->quote_type_id == $quoteTypeId) {
                $chunk[$index]->quote_type_name = $quoteType->value;
                $chunk[$index]->quote_code_prefix = $quoteType->shortCode();
            }
        }
    }

    /**
     * Get the model class name(s) based on QuoteTypes id(s).
     *
     * @return string|array
     */
    public static function getModelTypeByQuoteTypeId(int|array $quoteTypeIds)
    {
        $nameSpace = 'App\\Models\\';
        $resolveModel = function ($id) use ($nameSpace) {
            $quoteType = QuoteTypes::getName($id);
            if (! $quoteType) {
                throw new \InvalidArgumentException("Invalid QuoteTypeId: $id");
            }

            return checkPersonalQuotes(ucwords($quoteType->value))
                ? $nameSpace.'PersonalQuote'
                : $nameSpace.ucwords($quoteType->value).'Quote';
        };
        if (is_array($quoteTypeIds)) {
            return array_map($resolveModel, $quoteTypeIds);
        }

        return $resolveModel($quoteTypeIds);
    }

    /**
     * Get quote type ID to model mapping for SQL CASE statements.
     */
    private function getQuoteTypeModelMapping(array $personalQuoteTypeIds = []): string
    {
        $personalMapping = [
            11 => 'BikeQuote', // QuoteTypes::BIKE
            12 => 'CycleQuote', // QuoteTypes::CYCLE
            13 => 'JetskiQuote', // QuoteTypes::JETSKI
            14 => 'PetQuote', // QuoteTypes::PET
            15 => 'YachtQuote', // QuoteTypes::YACHT
            16 => 'HomeQuote', // QuoteTypes::HOME
        ];

        $nonPersonalMapping = [
            1 => 'CarQuote', // QuoteTypes::CAR
            2 => 'HealthQuote', // QuoteTypes::HEALTH
            3 => 'LifeQuote', // QuoteTypes::LIFE
            4 => 'BusinessQuote', // QuoteTypes::BUSINESS
            5 => 'TravelQuote', // QuoteTypes::TRAVEL
        ];

        $mapping = empty($personalQuoteTypeIds) ? $nonPersonalMapping : $personalMapping;

        $cases = [];
        foreach ($mapping as $id => $model) {
            $cases[] = "WHEN pqr.quote_type_id = {$id} THEN \"App\\\\\\\\Models\\\\\\\\{$model}\"";
        }

        return implode(' ', $cases).' ELSE "App\\\\\\\\Models\\\\\\\\PersonalQuote"';
    }

    public function saveAdditionalVehicleAndDriverDetails($request, $quote)
    {
        LoggerService::info(__FUNCTION__.' - Execution Started');
        try {
            if ($request->has('additional_vehicle_transaction_details')) {
                $updateCarQuoteRequestDetail = [
                    'rta_transaction_type' => $request->rta_transaction_type,
                    'plate_code' => $request->plate_code,
                    'plate_number' => $request->plate_number,
                    'traffic_code_number' => $request->traffic_code_number,
                    'chassis_number' => $request->chassis_number,
                    'engine_number' => $request->engine_number,
                    'rta_plate_category' => $request->rta_plate_category,
                    'vehicle_color' => $request->vehicle_color,
                    'plate_color' => $request->plate_color,
                    'bank_loan' => $request->bank_loan,
                    'bank_name' => $request->bank_name,
                    'first_registration_date' => $request->first_registration_date,
                    'policy_effective_date' => $request->policy_effective_date,
                    'policy_expiry_date' => $request->policy_expiry_date,
                    'certificate_start_date' => $request->certificate_start_date,
                    'certificate_end_date' => $request->certificate_end_date,
                    'annual_mileage_estimate' => $request->annual_mileage_estimate,
                ];
                $message = 'Additional Vehicle Transaction Details saved successfully';
            } else {
                $updateCarQuoteRequestDetail = [
                    'is_insured_and_driver_same' => $request->is_insured_and_driver_same,
                    'driver_first_name' => $request->driver_first_name,
                    'driver_last_name' => $request->driver_last_name,
                    'driver_dob' => $request->driver_dob,
                    'driver_gender' => $request->driver_gender,
                    'driver_license_number' => $request->driver_license_number,
                    'driver_license_issue_place' => $request->license_issue_place,
                    'driver_license_issue_date' => $request->license_issue_date,
                    'driver_license_expiry_date' => $request->license_expiry_date,
                    'driver_uae_driving_experience' => $request->uae_driving_experience,
                    'home_country_license_issuance' => $request->home_country_license_issuance,
                    'home_country_driving_experience' => $request->home_country_driving_experience,
                ];
                $message = 'Additional Driver Details saved successfully';
            }

            CarQuoteRequestDetail::where('car_quote_request_id', $quote->id)->update($updateCarQuoteRequestDetail);
            $response = ['status' => true, 'message' => $message];
            LoggerService::info(__FUNCTION__.' - '.$message);

        } catch (\Exception $ex) {
            LoggerService::info(__FUNCTION__.' - Error saving additional vehicle and driver details', $ex->getMessage());
            $response = ['status' => false, 'message' => 'Failed to save additional vehicle and driver details'];
        }

        return $response;
    }

    public function autoCaptureAMLValidationCheck($quote)
    {
        if ($quote->aml_status != AMLStatusCode::AMLScreeningCleared) {
            LoggerService::info(__FUNCTION__.' - Auto capture payment process failed - AML Screening is not cleared');

            return false;
        }

        if ($quote->source !== LeadSourceEnum::RENEWAL_UPLOAD && $quote->insurer_aml_status != AMLStatusCode::InsurerAMLScreeningCleared) {
            LoggerService::info(__FUNCTION__.' - Auto capture payment process failed - Insurer AML Screening is not cleared');

            return false;
        }

        return true;
    }

    /**
     * Check if insurer sync is enabled for the given quote type and request.
     */
    public function isInsurerSyncEnabled($quoteType, $quote): bool
    {
        $insurerScreenType = [
            InsuranceProvidersEnum::AXA => AMLScreeningTypeEnum::INSURER_AXA,
            InsuranceProvidersEnum::RSA => AMLScreeningTypeEnum::INSURER_RSA,
        ];
        $payment = $quote->payments()->mainLeadPayment()->first();
        $insuranceProvider = getInsuranceProvider($payment, $quoteType->text);

        if (! in_array($insuranceProvider?->code, array_keys($insurerScreenType))) {
            return false;
        }

        $kycLogs = KycLog::withTrashed()->where([
            'quote_request_id' => $quote->id,
            'quote_type_id' => $quoteType->id,
        ])->where('screening_type', $insurerScreenType[$insuranceProvider->code])->latest()->first();

        if (! $kycLogs) {
            return false;
        }

        $screeningResult = json_decode($kycLogs->results);

        if (! isset($screeningResult->uwApprovalStatus, $screeningResult->quoteStatus)) {
            return false;
        }

        return $screeningResult->uwApprovalStatus === GenericRequestEnum::EBAO_UW_APPROVAL_STATUS_NO
            && $screeningResult->quoteStatus === GenericRequestEnum::EBAO_QUOTE_STATUS;
    }

    public function getQuoteDetailsFromInsurer($quoteTypeId, $quoteUID)
    {
        try {
            $quoteType = QuoteTypes::getName($quoteTypeId)->value;
            $quoteDetails = $this->getQuoteObjectBy($quoteType, $quoteUID, 'uuid');
            $insurerCode = getInsuranceProvider($quoteDetails->payments()->mainLeadPayment()->first(), $quoteType);

            return match (ucfirst($quoteType)) {
                QuoteTypes::CAR->value => match ($insurerCode->code) {
                    InsuranceProvidersEnum::AXA => app(GIGInsuranceService::class)->getQuoteDetailsFromInsurer($quoteTypeId, $quoteDetails),

                    default => [
                        'success' => false,
                        'message' => 'Insurer not supported for quote type: ' . $quoteType,
                        'data' => null
                    ],
                },
                default => [
                    'success' => false,
                    'message' => 'Quote type not supported: ' . $quoteType,
                    'data' => null
                ],
            };
        } catch (\Exception $e) {
            LoggerService::info('__class__: '.self::class.' fn: '.__FUNCTION__.' - Exception: '.$e->getMessage().' - QuoteUID: '.$quoteUID);

            return [
                'success' => false,
                'message' => 'Exception occurred: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
}
