<?php

namespace Database\Seeders;

use App\Enums\QuoteTypes;
use App\Models\Team;
use Illuminate\Database\Seeder;

class AddPersonalLobsProducts extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $productBike = Team::where('name', QuoteTypes::BIKE->value)->first();
        if ($productBike == null) {
            Team::insert(['name' => QuoteTypes::BIKE->value, 'type' => 1, 'created_at' => now()]);
        }

        $productYacht = Team::where('name', QuoteTypes::YACHT->value)->first();
        if ($productYacht == null) {
            Team::insert(['name' => QuoteTypes::YACHT->value, 'type' => 1, 'created_at' => now()]);
        }

        $productTravel = Team::where('name', QuoteTypes::TRAVEL->value)->first();
        if ($productTravel == null) {
            Team::insert(['name' => QuoteTypes::TRAVEL->value, 'type' => 1, 'created_at' => now()]);
        }

        $productCycle = Team::where('name', QuoteTypes::CYCLE->value)->first();
        if ($productCycle == null) {
            Team::insert(['name' => QuoteTypes::CYCLE->value, 'type' => 1, 'created_at' => now()]);
        }

        $productJetski = Team::where('name', QuoteTypes::JETSKI->value)->first();
        if ($productJetski == null) {
            Team::insert(['name' => QuoteTypes::JETSKI->value, 'type' => 1, 'created_at' => now()]);
        }

    }
}
