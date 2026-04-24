<?php

namespace App\Services\UaePass;

use App\Enums\GenericRequestEnum;
use App\Enums\UaePassAPILogLabel;
use App\Enums\UaePassLogStatusEnum;
use App\Models\UaePassLog;
use App\Models\UaeSigningPassLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

final class UaeSigningPassLogsPresenter
{
    /**
     * @return list<string>
     */
    public static function mysqlApiNames(): array
    {
        return [UaePassAPILogLabel::USER_INFO_API->value];
    }

    /**
     * @return list<string>
     */
    public static function mongoApiNames(): array
    {
        return [];
    }

    public function mergedRows(string $quoteUuid, int $quoteTypeId): Collection
    {
        $mysql = $this->loadMysqlLogs($quoteUuid, $quoteTypeId)->map(fn (UaePassLog $log) => $this->normalizeMysqlRow($log));
        $mongo = $this->loadMongoLogs($quoteUuid, $quoteTypeId)->map(fn (UaeSigningPassLog $log) => $this->normalizeMongoRow($log));

        return $mysql
            ->concat($mongo)
            ->sortByDesc(fn (array $row) => $row['_sort_ts'])
            ->values()
            ->map(fn (array $row) => collect($row)->except('_sort_ts')->all());
    }

    private function loadMysqlLogs(string $quoteUuid, int $quoteTypeId): Collection
    {
        $q = UaePassLog::query()
            ->where('quote_uuid', $quoteUuid)
            ->where('quote_type_id', $quoteTypeId)
            ->orderByDesc('created_at');

        $apiNames = self::mysqlApiNames();
        if ($apiNames !== []) {
            $q->whereIn('api_name', $apiNames);
        }

        return $q->get();
    }

    private function loadMongoLogs(string $quoteUuid, int $quoteTypeId): Collection
    {
        $q = UaeSigningPassLog::query();

        self::applyMongoQuoteScope($q, $quoteUuid, $quoteTypeId);

        $apiNames = self::mongoApiNames();
        if (count($apiNames) > 0) {
            $q->whereIn('functionName', $apiNames);
        }

        return $q->get();
    }

    private static function applyMongoQuoteScope(Builder $query, string $quoteUuid, int $quoteTypeId): void
    {
        $query->where('refId', $quoteUuid)
            ->where('quoteTypeId', $quoteTypeId);
    }

    private function normalizeMysqlRow(UaePassLog $log): array
    {
        return $this->buildNormalizedRow([
            'id' => $log->id,
            'api_name' => $log->api_name,
            'status' => $log->status,
            'created_at' => $log->created_at,
            'updated_at' => $log->updated_at,
            'response_status' => $log->response_status,
            'proof_of_presentation_id' => $log->proof_of_presentation_id,
            'request_payload' => $log->request_payload,
            'response_payload' => $log->response_payload,
        ]);
    }

    private function normalizeMongoRow(UaeSigningPassLog $log): array
    {
        return $this->buildNormalizedRow([
            'id' => $log->_id,
            'api_name' => $log->functionName,
            'status' => ucfirst($log->status),
            'created_at' => $log->createdAt,
            'updated_at' => $log->updatedAt,
            'response_status' => $log->status == GenericRequestEnum::PASSED ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST,
            'proof_of_presentation_id' => $log->proofOfPresentationId,
            'request_payload' => $log->req,
            'response_payload' => $log->resp,
        ]);
    }

    private function buildNormalizedRow(array $row): array
    {
        $status = $row['status'];
        $tagColor = UaePassLogStatusEnum::resolveTagColor($status);
        $createdAt = $row['created_at'];
        $updatedAt = $row['updated_at'];

        return array_merge([
            'id' => $row['id'],
            'api_name' => $row['api_name'],
            'status' => $status,
            'created_at' => $createdAt,
            'response_status' => $row['response_status'],
            'proof_of_presentation_id' => $row['proof_of_presentation_id'],
            'status_display' => $status ? strtoupper((string) $status) : 'N/A',
            'status_tag_color' => $tagColor,
            'request_payload' => $row['request_payload'],
            'response_payload' => $row['response_payload'],
            'updated_at' => $updatedAt,
            '_sort_ts' => $createdAt,
        ]);
    }
}
