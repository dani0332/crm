<?php

namespace App\Services;

use App\Enums\QuoteTypeId;
use App\Models\HealthPlanType;
use App\Models\MemberCategory;
use App\Models\Nationality;
use App\Models\SICConfig;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\SICConfigurables;

class SICConfigurableService extends BaseService
{
    public function getEntity()
    {
        $sic = SICConfig::where('quote_type_id', QuoteTypeId::Health)
            ->with('sicConfigurables.configurable') // Eager load the 'configurable' relationship
            ->first();
        // Initialize an array to group configurables by their model name
        $data = [];
        if (! empty($sic) && ! empty($sic->sicConfigurables)) {
            // Get grouped data using the relationList method
            $groupedData = $this->relationList($sic->sicConfigurables);
            // Process and format the grouped data
            foreach ($groupedData as $alias => $configurables) {
                // Create a new array for each alias with the alias as the key
                $data[$alias] = [];
                foreach ($configurables as $configurable) {
                    // Add each configurable instance to the array for this alias
                    $data[$alias][] = $configurable;
                }
            }

        }

        return (object) ['data' => $sic, 'relations' => $data];
    }

    public function relationList($sicConfigurables)
    {
        // Define aliases for each model class
        $modelAliases = [
            HealthPlanType::class => 'health_plan_types',
            Nationality::class => 'nationalities',
            MemberCategory::class => 'member_categories',
            // Add more model classes and their aliases as needed
        ];
        // Initialize an array to hold grouped data
        $groupedConfigurable = [];
        // Loop through each SicConfigurables and group by model class
        foreach ($sicConfigurables as $sicConfigurable) {
            $configurable =$sicConfigurable->configurable;
            $modelClass = get_class($configurable); // Get the fully qualified class name
            // Use the alias or default to class name if alias not defined
            $alias = $modelAliases[$modelClass] ?? class_basename($modelClass);
            // Add configurable to the grouped data using alias
            if (! isset($groupedConfigurable[$alias])) {
                $groupedConfigurable[$alias] = [];
            }
            $groupedConfigurable[$alias][] = $configurable;
        }

        return $groupedConfigurable;
    }
    public function saveEntity($id, $data)
    {

        try {
           $sicConfigurable = SICConfig::where('quote_type_id', QuoteTypeId::Health)->first();
            $payload = [
                'is_age' => $data['is_age'],
                'min_age' => $data['min_age'],
                'max_age' => $data['max_age'],
                'is_type' => $data['is_type'],
                'quote_type_id' => QuoteTypeId::Health,
                'is_nationality' => $data['is_nationality'],
                'is_member_category' => $data['is_member_category'],
            ];
            if (empty($sicConfigurable)) {
               $sicConfigurable = SICConfig::create($payload);
            } else {
               $sicConfigurable->update($payload);
            }
            // Sync relationships
            if (isset($data['plan_types'])) {
                $this->syncData($data['plan_types'],$sicConfigurable->id, HealthPlanType::class);
            }

            if (isset($data['nationalities'])) {
                $this->syncData($data['nationalities'],$sicConfigurable->id, Nationality::class);
            }

            if (isset($data['member_categories'])) {
                $this->syncData($data['member_categories'],$sicConfigurable->id, MemberCategory::class);
            }

            return$sicConfigurable;
        } catch (Exception $e) {
            Log::error('SIC Health Config Error: '.$e->getMessage());
        }
    }

    public function syncData(array $ids, $configId, $configurableType)
    {

        DB::transaction(function () use ($ids, $configId, $configurableType) {

            // Fetch existing records for the given configId
            $existingRecords = DB::table('sic_configurables')
                ->where('sic_config_id', $configId)
                ->where('configurable_type', $configurableType)
                ->pluck('configurable_id')
                ->toArray();

            // Determine which IDs need to be inserted and deleted
            $idsToInsert = array_diff($ids, $existingRecords);
            $idsToDelete = array_diff($existingRecords, $ids);

            // Insert new records if any
            if ($idsToInsert) {
                $insertData = array_map(fn ($planTypeId) => [
                    'sic_config_id' => $configId,
                    'configurable_type' => $configurableType,
                    'configurable_id' => $planTypeId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $idsToInsert);

                DB::table('sic_configurables')->insert($insertData);
            }

            // Delete records that are no longer in the provided list
            if ($idsToDelete) {
                DB::table('sic_configurables')
                    ->where('sic_config_id', $configId)
                    ->where('configurable_type', $configurableType)
                    ->whereIn('configurable_id', $idsToDelete)
                    ->delete();
            }
        });
    }

}
