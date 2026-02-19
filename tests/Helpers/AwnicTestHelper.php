<?php

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use App\Models\PersonalQuote;
use App\Models\PolicyIssuance;

if (! function_exists('createAwnicPolicyIssuanceProcess')) {
    function createAwnicPolicyIssuanceProcess(PersonalQuote $quote): PolicyIssuance
    {
        $process = PolicyIssuance::factory()->forQuote($quote)->create([
            'status' => null,
            'completed_step' => null,
        ]);

        return $process->setRelation('model', $quote);
    }
}

if (! function_exists('seedAwnicApplicationStorage')) {
    function seedAwnicApplicationStorage(): void
    {
        $entries = [
            ApplicationStorageEnums::ENABLE_AWNI_CYBER_POLICY_ISSUANCE,
            ApplicationStorageEnums::ENABLE_RETRY_TIMEOUT_AWNI_CYBER_POLICY_ISSUANCE,
            ApplicationStorageEnums::CHIEF_DEPUTY_OFFICER_MOBILE_NO,
        ];

        foreach ($entries as $key) {
            $payload = [
                'key_name' => $key,
                'value' => 1,
                'is_active' => 1,
            ];

            $record = ApplicationStorage::query()->where('key_name', $key)->first();

            if ($record) {
                $record->update($payload);
            } else {
                ApplicationStorage::factory()->state($payload)->create();
            }
        }
    }
}
