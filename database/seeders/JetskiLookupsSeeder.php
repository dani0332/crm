<?php

namespace Database\Seeders;

use App\Enums\LookupsEnum;
use App\Enums\QuoteTypes;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JetskiLookupsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if( (!$exists = DB::table('lookups')->where('key', LookupsEnum::JETSKI_MATERIALS)->first() ) ) {
            DB::table('lookups')->insert([
                ['quote_type_id' => QuoteTypes::JETSKI->id(), 'key' => LookupsEnum::JETSKI_MATERIALS, 'code' => 'matFrp', 'text' => 'FRP', 'created_at' => now(), 'updated_at' => now()],
                ['quote_type_id' => QuoteTypes::JETSKI->id(), 'key' => LookupsEnum::JETSKI_MATERIALS, 'code' => 'matGrp', 'text' => 'GRP', 'created_at' => now(), 'updated_at' => now()],
                ['quote_type_id' => QuoteTypes::JETSKI->id(), 'key' => LookupsEnum::JETSKI_MATERIALS, 'code' => 'matOther', 'text' => 'OTHER', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        if( (!$exists = DB::table('lookups')->where('key', LookupsEnum::JETSKI_USES)->first() ) ) {
            DB::table('lookups')->insert([
                ['quote_type_id' => QuoteTypes::JETSKI->id(), 'key' => LookupsEnum::JETSKI_USES, 'code' => 'commercialUse', 'text' => 'Commercial use (Rental business)', 'created_at' => now(), 'updated_at' => now()],
                ['quote_type_id' => QuoteTypes::JETSKI->id(), 'key' => LookupsEnum::JETSKI_USES, 'code' => 'privateUse', 'text' => 'Private use', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }
}
