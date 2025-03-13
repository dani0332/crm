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

class UploadAndUpdateHomeImport implements SkipsOnFailure, ToModel, WithBatchInserts, WithChunkReading, WithEvents, WithStartRow, WithValidation
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
            'quote_type' => QuoteTypeShortCode::HOM,
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
            'customer_name' => ['index' => 0, 'title' => 'Customer Name', 'rules' => 'required|max:100'],
            'customer_email' => ['index' => 1, 'title' => 'Customer Email', 'rules' => 'nullable|max:255'],
            'customer_number' => ['index' => 2, 'title' => 'Customer Number', 'rules' => 'nullable|max:100'],
            'insurance_type' => ['index' => 3, 'title' => 'Insurance Type', 'rules' => 'required|max:10'],
            'current_insurance_provider' => ['index' => 4, 'title' => 'Current Insurance Provider', 'rules' => 'required|max:20'],
            'advisor_email' => ['index' => 5, 'title' => 'Advisor Email', 'rules' => 'required|max:100'],
            'policy_number' => ['index' => 6, 'title' => 'Policy Number', 'rules' => 'required|max:100'],
            'policy_start_date' => ['index' => 7, 'title' => 'Policy Start Date', 'rules' => ['nullable', 'max:10', function ($attribute, $value, $onFailure) {
                if (! $this->validateDate($value)) {
                    $onFailure('Invalid value provided for '.$attribute);
                }
            }], 'type' => 'date'],
            'policy_end_date' => ['index' => 8, 'title' => 'Policy End date', 'rules' => ['nullable', 'max:10', function ($attribute, $value, $onFailure) {
                if (! $this->validateDate($value)) {
                    $onFailure('Invalid value provided for '.$attribute);
                }
            }], 'type' => 'date'],
            'you_are_a' => ['index' => 9, 'title' => 'You are a', 'rules' => 'required|max:150'],
            'i_live_in_a' => ['index' => 10, 'title' => 'I live in a (Type of Property)', 'rules' => 'required|max:25'],
            'occupancy_status_for_owners' => ['index' => 11, 'title' => 'Occupancy Status for Owners', 'rules' => 'nullable|max:150'],
            'location_area' => ['index' => 12, 'title' => 'Location Area', 'rules' => 'required|max:100'],
            'cover_required' => ['index' => 13, 'title' => 'Cover Required', 'rules' => 'required|max:50'],
            'contents' => ['index' => 14, 'title' => 'Contents', 'rules' => 'nullable|max:50'],
            'personal_belongings' => ['index' => 15, 'title' => 'Personal Belongings', 'rules' => 'nullable|max:25'],
            'building' => ['index' => 16, 'title' => 'Building', 'rules' => 'nullable|max:25'],
            'insurance_provider' => ['index' => 17, 'title' => 'Insurance Provider', 'rules' => 'nullable|max:100'],
            'plan_name' => ['index' => 18, 'title' => 'Plan Name', 'rules' => 'nullable|max:100'],
            'claims_history' => ['index' => 19, 'title' => 'Claims History', 'rules' => 'required|max:10'],
            'premium' => ['index' => 20, 'title' => 'Premium', 'rules' => 'nullable|max:20'],
            'insurer_quote_no' => ['index' => 21, 'title' => 'Insurer Quote No', 'rules' => 'nullable|max:20'],
            'previous_advisor_email' => ['index' => 22, 'title' => 'Previous Advisor Email', 'rules' => 'nullable|max:100'],
            'notes' => ['index' => 23, 'title' => 'Notes', 'rules' => 'nullable|max:100'],
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
                            'quote_type' => QuoteTypeShortCode::HOM,
                            'policy_number' => $quoteData['policy_number'],
                            'data' => json_encode(json_encode($quoteData)),
                            'status' => RenewalProcessStatuses::VALIDATION_FAILED,
                            'type' => RenewalsUploadType::UPDATE_LEADS,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        $this->failedCount++;
                    }

                    $validationErrors = [];
                    foreach ($failure->errors() as $error) {
                        $validationErrors[] = $error;
                    }
                    $failed[$failure->row()]['validation_errors'] = json_encode($validationErrors);
                }

                if (! empty($failed)) {
                    RenewalQuoteProcess::insert($failed);
                }
            },
        ];
    }
}
