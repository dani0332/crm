<?php

namespace Database\Factories;

use App\Enums\ProcessStatusCode;
use App\Enums\RenewalsUploadType;
use App\Models\RenewalsUploadLeads;
use Illuminate\Database\Eloquent\Factories\Factory;

class RenewalsUploadLeadsFactory extends Factory
{
    protected $model = RenewalsUploadLeads::class;

    public function definition(): array
    {
        return [
            'file_name' => $this->faker->filePath(),
            'file_path' => $this->faker->filePath(),
            'quote_type' => 'CAR',
            'status' => ProcessStatusCode::IN_PROGRESS,
            'renewal_import_type' => RenewalsUploadType::UPDATE_LEADS,
            'renewal_import_code' => $this->faker->bothify('IM-#####'),
            'is_sic' => 0,
            'total_records' => 0,
            'good' => 0,
            'cannot_upload' => 0,
            'skip_plans' => null,
            'created_by_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function forQuoteType(string $quoteType): self
    {
        return $this->state(fn () => ['quote_type' => $quoteType]);
    }

    public function completed(): self
    {
        return $this->state(fn () => ['status' => ProcessStatusCode::COMPLETED]);
    }
}
