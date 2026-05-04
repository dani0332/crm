<?php

namespace Database\Seeders;

use App\Enums\QuoteTypeId;
use App\Models\BusinessQuote;
use App\Models\CustomerInsured;
use App\Models\Insured;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * One-off: set {@see BusinessQuote} {@code emirate_of_registration_id} from {@see Insured}
 * via {@see CustomerInsured} for business quotes where that column is currently null.
 *
 * Join path: {@code business_quote_request} → {@code customer_insured} ({@code quote_request_id}, {@code quote_type_id} = Business, active row)
 * → {@code insured} ({@code insured_id}); copy {@code insured.emirate_of_registration_id} when present.
 */
class BackfillBusinessQuoteEmirateFromInsuredSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('business_quote_request as bqr')
            ->join('customer_insured as ci', function ($join): void {
                $join->on('bqr.id', '=', 'ci.quote_request_id')
                    ->where('ci.quote_type_id', QuoteTypeId::Business)
                    ->where('ci.is_active', true);
            })
            ->join('insured as i', 'ci.insured_id', '=', 'i.id')
            ->whereNull('bqr.emirate_of_registration_id')
            ->whereNotNull('i.emirate_of_registration_id')
            ->where('i.emirate_of_registration_id', '>', 0)
            ->update([
                'bqr.emirate_of_registration_id' => DB::raw('i.emirate_of_registration_id'),
                'bqr.updated_at' => now(),
            ]);
    }
}
