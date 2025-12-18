<?php

namespace Database\Factories;

use App\Models\CyberQuote;
use App\Models\Emirate;
use App\Models\PersonalQuote;
use Illuminate\Database\Eloquent\Factories\Factory;

class CyberQuoteFactory extends Factory
{
    protected $model = CyberQuote::class;

    public function definition(): array
    {
        return [
            'personal_quote_id' => PersonalQuote::factory(),
            'emirate_of_registration_id' => Emirate::factory(),
            'coverage_id' => 1,
        ];
    }
}

