<?php

namespace App\Services;

use App\Enums\AMLDecisionStatusEnum;
use App\Enums\CustomerTypeEnum;
use App\Enums\EnvEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Models\AML;
use App\Models\BikeQuote;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\CycleQuote;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\JetskiQuote;
use App\Models\KycLog;
use App\Models\LifeQuote;
use App\Models\PersonalQuote;
use App\Models\PetQuote;
use App\Models\TravelQuote;
use App\Models\User;
use App\Models\YachtQuote;
use App\Repositories\CustomerMembersRepository;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;

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
            QuoteTypes::YACHT->id() => $quoteRequestId
        };
    }

    public static function updatePaIdForPersonalQuotes($quoteTypeId, $quoteRequestId, $isMigrated, $updateData = '')
    {
        $filterColumn = $isMigrated ? 'personal_quote_id' : 'id';
        $updateData = empty($updateData) ? ['pa_id' => auth()->id()] : $updateData;

        return match ($quoteTypeId) {
            QuoteTypes::BIKE->id() => BikeQuote::where($filterColumn, $quoteRequestId)->update($updateData),
            QuoteTypes::CYCLE->id() => CycleQuote::where($filterColumn, $quoteRequestId)->touch(),
            QuoteTypes::JETSKI->id() => JetskiQuote::where($filterColumn, $quoteRequestId)->touch(),
            QuoteTypes::PET->id() => PetQuote::where($filterColumn, $quoteRequestId)->update($updateData),
            QuoteTypes::YACHT->id() => YachtQuote::where($filterColumn, $quoteRequestId)->update($updateData)
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
            ])->where('id', $quoteRequestId)->firstOrFail();
        } elseif ($quoteTypeId == QuoteTypes::HOME->id()) {
            $quoteRequestDetails = HomeQuote::with([
                'quoteStatus',
                'payments.paymentMethod',
                'payments.getCustomerPaymentInstrument',
                'paymentStatus',
                'customer.detail',
                'possessionType',
                'accommodationType',
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
            $quoteRequestDetails = LifeQuote::with([
                'quoteStatus',
                'payments.paymentMethod',
                'payments.getCustomerPaymentInstrument',
                'paymentStatus',
                'customer.detail',
                'purposeOfInsurance',
                'children',
                'maritalStatus',
                'insuranceTenure',
                'numberOfYears',
                'currency',
                'nationality',
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

    public static function sendAMLMatchedEmailtoComplianceTeam($amlQuoteUrl, $quoteRefId, $amlResultCount, $customerOrEntityName, $quoteType, $loginUserEmail, $forComplianceSuperUser = false)
    {
        $emailRecipients = [];
        $emailSystem = config('constants.APP_ENV');
        $complianceRole = $forComplianceSuperUser ? [RolesEnum::ComplianceSuperUser] : [RolesEnum::COMPLIANCE, RolesEnum::ComplianceSuperUser];
        $recipients = User::select('users.email as user_email')
            ->leftjoin('model_has_roles', 'users.id', 'model_has_roles.model_id')
            ->leftjoin('roles', 'model_has_roles.role_id', 'roles.id')
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
            function ($message) use ($emailSubject, $emailRecipients, $fromName, $fromEmail, $loginUserEmail, $forComplianceSuperUser) {
                $message->to($emailRecipients);
                if (in_array($loginUserEmail, $emailRecipients) || ! $forComplianceSuperUser) {
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
    //            info('sendAmlComplianceMail ---- Received Code : '.$responseCode);
    //        } catch (Exception $ex) {
    //            $responseCode = $ex->getCode();
    //            $responseDetail = 'sendAmlComplianceMail: Code/Message: '.$responseCode.'/'.$ex->getMessage();
    //        }
    //    }

    public static function getMemberOrUBODetails($request, $quoteType, $quoteRequestId)
    {
        $membersFor = ($request->customer_type == CustomerTypeEnum::Entity) ? CustomerTypeEnum::Entity : CustomerTypeEnum::Individual;

        return CustomerMembersRepository::getBy($quoteRequestId, $quoteType->code, $membersFor);
    }

    public static function updateAMLDecisionLexisNexis($request)
    {
        if (! $request->result_id) {
            info('AML Screening Bridger - Bridger Decision update API Call - Result Id not found');

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
            ->whereNull('screenshot')
            ->orderBy('id', 'desc')
            ->value('splitted_customer_code');

        if ($status == null && $quoteTypeId == QuoteTypes::BUSINESS->id()) {
            $status = CustomerTypeEnum::EntityShort;
        } elseif ($status == null && $quoteTypeId != QuoteTypes::BUSINESS->id()) {
            $status = CustomerTypeEnum::IndividualShort;
        }

        return $status;
    }

    public static function checkAMLStatusFailed($quoteTypeId, $quoteRequestId)
    {
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
}
