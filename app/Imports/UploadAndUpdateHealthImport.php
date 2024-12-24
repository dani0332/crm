<?php

namespace App\Imports;

use App\Enums\FetchPlansStatuses;
use App\Enums\QuoteTypeShortCode;
use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsUploadLeads;
use App\Traits\RenewalsImportTrait;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Events\AfterImport;

class UploadAndUpdateHealthImport implements SkipsOnFailure, ToModel, WithBatchInserts, WithChunkReading, WithEvents, WithStartRow, WithValidation
{
    use Importable, RegistersEventListeners, RenewalsImportTrait, SkipsFailures;

    private $validCount = 0;
    private $failedCount = 0;
    private $renewalsUploadLead;

    public function __construct(RenewalsUploadLeads $renewalsUploadLead)
    {
        $this->renewalsUploadLead = $renewalsUploadLead;
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
     * @return RenewalQuoteProcess
     */
    public function model(array $row)
    {
        $this->validCount++;

        $quoteData = $this->mapQuoteData($row);

        return new RenewalQuoteProcess([
            'renewals_upload_lead_id' => $this->renewalsUploadLead->id,
            'quote_type' => QuoteTypeShortCode::HEA,
            'policy_number' => $quoteData['policy_number'],
            'data' => $quoteData,
            'status' => RenewalProcessStatuses::NEW,
            'fetch_plans_status' => FetchPlansStatuses::PENDING,
            'type' => RenewalsUploadType::UPDATE_LEADS,
        ]);
    }

    public function batchSize(): int
    {
        return 500;
    }

    /**
     * start import from row 2, first row have titles
     */
    public function startRow(): int
    {
        return 2;
    }

    public function chunkSize(): int
    {
        return 2000;
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
        $columns = [
            'customer_name' => ['index' => 0, 'title' => 'Customer Name', 'rules' => 'nullable|max:100'],
            'email' => ['index' => 1, 'title' => 'Customer Email', 'rules' => 'nullable|max:255'],
            'mobile_no' => ['index' => 2, 'title' => 'Customer Mobile', 'rules' => 'nullable|max:100'],
            'plan_code' => ['index' => 3, 'title' => 'Insurer Plan Code', 'rules' => 'required|max:50'],
            'policy_number' => ['index' => 4, 'title' => 'Previous Policy Number', 'rules' => 'required|max:50'],
            'end_date' => ['index' => 5, 'title' => 'Previous Policy Expiry Date', 'rules' => ['required', 'max:10', function ($attribute, $value, $onFailure) {
                if (! $this->validateDate($value)) {
                    $onFailure('Invalid value provided for '.$attribute);
                }
            }], 'type' => 'date'],
            'advisor' => ['index' => 6, 'title' => 'Advisor Email', 'rules' => 'nullable|max:100'],
            'member_premium' => ['index' => 7, 'title' => 'Renewal Premium', 'rules' => 'nullable|max:150'],
            'copay' => ['index' => 8, 'title' => 'Renewal Co-Pay', 'rules' => 'required|max:300'],
            'member_names' => ['index' => 9, 'title' => 'Member Names', 'rules' => 'nullable|max:500'],
            'member_dob' => ['index' => 10, 'title' => "Customer's DOB", 'rules' => ['required', 'max:100', function ($attribute, $value, $onFailure) {
                $dobs = explode('|', $value);
                foreach ($dobs as $dob) {
                    if (! $this->validateDate($dob)) {
                        $onFailure('Invalid value provided for '.$attribute);
                        break;
                    }   
                }
            }]],
            'member_nationality' => ['index' => 11, 'title' => "Customer's Nationality", 'rules' => ['required', 'max:300']],
            'member_gender' => ['index' => 12, 'title' => 'Gender', 'rules' => 'required|max:50'],
            'member_category' => ['index' => 13, 'title' => 'Member Category', 'rules' => 'required|max:300'],
            'member_emirate_of_visa' => ['index' => 14, 'title' => 'Emirate of Visa', 'rules' => 'required|max:100'],
            'payment_link' => ['index' => 15, 'title' => 'Payment Link', 'rules' => 'nullable|max:400'],
            'previous_policy_premium' => ['index' => 16, 'title' => 'Previous Policy Premium', 'rules' => 'required|max:15'],
            'notes' => ['index' => 17, 'title' => 'Notes', 'rules' => 'max:500'],
        ];

        return $columns;
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
                            'quote_type' => QuoteTypeShortCode::HEA,
                            'policy_number' => $quoteData['policy_number'],
                            'data' => $quoteData,
                            'status' => RenewalProcessStatuses::VALIDATION_FAILED,
                            'type' => RenewalsUploadType::UPDATE_LEADS,
                        ];

                        $this->failedCount++;
                    }

                    foreach ($failure->errors() as $error) {
                        $failed[$failure->row()]['validation_errors'][] = $error;
                    }
                }

                if (!empty($failed)) {
                    RenewalQuoteProcess::insert($failed);
                }
            },
        ];
    }
}
