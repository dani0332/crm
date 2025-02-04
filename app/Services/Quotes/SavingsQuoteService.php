<?php

namespace App\Services\Quotes;

use App\Enums\RolesEnum;
use App\Enums\GenderEnum;
use App\Enums\QuoteTypes;
use App\Models\Nationality;
use App\Models\CurrencyType;
use App\Models\MartialStatus;
use App\Models\PersonalQuote;
use App\Enums\SavingsPurposeEnum;
use Illuminate\Support\Facades\Auth;

class SavingsQuoteService extends BaseQuoteService
{
    public function __construct()
    {
        parent::__construct(QuoteTypes::SAVINGS);
    }

    public function getData(bool $paginted = false, bool $forExport = false, bool $getTotalCount = false)
    {
        $query = $this->baseQuery()->with([
            'quoteStatus',
            'currentlyInsuredWith',
            'advisor',
            'paymentStatus',
            'payments',
            'quoteDetail',
            'renewalBatchModel',
        ])
            ->filter(! $forExport, $getTotalCount)
            ->withFakeLeadCriteria($getTotalCount);

        $this->adjustQueryByInsurerInvoiceFilters($query);
        $this->adjustQueryByDateFilters($query, 'personal_quotes');

        return $query->resolveData($paginted, $forExport, $getTotalCount);
    }

    public function getFormOptions()
    {
        return [
            'nationalities' => Nationality::withActive()->options(),
            'genders' => GenderEnum::withLabels(),
            'maritalStatuses' => MartialStatus::withActive()->options(),
            'purposes' => SavingsPurposeEnum::withLabels(),
            'currencies' => CurrencyType::withActive()->options(),
        ];
    }

    // TODO: Remove this function after the API is ready
    private function tempMockApi($data)
    {
        $getUUID = function(): string
        {
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $length = 8;

            do {
                $uid = '';
                for ($i = 0; $i < $length; $i++) {
                    $uid .= $characters[random_int(0, strlen($characters) - 1)];
                }
            } while (PersonalQuote::where('uuid', $uid)->exists());

            return $uid;
        };

        $uuid = $getUUID();

        $personalQuote = PersonalQuote::create([
            'first_name' => $data['firstName'],
            'last_name' => $data['lastName'],
            'email' => $data['email'],
            'mobile_no' => $data['mobileNo'],
            'dob' => $data['dob'],
            'nationality_id' => $data['nationalityId'],
            'gender' => $data['gender'],
            'quote_type_id' => $data['quoteTypeId'],
            'uuid' => $uuid,
            'code' => "{$this->quoteType->shortCode()}-{$uuid}",
        ]);

        dd($personalQuote);
    }

    public function create(array $data)
    {
        $sourceName = config('constants.SOURCE_NAME');
        $appUrl = config('constants.APP_URL');

        $data = [
            'firstName' => $data['first_name'],
            'lastName' => $data['last_name'],
            'email' => $data['email'],
            'mobileNo' => $data['mobile_no'],
            'dob' => $data['dob'],
            'nationalityId' => $data['nationality_id'],
            'gender' => $data['gender'],
            'quoteTypeId' => $this->quoteType->id(),
            "maritalStatusId" => $data['marital_status_id'],
            "tenureOfSavings" => $data['tenure_of_savings'],
            "hasNicotine" => $data['tenure_of_savings'],
            "purpose" => $data['tenure_of_savings'],
            "currency" => $data['tenure_of_savings'],
            "amount" => $data['tenure_of_savings'],
            "investmentFrequency" => $data['tenure_of_savings'],
            "additionalNotes" => $data['tenure_of_savings'],
            'lang' => 'EN',
            'device' => 'DESKTOP',
            'utmSource' => '',
            'utmMedium' => '',
            'utmCampaign' => '',
            'source' => $sourceName,
            'referenceUrl' => $appUrl,
            'advisorId' => (! Auth::user()->hasRole(RolesEnum::Admin)) ? Auth::id() : null,
        ];

        $this->tempMockApi($data);
    }
}
