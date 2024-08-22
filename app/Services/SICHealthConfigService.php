<?php

namespace App\Services;

use Exception;
use App\Enums\QuoteTypeId;
use App\Models\Nationality;
use App\Models\HealthPlanType;
use App\Models\MemberCategory;
use App\Models\SICHealthConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SICHealthConfigService extends BaseService
{
    public function getEntity()
    {
        return SICHealthConfig::latest()->first();
    }

    public function saveEntity($id, $data)
    {

        // try {
            $sicHealthConfig =  SICHealthConfig::where('quote_type_id',QuoteTypeId::Health)->first();
            $payload= [
                'is_age' => $data['is_age'],
                'min_age' => $data['min_age'],
                'max_age' => $data['max_age'],
                'is_type' => $data['is_type'],
                'quote_type_id' => QuoteTypeId::Health,
                'is_nationality' => $data['is_nationality'],
                'is_member_category' => $data['is_member_category'],
            ];
            if(empty($sicHealthConfig)){
                $sicHealthConfig =   SICHealthConfig::create($payload);
            }
            else {
                $sicHealthConfig->update($payload);
            }


            // Sync relationships
                if (isset($data['plan_types'])) {
                    $this->syncData( $data['plan_types'], $sicHealthConfig->id,HealthPlanType::class);
                }

                if (isset($data['nationalities'])) {
                    $this->syncData($data['nationalities'], $sicHealthConfig->id,Nationality::class);
                }

                if (isset($data['member_categories'])) {
                    $this->syncData($data['member_categories'], $sicHealthConfig->id,MemberCategory::class);
                }

            return $sicHealthConfig;
        // } catch (Exception $e) {
            Log::error('SIC Health Config Error: '.$e->getMessage());
        // }
    }

    public function syncData(array $Ids, $configId, $configurableType)
    {

        DB::transaction(function () use ($Ids, $configId,$configurableType) {

            // Fetch existing records for the given configId
            $existingRecords = DB::table('sic_configables')
                ->where('sic_config_id', $configId)
                ->where('configurable_type', $configurableType)
                ->pluck('configurable_id')
                ->toArray();

            // Determine which IDs need to be inserted and deleted
            $idsToInsert = array_diff($Ids, $existingRecords);
            $idsToDelete = array_diff($existingRecords, $Ids);

            // Insert new records if any
            if ($idsToInsert) {
                $insertData = array_map(fn($planTypeId) => [
                    'sic_config_id' => $configId,
                    'configurable_type' => $configurableType,
                    'configurable_id' => $planTypeId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $idsToInsert);

                DB::table('sic_configables')->insert($insertData);
            }

            // Delete records that are no longer in the provided list
            if ($idsToDelete) {
                DB::table('sic_configables')
                    ->where('sic_config_id', $configId)
                    ->where('configurable_type', $configurableType)
                    ->whereIn('configurable_id', $idsToDelete)
                    ->delete();
            }
        });
    }

}
