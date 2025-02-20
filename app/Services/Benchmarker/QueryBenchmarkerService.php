<?php

namespace App\Services\Benchmarker;

use App\Enums\ApplicationStorageEnums;
use Exception;
use Illuminate\Support\Benchmark;
use Illuminate\Support\Facades\DB;

class QueryBenchmarkerService
{
    public function validateQuery(string $query): void
    {
        $query = trim($query);

        if (! preg_match('/^\s*select\b[^;]*$/i', $query)) {
            throw new Exception('Only single SELECT queries are allowed.');
        }

        $forbiddenPatterns = [
            '/\b(insert|update|delete|drop|alter|truncate|create|replace|execute|merge|call|grant|revoke|commit|rollback|savepoint|set|lock|unlock)\b/i', // DML & DDL operations
            '/\b(union\s+all|union\b)/i',
            '/\b(sleep|benchmark|load_file|into outfile|into dumpfile)\b/i',
            '/(--|#|\/\*.*?\*\/)/s',
        ];

        foreach ($forbiddenPatterns as $pattern) {
            if (preg_match($pattern, $query)) {
                throw new Exception('Forbidden keyword found in query.');
            }
        }
    }

    public function runQuery(string $query): array
    {
        $this->validateQuery($query);

        return DB::select($query);
    }

    public function benchmark(string $query, int $iterations = 1): array
    {
        abort_if(getAppStorageValueByKey(ApplicationStorageEnums::BENCHMARKING_ENABLED, 0) == 0, 403, 'Benchmarking is disabled.');

        try {
            $this->validateQuery($query);

            $time = Benchmark::measure(fn () => $this->runQuery($query), $iterations);

            return [
                'iterations' => $iterations,
                'execution_time_ms' => number_format($time, 2).' ms',
            ];
        } catch (Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }
}
