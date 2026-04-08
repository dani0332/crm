<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Exports\Reports\ConversionOptimizationReportExport;
use App\Models\ApplicationStorage;
use App\Models\QuoteBatches;
use App\Models\User;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ConversionOptimizationScheduledExportService
{


    /**
     * Request keys allowed inside application_storage JSON `filters` (merged over code defaults).
     *
     * @var list<string>
     */
    private const MERGEABLE_FILTER_KEYS = [
        'lob',
        'advisorAssignedDates',
        'tiers',
        'leadSources',
        'advisors',
        'teams',
        'sub_teams',
        'cap_percentage',
        'isCommercial',
        'isEmbeddedProducts',
        'is_ecommerce',
        'vehicle_type',
        'insurance_type',
        'insurance_for',
        'travel_coverage',
        'segment_filter',
        'registration_type',
        'vehicle_use',
        'quote_batch_id',
        'leadType',
        'page',
    ];

    public function __construct(
        private readonly ConversionOptimizationReportService $conversionOptimizationReportService
    ) {}

    /**
     * Build filter payload (defaults + optional JSON `filters`), attach batch ids overlapping JSON `batch` date range,
     * merge email/job fields, and queue the CSV email export.
     *
     * @param  string  $applicationStorageKeyName  {@see application_storage.key_name} holding recipient JSON.
     */
    public function dispatchScheduledExport(string $applicationStorageKeyName): bool
    {
        $applicationStorageKeyName = trim($applicationStorageKeyName);

        if ($applicationStorageKeyName === '') {
            LoggerService::error('Conversion optimization scheduled export: application storage key is empty');

            return false;
        }

        LoggerService::info('Conversion optimization scheduled export: loading recipients from application_storage', [
            'application_storage_key' => $applicationStorageKeyName,
        ]);

        //region Fetch params from application_storage
        $row = ApplicationStorage::query()
            ->where('key_name', $applicationStorageKeyName)
            ->first();

        if ($row === null || $row->value === null || trim((string) $row->value) === '') {
            LoggerService::error('Conversion optimization scheduled export: application_storage row missing or empty', [
                'application_storage_key' => $applicationStorageKeyName,
            ]);

            return false;
        }

        $decoded = json_decode((string) $row->value, true);


        if (! is_array($decoded)) {
            LoggerService::error('Conversion optimization scheduled export: recipients value is not valid JSON', [
                'application_storage_key' => $applicationStorageKeyName,
            ]);

            return false;
        }
        //endregion

        if (! $this->validateConfig($decoded, $applicationStorageKeyName)) {
            return false;
        }

        $toEmailNormalized = strtolower(trim((string) $decoded['to_email']));
        $ccEmails = $this->normalizeCcEmails($decoded['cc_emails'] ?? []);

        $user = User::query()
            ->with(['permissions', 'roles.permissions'])
            ->activeUser()
            ->whereRaw('LOWER(users.email) = ?', [$toEmailNormalized])
            ->first();

        if ($user === null) {
            LoggerService::error('Conversion optimization scheduled export: to_email user missing or inactive', [
                'to_email' => $toEmailNormalized,
                'application_storage_key' => $applicationStorageKeyName,
            ]);

            return false;
        }

        try {
            Auth::login($user); // required in conversionOptimizationReportService->getDefaultFilters

            $defaultFilters = $this->conversionOptimizationReportService->getDefaultFilters();
            $storageFilterOverlay = $this->extractMergeableFiltersFromStorage($decoded);

            $reportFilters = array_merge(
                $this->browserAlignedFilterDefaults(),
                $defaultFilters,
                $storageFilterOverlay
            );

            $batchWindow = $this->normalizeBatchWindowFromDecoded($decoded);
            $batchIds = $this->resolveQuoteBatchIdsOverlappingRange($batchWindow['start'], $batchWindow['end']);

            if ($batchIds === []) {
                LoggerService::error('Conversion optimization scheduled export: no quote_batches overlap batch window', [
                    'batch_window_start' => $batchWindow['start'],
                    'batch_window_end' => $batchWindow['end'],
                    'application_storage_key' => $applicationStorageKeyName,
                ]);

                return false;
            }

            $reportFilters['batches'] = $batchIds;

            LoggerService::info('Conversion optimization scheduled export: report filters summary', [
                'application_storage_key' => $applicationStorageKeyName,
                'batch_id_count' => count($batchIds),
                'batch_window_start' => $batchWindow['start'],
                'batch_window_end' => $batchWindow['end'],
                'filter_keys' => array_keys($reportFilters),
                'feature' => LoggerFeatureEnum::CONVERSION_OPTIMIZATION_SCHEDULED_EXPORT->value,
            ]);

            $requestParams = array_merge($reportFilters, [
                'user_id' => $user->id,
                'recipientEmail' => $toEmailNormalized,
                'ccRecipients' => $ccEmails,
                'exportTitle' => 'Scheduled Conversion Optimization Report',
            ]);

            $export = new ConversionOptimizationReportExport(
                $this->conversionOptimizationReportService,
                $requestParams
            );

            $export->emailCSV('Conversion Optimization Report', $requestParams);

            LoggerService::info('Conversion optimization scheduled export: ExportCsvAndSendEmailJob dispatched', [
                'export_class' => ConversionOptimizationReportExport::class,
                'queue' => 'ocr_dedicated',
                'application_storage_key' => $applicationStorageKeyName,
                'feature' => LoggerFeatureEnum::CONVERSION_OPTIMIZATION_SCHEDULED_EXPORT->value,
            ]);

            return true;
        } catch (\Throwable $e) {
            LoggerService::error('Conversion optimization scheduled export: unexpected failure', [
                'application_storage_key' => $applicationStorageKeyName,
                'user_id' => $user->id,
                'exception' => $e->getMessage(),
                'exception_class' => $e::class,
            ]);

            throw $e;
        } finally {
            if (Auth::check()) {
                Auth::logout();
            }
        }
    }

    /**
     * Batches whose [start_date, end_date] overlap [rangeStart, rangeEnd] (inclusive calendar dates).
     *
     * @return list<string>
     */
    private function resolveQuoteBatchIdsOverlappingRange(string $rangeStart, string $rangeEnd): array
    {
        return QuoteBatches::query()
            ->whereDate('start_date', '<=', $rangeEnd)
            ->whereDate('end_date', '>=', $rangeStart)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $decoded
     * @return array{start: string, end: string}  Normalized Y-m-d (validated in {@see validateConfig}).
     */
    private function normalizeBatchWindowFromDecoded(array $decoded): array
    {
        /** @var array<string, mixed> $batch */
        $batch = $decoded['batch'];
        $start = Carbon::parse(trim((string) $batch['start']))->startOfDay()->toDateString();
        $end = Carbon::parse(trim((string) $batch['end']))->endOfDay()->toDateString();

        return ['start' => $start, 'end' => $end];
    }

    /**
     * @param  array<string, mixed>  $decoded
     * @return array<string, mixed>
     */

    /** Sample application storage JSON for reference
        {
          "to_email": "advisor.user@example.com",
          "cc_emails": [
            "reports-cc@example.com"
          ],
          "batch": {
            "start": "2026-01-01",
            "end": "2026-03-31"
          },
          "filters": {
            "lob": "Car",
            "advisorAssignedDates": [
              "2026-01-01",
              "2026-04-08"
            ],
            "tiers": [
              "1",
              "2"
            ],
            "leadSources": [
              "3"
            ],
            "advisors": [
              "101",
              "102"
            ],
            "teams": [
              "12"
            ],
            "sub_teams": [
              "34",
              "35"
            ],
            "cap_percentage": "20",
            "isCommercial": "All",
            "isEmbeddedProducts": "false",
            "is_ecommerce": "All",
            "vehicle_type": "All",
            "insurance_type": "",
            "insurance_for": "",
            "travel_coverage": "",
            "segment_filter": "all",
            "registration_type": "",
            "vehicle_use": "",
            "quote_batch_id": "",
            "leadType": "new_leads",
            "page": 1
          }
        }
      */

    private function extractMergeableFiltersFromStorage(array $decoded): array
    {
        if (! isset($decoded['filters'])) {
            return [];
        }

        if (! is_array($decoded['filters'])) {
            return [];
        }

        $out = [];
        foreach (self::MERGEABLE_FILTER_KEYS as $key) {
            if (array_key_exists($key, $decoded['filters'])) {
                $out[$key] = $decoded['filters'][$key];
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $decoded
     */
    private function validateConfig(array $decoded, string $applicationStorageKeyName): bool
    {
        if (! isset($decoded['to_email']) || ! is_string($decoded['to_email']) || trim($decoded['to_email']) === '') {
            LoggerService::error('Conversion optimization scheduled export: recipients JSON missing to_email', [
                'application_storage_key' => $applicationStorageKeyName,
            ]);

            return false;
        }

        $toEmail = trim((string) $decoded['to_email']);
        if (filter_var($toEmail, FILTER_VALIDATE_EMAIL) === false) {
            LoggerService::error('Conversion optimization scheduled export: invalid to_email', [
                'application_storage_key' => $applicationStorageKeyName,
            ]);

            return false;
        }

        if (isset($decoded['cc_emails'])) {
            if (! is_array($decoded['cc_emails'])) {
                LoggerService::error('Conversion optimization scheduled export: cc_emails must be an array when present', [
                    'application_storage_key' => $applicationStorageKeyName,
                ]);

                return false;
            }

            foreach ($decoded['cc_emails'] as $cc) {
                if (! is_string($cc) || trim($cc) === '' || filter_var(trim($cc), FILTER_VALIDATE_EMAIL) === false) {
                    LoggerService::error('Conversion optimization scheduled export: invalid cc_emails entry', [
                        'application_storage_key' => $applicationStorageKeyName,
                    ]);

                    return false;
                }
            }
        }

        if (isset($decoded['filters']) && ! is_array($decoded['filters'])) {
            LoggerService::error('Conversion optimization scheduled export: filters must be an object/array when present', [
                'application_storage_key' => $applicationStorageKeyName,
            ]);

            return false;
        }

        if (! isset($decoded['batch']) || ! is_array($decoded['batch'])) {
            LoggerService::error('Conversion optimization scheduled export: batch is required (object with start and end dates)', [
                'application_storage_key' => $applicationStorageKeyName,
            ]);

            return false;
        }

        /** @var array<string, mixed> $batch */
        $batch = $decoded['batch'];
        foreach (['start', 'end'] as $batchKey) {
            if (! isset($batch[$batchKey]) || ! is_string($batch[$batchKey]) || trim($batch[$batchKey]) === '') {
                LoggerService::error('Conversion optimization scheduled export: batch missing date key', [
                    'batch_key' => $batchKey,
                    'application_storage_key' => $applicationStorageKeyName,
                ]);

                return false;
            }
        }

        try {
            $startAt = Carbon::parse(trim((string) $batch['start']))->startOfDay();
            $endAt = Carbon::parse(trim((string) $batch['end']))->startOfDay();
        } catch (\Throwable) {
            LoggerService::error('Conversion optimization scheduled export: batch start/end are not valid dates', [
                'application_storage_key' => $applicationStorageKeyName,
            ]);

            return false;
        }

        if ($endAt->lt($startAt)) {
            LoggerService::error('Conversion optimization scheduled export: batch end must be on or after batch start', [
                'application_storage_key' => $applicationStorageKeyName,
            ]);

            return false;
        }


        LoggerService::info('Conversion optimization scheduled export: recipients JSON validated', [
            'application_storage_key' => $applicationStorageKeyName,
        ]);

        return true;
    }

    /**
     * @return list<string>
     */
    private function normalizeCcEmails(mixed $ccEmails): array
    {
        if ($ccEmails === null || $ccEmails === []) {
            return [];
        }

        if (! is_array($ccEmails)) {
            return [];
        }

        $out = [];
        foreach ($ccEmails as $entry) {
            if (! is_string($entry) || trim($entry) === '') {
                continue;
            }
            $out[] = strtolower(trim($entry));
        }

        return array_values(array_unique($out));
    }

    /**
     * Keys aligned with the Conversion Optimization Inertia page filter payload so advisor report normalization
     * matches the manual export.
     *
     * @return array<string, mixed>
     */
    private function browserAlignedFilterDefaults(): array
    {
        return [
            'batches' => [],
            'tiers' => [],
            'leadSources' => [],
            'advisors' => [],
            'is_ecommerce' => '',
            'page' => 1,
            'vehicle_type' => 'All',
            'insurance_type' => '',
            'insurance_for' => '',
            'travel_coverage' => '',
            'segment_filter' => 'all',
            'registration_type' => '',
            'vehicle_use' => '',
        ];
    }
}
