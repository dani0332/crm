<?php

namespace App\Repositories;

use App\Enums\QuoteTypes;
use App\Facades\Capi;
use App\Models\PersonalQuote;
use App\Models\QuoteDocument;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class BikeQuoteRepository extends BaseRepository
{
    public function model() {
        return PersonalQuote::class;
    }

    /**
     * create new personal quote
     * @param $quoteTypeCode
     * @param $data
     * @return mixed
     */
    public function fetchCreate($data)
    {
        $quoteData = [
            "quoteTypeId" => intval(QuoteTypes::BIKE->id()),
            "nationalityId" => $data['nationality_id'],
            "mobileNo"  => $data['mobile_no'],
            "email" => $data['email'],
            "firstName" => $data['first_name'],
            "lastName"  => $data['last_name'],
            "dob"   => $data['dob'],
            "bikeCompanyToInsure"   => $data['bike_company_to_insure'],
            "assetValue"    => $data['asset_value'],
            "currentlyInsuredWithId"    => $data['currently_insured_with_id'],
            "uaeLicenseHeldForId"   => $data['uae_license_held_for_id'],
            "yearOfManufacture" => $data['year_of_manufacture'],
            "lang"  => "EN",
            "device"    => "DESKTOP",
            "source"    => config('constants.SOURCE_NAME'),
            "referenceUrl"  => URL::current(),
        ];

        return Capi::request('/api/v1-save-personal-quote', 'post', $quoteData);
    }

    /**
     * @param $uuid
     * @param $data
     * @return mixed
     */
    public function fetchUpdate($uuid, $data)
    {
        return DB::transaction(function() use($uuid, $data)
        {
            $quote = $this->byQuoteTypeId(QuoteTypes::BIKE->id())->where('uuid', $uuid)->firstOrFail();

            $quote->update(Arr::only($data, [
                'first_name', 'last_name', 'email', 'mobile_no', 'dob', 'nationality_id',  'asset_value', 'currently_insured_with_id'
            ]));

            $quote->bikeQuote->update( Arr::only($data,['bike_company_to_insure', 'year_of_manufacture', 'uae_license_held_for_id']));

            return $quote;
        });
    }


    /**
     * get all dropdown options required for form
     * @return array
     */
    public function fetchGetFormOptions()
    {
        return [
            'nationalities' => NationalityRepository::withActive()->get(),
            'uaeLicenses' => UaeLicenseHeldRepository::withActive()->get(),
            'yearOfManufacture' => YearOfManufactureRepository::get(),
            'insuranceProviders' => InsuranceProviderRepository::select('id', 'text')->orderBy('text', 'asc')->get()
        ];
    }

    /**
     * @param $column
     * @param $value
     * @return mixed
     */
    public function fetchGetBy($column = 'id', $value) {

        return $this->byQuoteTypeId(QuoteTypes::BIKE->id())
            ->where($column, $value)
            ->with(['bikeQuote', 'advisor', 'nationality', 'quoteDetail.lostReason'])->firstOrFail();
    }

    /**
     * @return mixed
     */
    public function fetchGetData()
    {
        return $this->byQuoteTypeCode(QuoteTypes::BIKE)->filter()->simplePaginate();
    }

    public function fetchUploadDocument($file, $data)
    {
        $documentType = DocumentTypeRepository::where('code', $data['document_type_code'])->first();
        $quote = $this->whereId($data['quote_id'])->first();

        $originalName = $file->getClientOriginalName();
        $docName = preg_replace('/\s+/', '', uniqid().'_'.$originalName);
        $fileMimeType = $file->getClientMimeType();

        //upload file to azure
        $fileNameAzure = uniqid().'_'.$data['quote_uuid'].'_'.$docName;
        $filePathAzure = $file->storeAs('documents/'.$documentType->folder_path, $fileNameAzure, 'azureIM');

        //generate unique uuid
        $docUuid = uniqid();
        while (QuoteDocument::where('doc_uuid', $docUuid)->first()) {
            $docUuid = uniqid().rand(1, 100);
        }

        return $quote->documents()->create([
            'doc_name' => $docName,
            'original_name' => $originalName,
            'doc_url' => $filePathAzure,
            'doc_mime_type' => $fileMimeType,
            'document_type_code' => $documentType->code,
            'document_type_text' => $documentType->text,
            'doc_uuid' => $docUuid,
            'member_detail_id' => $data['member_detail_id'] ?? null,
            'created_by_id' => auth()->id(),
        ]);
    }

}
