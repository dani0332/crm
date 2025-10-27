<?php

namespace Database\Seeders;

use App\Enums\InsuranceProvidersEnum;
use App\Enums\LookupsEnum;
use App\Models\InsuranceProvider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NonApiInsurerVehicleColorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $insurerList = [
            InsuranceProvidersEnum::RAK,
            InsuranceProvidersEnum::DNIRC,
            InsuranceProvidersEnum::AMJ,
            InsuranceProvidersEnum::FID,
            InsuranceProvidersEnum::NT,
            InsuranceProvidersEnum::AFNIC,
        ];
        $GIGInsurerProvider = InsuranceProvider::where('code', InsuranceProvidersEnum::AXA)->first();

        DB::transaction(function () use ($insurerList, $GIGInsurerProvider) {
            foreach ($insurerList as $insurer) {
                $targetProvider = InsuranceProvider::where('code', $insurer)->first();

                // To add all rows at once rather than in loop
                DB::table('lookups')->insertUsing(
                    ['quote_type_id', 'insurance_provider_id', 'key', 'code', 'text', 'parent_id', 'is_active', 'created_at', 'updated_at'],
                    DB::table('lookups')
                        ->selectRaw(
                            'quote_type_id, ? as insurance_provider_id, `key`, `code`, `text`, `parent_id`, `is_active`, NOW(), NOW()',
                            [$targetProvider->id]
                        )
                        ->where('insurance_provider_id', $GIGInsurerProvider->id)
                        ->where('key', LookupsEnum::VEHICLE_COLOR)
                );
            }
        });
    }
}
