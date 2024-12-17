<?php

namespace App\Imports;

use App\Enums\quoteTypeCode;
use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Enums\TravelQuoteEnum;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsUploadLeads;
use App\Services\RenewalsUploadService;
use App\Traits\RenewalsImportTrait;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Row;

class TravelUploadAndCreateImport implements OnEachRow, SkipsOnFailure, WithChunkReading, WithEvents, WithStartRow, WithValidation
{
    use Importable, RegistersEventListeners, RenewalsImportTrait, SkipsFailures;

    private $validCount = 0;
    private $failedCount = 0;
    private $totalRows;
    private $fileName;
    private $renewalImportCode;
    private $uploadType;
    private $renewalsUploadLead;

    /**
     * @param  RenewalsUploadService  $renewalsUploadService
     */
    public function __construct(RenewalsUploadLeads $renewalsUploadLead)
    {
        $this->renewalsUploadLead = $renewalsUploadLead;
    }

    public function onRow(Row $row)
    {
        $this->validCount++;
        $row = $row->toArray();

        $quoteData = $this->mapQuoteData($row);

        return RenewalQuoteProcess::create([
            'renewals_upload_lead_id' => $this->renewalsUploadLead->id,
            'quote_type' => strtoupper(quoteTypeCode::TRA),
            'policy_number' => $quoteData['policy_number'],
            'data' => $quoteData,
            'batch' => $quoteData['renewal_batch'],
            'status' => RenewalProcessStatuses::NEW,
            'type' => TravelQuoteEnum::REVIVAL,
        ]);
    }

    public function chunkSize(): int
    {
        return 2000;
    }

    /**
     * start import from row 2, first row have titles
     */
    public function startRow(): int
    {
        return 2;
    }

    public function getValidCount(): int
    {
        return $this->validCount;
    }

    public function getFailedCount(): int
    {
        return $this->failedCount;
    }

    /**
     * create columns schema, with index, title and rules to be validated for each column.
     *
     * @return array[]
     */
    public function getColumns()
    {
        return [
            'code' => ['index' => 0, 'title' => 'REF-ID', 'rules' => 'required|max:20'],
            'first_name' => ['index' => 1, 'title' => 'FIRST NAME', 'rules' => 'required|max:100'],
            'last_name' => ['index' => 2, 'title' => 'LAST NAME', 'rules' => 'required|max:100'],
            'advisor' => ['index' => 4, 'title' => 'ADVISOR', 'rules' => 'required|max:100'],
            'source' => ['index' => 5, 'title' => 'SOURCE', 'rules' => 'max:8'],
            'renewal_batch' => ['index' => 6, 'title' => 'Batch Number', 'rules' => 'required|max:20'],
            'premium' => ['index' => 16, 'title' => 'PREMIUM', 'rules' => 'max:100'],
            'policy_number' => ['index' => 17, 'title' => 'POLICY NUMBER', 'rules' => 'max:100'],
            'destination' => ['index' => 18, 'title' => 'DESTINATION', 'rules' => 'max:100'],
            'currently_located_in' => ['index' => 19, 'title' => 'CURRENTLY LOCATED IN', 'rules' => 'max:100'],
            'policy_expiry_date' => ['index' => 20, 'title' => 'EXPIRY DATE', 'rules' => 'max:100'],
            'is_ecommerce' => ['index' => 21, 'title' => 'IS ECOMMERCE', 'rules' => 'max:3'],
            'payment_status' => ['index' => 22, 'title' => 'PAYMENT STATUS', 'rules' => 'max:20'],
            'customer_mobile' => ['index' => 23, 'title' => 'MOBILE NUMBER', 'rules' => 'max:20'],
            'customer_email' => ['index' => 24, 'title' => 'EMAIL', 'rules' => 'max:100'],
        ];
    }

    /**
     * validation rules for every column in a row.
     *
     * @return string[]
     */
    public function rules(): array
    {
        return $this->getRules();
    }

    /**
     * get all validation errors and store records in db along with errors.
     *
     * @return \Closure[]
     */
    public function registerEvents(): array
    {
        return [

            AfterImport::class => function (AfterImport $event) {
                $failed = [];

                foreach ($this->failures() as $failure) {
                    if (! isset($failed[$failure->row()])) {
                        $quoteData = $this->mapData($failure->values());
                        $failed[$failure->row()] = [
                            'renewals_upload_lead_id' => $this->renewalsUploadLead->id,
                            'quote_type' => strtoupper(quoteTypeCode::TRA),
                            'policy_number' => $quoteData['policy_number'],
                            'data' => $quoteData,
                            'batch' => $quoteData['renewal_batch'],
                            'status' => RenewalProcessStatuses::VALIDATION_FAILED,
                            'type' => RenewalsUploadType::CREATE_LEADS,
                        ];

                        $this->failedCount++;
                    }
                    foreach ($failure->errors() as $error) {
                        $failed[$failure->row()]['validation_errors'][] = $error;
                    }
                }

                foreach ($failed as $failedRecord) {
                    RenewalQuoteProcess::create($failedRecord);
                }
            },
        ];
    }
}
