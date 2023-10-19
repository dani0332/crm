<?php

namespace App\Services;

use App\Enums\CustomerTypeEnum;
use App\Enums\EnvEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Models\AML;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\HealthMemberDetail;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\LifeQuote;
use App\Models\PersonalQuote;
use App\Models\PetQuote;
use App\Models\TravelMemberDetail;
use App\Models\TravelQuote;
use App\Models\User;
use App\Repositories\QuoteMemberDetailsRepository;
use Carbon\Carbon;
use Config;
use Illuminate\Support\Facades\Mail;

class AMLService
{
    public static function isDataMigrated($quoteTypeId, $quoteRequestId = '', $parseDate = ''): bool
    {
        $createdDate = $parseDate;
        if (empty($parseDate)) {
            $createdDate = AML::where(['quote_request_id' => $quoteRequestId, 'quote_type_id' => $quoteTypeId])->firstOrFail()->created_at;
        }

        $dataMigrationDate = match ( (int) $quoteTypeId ) {
            (int) QuoteTypes::PET->id() => Carbon::createFromFormat('Y-m-d', '2023-08-14'),
        };

        return Carbon::createFromFormat(
            config('constants.DATE_FORMAT_ONLY'),
            Carbon::parse($createdDate)->format(config('constants.DATE_FORMAT_ONLY'))
        )->gte($dataMigrationDate);
    }

    public static function getPersonalQuoteId($quoteTypeId, $quoteRequestId)
    {
        return match ($quoteTypeId) {
            QuoteTypes::PET->id() => PetQuote::where('id', $quoteRequestId)->firstOrFail()->personal_quote_id
        };
    }

    public static function updatePaIdForPersonalQuotes($quoteTypeId, $quoteRequestId, $isMigrated, $updateData = '')
    {
        $filterColumn = $isMigrated ? 'personal_quote_id' : 'id';
        $updateData = empty($updateData) ? ['pa_id' => auth()->id()] : $updateData;

        return match ($quoteTypeId) {
            QuoteTypes::PET->id() => PetQuote::where($filterColumn, $quoteRequestId)->update($updateData)
        };
    }

    public static function getQuoteDetails($quoteTypeId, $quoteRequestId)
    {
        $migratedQuoteTypes = [QuoteTypes::PET->id()];
        $isDataMigrated = true;
        $quoteRequestDetails = [];

        if (in_array($quoteTypeId, $migratedQuoteTypes)) {
            $checkAMLService = new CheckAmlService();
            $isDataMigrated = $checkAMLService->isDataMigrated($quoteTypeId, $quoteRequestId);

            if(!$isDataMigrated)
                $quoteRequestId = $checkAMLService->getPersonalQuoteId($quoteTypeId, $quoteRequestId);
        }

        if($quoteTypeId == QuoteTypes::CAR->id()) {
            $quoteRequestDetails = CarQuote::with([
                'quoteStatus',
                'paymentStatus',
                'customer',
                'uaeLicenseHeldFor',
                'carMake',
                'carModel',
                'emirate',
                'carTypeInsurance',
                'claimHistory',
                'nationality'
            ])->where('id', $quoteRequestId)->firstOrFail();

        } elseif ($quoteTypeId == QuoteTypes::HOME->id()) {
            $quoteRequestDetails = HomeQuote::with([
                'quoteStatus',
                'paymentStatus',
                'customer',
                'possessionType',
                'accommodationType'
            ])->where('id', $quoteRequestId)->firstOrFail();

        } elseif ($quoteTypeId == QuoteTypes::HEALTH->id()) {
            $quoteRequestDetails = HealthQuote::with([
                'quoteStatus',
                'paymentStatus',
                'customer',
                'healthCoverFor',
                'maritalStatus',
                'emirate',
                'nationality'
            ])->where('id', $quoteRequestId)->firstOrFail();

        } elseif ($quoteTypeId == QuoteTypes::LIFE->id()) {
            $quoteRequestDetails = LifeQuote::with([
                'quoteStatus',
                'paymentStatus',
                'customer',
                'purposeOfInsurance',
                'children',
                'maritalStatus',
                'insuranceTenure',
                'numberOfYears',
                'currency',
                'nationality'
            ])->where('id', $quoteRequestId)->firstOrFail();

        } elseif ($quoteTypeId == QuoteTypes::BUSINESS->id()) {
            $quoteRequestDetails = BusinessQuote::with([
                'quoteStatus',
                'paymentStatus',
                'customer',
                'businessTypeOfInsurance',
            ])->where('id', $quoteRequestId)->firstOrFail();

        } elseif ($quoteTypeId == QuoteTypes::TRAVEL->id()) {
            $quoteRequestDetails = TravelQuote::with([
                'quoteStatus',
                'paymentStatus',
                'customer',
                'regionCoverFor',
                'travelCoverFor',
                'nationality'
            ])->where('id', $quoteRequestId)->firstOrFail();

        } elseif ($quoteTypeId == QuoteTypes::PET->id()) {
            if($isDataMigrated) {
                $quoteRequestDetails = PersonalQuote::byQuoteTypeId(QuoteTypes::PET->id())->with([
                    'petQuote',
                    'customer',
                    'quoteStatus',
                    'paymentStatus'
                ])->where('id', $quoteRequestId)->firstOrFail();
            } else {
                $quoteRequestDetails = PetQuote::with([
                    'quoteStatus',
                    'paymentStatus',
                    'customer'
                ])->where('id', $quoteRequestId)->firstOrFail();
            }
        }

        return $quoteRequestDetails;
    }

    public static function sendAMLErrorEmailtoEngTeam($amlQuoteUrl, $apiResponseMessage, $amlDataForEmail, $getStatusCode)
    {
        $emailSystem = Config::get('constants.emailL_sys');
        $errorEmailRecipients = explode(',', Config::get('constants.ERROR_EMAIL_RECIPIENTS'));

        $subject = $emailSystem.' BRIDGER SEARCH API ERROR | '.\Request::url().' | '.date(Config::get('constants.DB_DATE_FORMAT_MATCH'));
        MailService::sendEmail('AmlErrorMail', [
            'amlUrl' => $amlQuoteUrl,
            'emailAmlData' => $amlDataForEmail,
            'chAmlStatus' => $getStatusCode,
            'requestMessage' => $apiResponseMessage,
        ], $subject, $errorEmailRecipients);
    }

    public static function sendAMLMatchedEmailtoComplianceTeam($amlQuoteUrl, $quoteRefId, $getDecodeContents, $customerOrEntityName, $quoteType)
    {
        $emailRecipients = [];
        $emailSystem = Config::get('constants.emailL_sys');
        $recipients = User::select('users.email as user_email')
            ->leftjoin('model_has_roles', 'users.id', 'model_has_roles.model_id')
            ->leftjoin('roles', 'model_has_roles.role_id', 'roles.id')
            ->whereIn('roles.name', [RolesEnum::COMPLIANCE])->get();

        foreach ($recipients as $recipient) {
            $emailRecipients[] = $recipient->user_email;
        }

        if (strtolower($emailSystem) == EnvEnum::PRODUCTION) {
            $fromEmail = Config::get('constants.MAIL_FROM_ADDRESS_AML');
            $fromName = Config::get('constants.MAIL_FROM_NAME_AML');
            $emailSubject = 'IMCRM | New AML Matches Found for Ref-ID : '.$quoteRefId;
        } else {
            $fromEmail = Config::get('constants.MAIL_FROM_ADDRESS');
            $fromName = Config::get('constants.MAIL_FROM_NAME');
            $emailSubject = $emailSystem .' | IMCRM | New AML Matches Found for Ref-ID : '.$quoteRefId;
        }
        Mail::send(
            ['html' => 'AmlComplianceMail'],
            [
                'amlUrl' => $amlQuoteUrl,
                'resultsFound' => $getDecodeContents,
                'fullName' => $customerOrEntityName,
                'quoteTypeName' => $quoteType,
                'quoteCdbId' => $quoteRefId,
            ],
            function ($message) use ($emailSubject, $emailRecipients, $fromName, $fromEmail) {
                $message->to($emailRecipients)->cc(auth()->user()->email)->subject($emailSubject);
                $message->from($fromEmail, $fromName);
            }
        );
    }

    public static function getMemberOrUBODetails($request, $quoteType, $quoteRequestId)
    {
        $customerChildDetails = [];
        $customerType = ($request->customer_type == CustomerTypeEnum::Entity) ? CustomerTypeEnum::EntityShort : CustomerTypeEnum::IndividualShort;

        if($customerType == CustomerTypeEnum::IndividualShort) {

            if ($quoteType->code == QuoteTypes::HEALTH->value) {
                $customerChildDetails = HealthMemberDetail::with('nationality')->where([
                    'health_quote_request_id' => $quoteRequestId
                ])->get();

            } elseif ($quoteType->code == QuoteTypes::TRAVEL->value) {
                $customerChildDetails = TravelMemberDetail::with('nationality')->where([
                    'travel_quote_request_id' => $quoteRequestId
                ])->get();
            }
            else {
                $customerChildDetails = QuoteMemberDetailsRepository::with('nationality')->where([
                    'customer_type' => CustomerTypeEnum::Individual,
                    'quote_type_id' => $quoteType->id,
                    'quote_request_id' => $quoteRequestId,
                ])->get();
            }
        }

        if ($customerType == CustomerTypeEnum::EntityShort) {
            $customerChildDetails = QuoteMemberDetailsRepository::with('nationality')->where([
                'customer_type' => CustomerTypeEnum::Entity,
                'quote_type_id' => $quoteType->id,
                'quote_request_id' => $quoteRequestId,
            ])->get();
        }

        return $customerChildDetails;

    }
}
