<?php

namespace Database\Seeders;

use App\Models\Lookup;
use Illuminate\Database\Seeder;

class FixSendUpdateLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $allMainTypes = Lookup::where('code', 'send-update-code')->get(); // Endorsement, Cancellation from inception, Correction of policy.

        foreach ($allMainTypes as $mainType) {
            info('Main Type: '.$mainType->key);
            $allSubTypes = Lookup::where('parent_id', $mainType->id)->get(); // EF EN, CI CIR, CPU CPD.

            foreach ($allSubTypes as $subType) {
                info('Sub Type: '.$subType->key);
                $childTypes = Lookup::where('parent_id', $subType->id)->get(); // all subtypes

                foreach ($childTypes as $childType) {
                    $updatedChildType = Lookup::where('id', $childType->id)->whereNull('description')->first();
                    info('Child Type: '.$childType->key);

                    if ($updatedChildType) {
                        $updatedChildType->quote_type_id = $childType->quote_type_id ?? null;
                        // $updatedChildType->business_insurance_type_id => $businessInsuranceTypeId ?? null,
                        $updatedChildType->key = strtolower(str_replace(' ', '-', ucwords($childType->key)));
                        $updatedChildType->text = $childType->key;
                        $updatedChildType->code = $childType->code;
                        $updatedChildType->description = $childType->text;
                        $updatedChildType->parent_id = $childType->parent_id ?? null;

                        $updatedChildType->update();
                    }
                }

                /* $subType->update([
                    'quote_type_id' => $subType->quote_type_id ?? null,
                    // 'business_insurance_type_id' => $businessInsuranceTypeId ?? null,
                    'key' => strtolower(str_replace(' ', '-', ucwords($subType->key))),
                    'text' => $subType->key,
                    'code' => $subType->code,
                    'description' => $subType->text,
                    'parent_id' => $subType->parent_id ?? null,
                ]); */
            }

            /* $mainType->update([
                'quote_type_id' => $mainType->quote_type_id ?? null,
                // 'business_insurance_type_id' => $businessInsuranceTypeId ?? null,
                'key' => strtolower(str_replace(' ', '-', ucwords($mainType->key))),
                'text' => $mainType->key,
                'code' => $mainType->code,
                'description' => $mainType->text,
                'parent_id' => $mainType->parent_id ?? null,
            ]); */
        }
    }
}
