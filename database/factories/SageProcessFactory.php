<?php

namespace Database\Factories;

use App\Enums\SageEnum;
use App\Models\SageProcess;
use Illuminate\Database\Eloquent\Factories\Factory;

class SageProcessFactory extends Factory
{
    protected $model = SageProcess::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'insurance_provider_id' => null,
            'model_type' => null,
            'model_id' => null,
            'request' => null,
            'message' => null,
            'status' => SageEnum::SAGE_PROCESS_PENDING_STATUS,
        ];
    }
}
