<?php

declare(strict_types=1);

namespace App\Services\Quotes;

use Illuminate\Support\Facades\DB;

/**
 * Resolves primary insured / ID fields on personal_quotes for AML automation,
 * mirroring {@see CyberQuoteService::getCustomerCyberInfo} for any personal LOB quote_type_id.
 */
final class PersonalQuoteAmlAutomationCustomerService
{
    /**
     * @return object|false Row with keys: id, code, customer_id, gender, first_name, last_name, dob, nationality_id, id_type, id_number
     */
    public function getCustomerPersonalQuoteAmlInfo(int $quoteRequestId, int $quoteTypeId): object|false
    {
        return DB::table('personal_quotes as pq')
            ->leftJoin('customer_insured as ci', function ($join) use ($quoteTypeId) {
                $join->on('ci.quote_request_id', '=', 'pq.id')
                    ->where('ci.quote_type_id', '=', $quoteTypeId);
            })
            ->leftJoin('insured as i', 'ci.insured_id', '=', 'i.id')
            ->select(
                'pq.id',
                'pq.code',
                'pq.customer_id',
                'pq.gender',
                'pq.first_name',
                'pq.last_name',
                'i.dob',
                'pq.nationality_id',
                'i.id_type',
                'i.id_number'
            )
            ->where('pq.id', $quoteRequestId)
            ->where('pq.quote_type_id', $quoteTypeId)
            ->orderByDesc('ci.updated_at')
            ->first() ?? false;
    }

    /**
     * @param  array<string, mixed>  $personalQuoteRow
     * @return array{status: bool, message: string}
     */
    public function checkCustomerPersonalQuoteAmlInfoIsComplete(array $personalQuoteRow): array
    {
        $message = '';
        $requiredProperty = collect(['first_name', 'last_name', 'dob', 'nationality_id']);

        $missingDetails = [];
        foreach ($requiredProperty as $value) {
            if (empty($personalQuoteRow[$value])) {
                $propertyName = match ($value) {
                    'dob' => 'date of birth',
                    'nationality_id' => 'nationality',
                    default => str_replace(['-', '_'], ' ', (string) $value)
                };
                $missingDetails[] = ucwords($propertyName);
            }
        }

        if (empty($personalQuoteRow['id_number'])) {
            $missingDetails[] = 'ID Number (Passport or Emirates ID)';
        }

        $missingDetailCount = count($missingDetails);
        if ($missingDetailCount) {
            $message = 'Missing Info: '.implode(', ', $missingDetails);
        }

        return ['status' => $missingDetailCount === 0, 'message' => $message];
    }
}
