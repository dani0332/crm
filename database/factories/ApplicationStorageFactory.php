<?php

namespace Database\Factories;

use App\Models\ApplicationStorage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ApplicationStorageFactory extends Factory
{
    protected $model = ApplicationStorage::class;

    public function definition(): array
    {
        return [
            'key_name' => Str::upper(Str::random(24)),
            'value' => 1,
            'is_active' => true,
        ];
    }
}

