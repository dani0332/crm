<?php

declare(strict_types=1);

namespace App\Traits;

use App\Contracts\CsvExportableInterface;
use App\Jobs\ExportCsvAndSendEmailJob;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

trait ModernCsvExportable
{
    /**
     * Download CSV file directly
     */
    public function download(string $fileName): StreamedResponse
    {
        $fileName = $fileName.'-'.Carbon::now()->format('Y-m-d');

        return new StreamedResponse(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $this->headings());

            $data = $this->collection([]);
            foreach ($data as $record) {
                fputcsv($handle, $this->map($record));
            }

            if (method_exists($this, 'postDataRows')) {
                $this->postDataRows($handle);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'.csv"',
        ]);
    }

    /**
     * Queue a job to send the CSV as an email attachment
     */
    public function emailCSV(string $fileName, array $requestParams = []): JsonResponse
    {
        // Ensure this class implements the required interface
        if (! $this instanceof CsvExportableInterface) {
            throw new \InvalidArgumentException(
                'Class must implement CsvExportableInterface to use emailCSV functionality'
            );
        }

        $fileName = $fileName.'-'.Carbon::now()->format('Y-m-d');
        $requestParams = $this->processEmailParameters($fileName, $requestParams);

        // Use ExportCsvAndSendEmailJob instead to avoid serialization issues with dependencies
        ExportCsvAndSendEmailJob::dispatch(
            static::class, // Pass class name instead of instance
            $requestParams['recipientEmail'],
            $requestParams
        );

        return response()->json([
            'message' => 'Your export is being processed. You will receive an email with the CSV file shortly.',
        ]);
    }

    /**
     * Process and validate email parameters
     */
    private function processEmailParameters(string $fileName, array $requestParams): array
    {
        // Ensure we have a user for the job context (query builders need this)
        $currentUser = null;
        if (! isset($requestParams['user_id']) && Auth::check()) {
            $currentUser = Auth::user();
            $requestParams['user_id'] = $currentUser->id;
        }

        // Set recipient email if not provided
        if (empty($requestParams['recipientEmail'])) {
            if (! Auth::check()) {
                throw new UnauthorizedHttpException('', 'User not authenticated');
            }

            $currentUser = $currentUser ?? Auth::user();
            $requestParams['recipientEmail'] = $currentUser->email;
            $requestParams['recipientName'] = $currentUser->name;
        }

        // Set filename and export title
        $requestParams['fileName'] = $fileName;

        if (empty($requestParams['exportTitle'])) {
            $requestParams['exportTitle'] = ucfirst($requestParams['quoteType'] ?? 'Data');
        } else {
            $requestParams['exportTitle'] = ucfirst($requestParams['exportTitle']);
        }

        // Set subject if not provided
        if (empty($requestParams['subject'])) {
            $currentDate = Carbon::now()->format('d-m-Y');
            $requestParams['subject'] = "{$requestParams['exportTitle']} Export - {$currentDate}";
        }

        return $requestParams;
    }

    /**
     * Default implementation for getQuery - can be overridden by implementing classes
     */
    public function getQuery(array $requestParams = []): ?Builder
    {
        // Default implementation returns null, falling back to collection() method
        return null;
    }

    /**
     * Default implementation for getExportMetadata
     */
    public function getExportMetadata(array $requestParams = []): array
    {
        return [
            'exportClass' => static::class,
            'timestamp' => Carbon::now()->toISOString(),
            'parameters' => $requestParams,
        ];
    }

    /**
     * Compatibility method for ExportCsvAndSendEmailJob
     * Delegates to the new CsvExportService for actual implementation
     */
    public function sendEmailWithCSVAttachment($recipientEmail, $emailSubject, $requestParams, $ccRecipients = [], $fileName = 'export')
    {
        // Ensure this class implements the required interface
        if (! $this instanceof CsvExportableInterface) {
            throw new \InvalidArgumentException(
                'Class must implement CsvExportableInterface to use sendEmailWithCSVAttachment functionality'
            );
        }

        // Use the modern email export service
        $emailExportService = app(\App\Services\EmailExportService::class);

        $emailExportService->sendCsvByEmail(
            $this,
            $recipientEmail,
            $emailSubject,
            $requestParams,
            $ccRecipients
        );
    }

    public function resolveNumberFormat($value)
    {
        if (is_string($value) && strpos($value, ',') !== false) {
            return floatval(str_replace(',', '', $value));
        }

        if (is_numeric($value)) {
            return floatval($value);
        }

        return $value;
    }
}
