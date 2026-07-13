<?php

namespace App\Imports;

use App\Enums\FetchPlansStatuses;
use App\Enums\LeadSourceEnum;
use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsUploadLeads;
use App\Models\User;
use App\Services\OtherNonMotorRenewalsUploadService;
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

class UploadAndUpdateOtherNonMotorImport implements SkipsOnFailure, ToModel, WithBatchInserts, WithChunkReading, WithEvents, WithStartRow, WithValidation
{
    use Importable, RegistersEventListeners, RenewalsImportTrait, SkipsFailures;

    private int $validCount = 0;
    private int $failedCount = 0;
    private array $seenRefIds = [];
    private array $resolvedPolicyNumbers = [];

    public function __construct(
        private OtherNonMotorRenewalsUploadService $otherNonMotorRenewalsUploadService,
        private RenewalsUploadLeads $renewalsUploadLead,
    ) {}

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
        $refId = strtoupper(trim((string) ($quoteData['ref_id'] ?? '')));

        return new RenewalQuoteProcess([
            'renewals_upload_lead_id' => $this->renewalsUploadLead->id,
            'quote_type' => OtherNonMotorRenewalsUploadService::QUOTE_TYPE,
            'policy_number' => $this->resolvedPolicyNumbers[$refId] ?? null,
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
    public function getColumns(): array
    {
        return [
            'ref_id' => [
                'index' => 0,
                'title' => 'Ref-ID',
                'rules' => [
                    'required',
                    'max:100',
                    function ($_, $value, $fail) {
                        $refId = strtoupper(trim((string) $value));

                        // Prevent duplicate Ref-IDs within the same file to avoid race conditions later
                        if (isset($this->seenRefIds[$refId])) {
                            $fail('Duplicate Ref-ID found in file : '.$refId);

                            return;
                        }
                        $this->seenRefIds[$refId] = true;

                        $quote = $this->otherNonMotorRenewalsUploadService->findEligibleQuote($refId);

                        if (! $quote) {
                            $fail('No eligible renewal lead found for provided Ref-ID : '.$refId);

                            return;
                        }

                        $this->resolvedPolicyNumbers[$refId] = $quote->previous_quote_policy_number;

                        if ($quote->source !== LeadSourceEnum::RENEWAL_UPLOAD) {
                            $fail('Lead source must be renewal_upload : '.$refId);
                        }

                        if ($this->otherNonMotorRenewalsUploadService->isManuallyAssigned($quote)) {
                            $fail('Lead is manually assigned and was not updated : '.$refId);
                        }
                    },
                ],
            ],
            'advisor_email' => [
                'index' => 1,
                'title' => 'Advisor Email',
                'rules' => [
                    'required',
                    'email:rfc,dns',
                    'max:100',
                    function ($attribute, $value, $fail) {
                        $advisor = User::where('email', strtolower(trim((string) $value)))->first();
                        if (! $advisor) {
                            $fail($attribute.': Advisor Email must belong to an active IMCRM user: '.$value);
                        }
                    },
                ],
            ],
        ];
    }

    /**
     * get all validation errors and store records in db along with errors.
     *
     * @return \Closure[]
     */
    public function registerEvents(): array
    {
        return [
            AfterImport::class => function () {
                $failed = [];
                foreach ($this->failures() as $failure) {
                    if (! isset($failed[$failure->row()])) {
                        $quoteData = $this->mapData($failure->values());
                        $refId = strtoupper(trim((string) ($quoteData['ref_id'] ?? '')));
                        $failed[$failure->row()] = [
                            'renewals_upload_lead_id' => $this->renewalsUploadLead->id,
                            'quote_type' => OtherNonMotorRenewalsUploadService::QUOTE_TYPE,
                            'policy_number' => $this->resolvedPolicyNumbers[$refId] ?? null,
                            'data' => $quoteData,
                            'status' => RenewalProcessStatuses::VALIDATION_FAILED,
                            'type' => RenewalsUploadType::UPDATE_LEADS,
                        ];

                        $this->failedCount++;
                    }

                    $validationErrors = [];
                    foreach ($failure->errors() as $error) {
                        $validationErrors[] = $error;
                    }
                    $failed[$failure->row()]['validation_errors'] = $validationErrors;
                }

                if (! empty($failed)) {
                    RenewalQuoteProcess::insert(array_map(fn ($r) => RenewalQuoteProcess::prepareForBulkInsert($r), $failed));
                }
            },
        ];
    }

}
