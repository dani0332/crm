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
        $query = trim($query, ';');

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

        if (! preg_match('/^\s*select\b[^;]*$/i', $query)) {
            throw new Exception('Only SELECT queries are allowed.');
        }

        // Ensure query contains a WHERE clause
        if (! preg_match('/\bwhere\b/i', $query)) {
            throw new Exception('Query must contain a WHERE clause for performance reasons.');
        }

        // Check for sensitive data patterns in SELECT clause only, not in WHERE clause
        $sensitiveDataPatterns = [
            '/\b(email|e_mail|e-mail|mail|user_email|customer_email)\b/i',
            '/\b(phone|telephone|mobile|phone_number|mobile_number|contact_number|cell|cellphone)\b/i',
            '/\b(password|passwd|pwd|user_password|hash|secret)\b/i',
            '/\b(ssn|social_security|tax_id|national_id|id_number)\b/i',
            '/\b(credit_card|card_number|cc_number|payment_card)\b/i',
            '/\b(address|street|postal_code|zip_code|zip)\b/i',
        ];

        // Extract SELECT clause from the query
        if (preg_match('/select\s+(.*?)\s+from/is', $query, $matches)) {
            $selectClause = $matches[1];

            // If using SELECT *, we'll handle sensitive data via filterSensitiveData()
            if (trim($selectClause) === '*') {
                return;
            }

            // Check if the SELECT clause explicitly requests sensitive fields
            foreach ($sensitiveDataPatterns as $pattern) {
                if (preg_match($pattern, $selectClause)) {
                    throw new Exception('For security reasons, explicitly selecting sensitive data (emails, phone numbers, addresses, etc.) is not allowed. Use SELECT * instead and sensitive fields will be redacted.');
                }
            }
        }
    }

    public function runQuery(string $query): array
    {
        $this->validateQuery($query);

        // Check if query already has a LIMIT clause
        $hasLimit = preg_match('/\blimit\s+\d+(?:\s*,\s*\d+)?\b/i', $query);

        if ($hasLimit) {
            // Extract the limit value to ensure it's not more than 1000
            preg_match('/\blimit\s+(\d+)(?:\s*,\s*\d+)?\b/i', $query, $matches);
            $limitValue = isset($matches[1]) ? (int) $matches[1] : 0;

            if ($limitValue > 1000) {
                throw new Exception('Maximum allowed LIMIT is 1000 records.');
            }

            return DB::select($query);
        } else {
            // No LIMIT in the query, append LIMIT 1000
            $query = rtrim($query, '; ').' LIMIT 1000';

            return DB::select($query);
        }
    }

    public function benchmark(string $query, int $iterations = 1, bool $fetch_data = true): array
    {
        abort_if(getAppStorageValueByKey(ApplicationStorageEnums::BENCHMARKING_ENABLED, 0) == 0, 403, 'Benchmarking is disabled.');

        try {
            $this->validateQuery($query);

            // Execute query once to get results if fetch_data is true
            $results = [];
            $rowCount = 0;

            if ($fetch_data) {
                $results = $this->runQuery($query);
                $results = $this->filterSensitiveData($results);
                $rowCount = count($results);
            }

            // Benchmark the execution time
            $time = Benchmark::measure(fn () => $this->runQuery($query), $iterations);

            $response = [
                'iterations' => $iterations,
                'execution_time_ms' => number_format($time, 2).' ms',
            ];

            // Only include results data if fetch_data is true
            if ($fetch_data) {
                $response['results'] = $results;
                $response['row_count'] = $rowCount;
            }

            return $response;
        } catch (Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Filter sensitive data from query results
     *
     * @param  array  $results  The query results
     * @return array Filtered results with sensitive fields removed
     */
    private function filterSensitiveData(array $results): array
    {
        if (empty($results)) {
            return $results;
        }

        $sensitiveFields = [
            // Email patterns
            'email', 'e_mail', 'e-mail', 'mail', 'user_email', 'customer_email',
            // Phone patterns
            'phone', 'telephone', 'mobile', 'phone_number', 'mobile_number', 'contact_number', 'cell', 'cellphone',
            // Password patterns
            'password', 'passwd', 'pwd', 'user_password', 'hash', 'secret',
            // ID patterns
            'ssn', 'social_security', 'tax_id', 'national_id', 'id_number',
            // Payment patterns
            'credit_card', 'card_number', 'cc_number', 'payment_card',
            // Address patterns
            'address', 'street', 'city', 'postal_code', 'zip_code', 'zip',
        ];

        $filteredResults = [];
        foreach ($results as $row) {
            $filteredRow = [];
            foreach ((array) $row as $key => $value) {
                // Check if the column name contains any sensitive field pattern
                $isSensitive = false;
                foreach ($sensitiveFields as $field) {
                    if (stripos($key, $field) !== false) {
                        $isSensitive = true;
                        break;
                    }
                }

                // If sensitive, replace with redacted text
                if ($isSensitive) {
                    $filteredRow[$key] = '[REDACTED]';
                } else {
                    $filteredRow[$key] = $value;
                }
            }
            $filteredResults[] = (object) $filteredRow;
        }

        return $filteredResults;
    }
}
