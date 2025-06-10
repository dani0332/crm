<?php

declare(strict_types=1);

namespace App\Traits;

use App\Contracts\CsvExportableInterface;
use App\Jobs\CsvExportEmailJob;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        // Dispatch the new cleaner job
        CsvExportEmailJob::dispatch(
            $this,
            $requestParams['recipientEmail'],
            $requestParams['subject'],
            $requestParams,
            $requestParams['ccRecipients'] ?? []
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
        // Set recipient email if not provided
        if (empty($requestParams['recipientEmail'])) {
            if (! auth()->check()) {
                throw new \UnauthorizedHttpException('', 'User not authenticated');
            }

            $currentUser = User::find(auth()->user()->id);
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
}
