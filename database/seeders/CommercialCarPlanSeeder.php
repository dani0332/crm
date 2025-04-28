<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CarPlan;
use App\Models\CarAddOn;
use App\Models\CarPlanAddon;
use App\Models\CarAddOnOption;
use Illuminate\Support\Facades\DB;
use App\Enums\QuoteTypeId;

class CommercialCarPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $carPlans = CarPlan::where('quote_type_id', 1)
            ->whereIn('repair_type', ['TPL', 'COMP', 'AGENCY'])
            ->groupBy('provider_id', 'repair_type')
            ->get();

        foreach ($carPlans as $basePlan) {

            $existingPlan = CarPlan::where([
                'quote_type_id' => QuoteTypeId::CompanyCar,
                'provider_id' => $basePlan->provider_id,
                'repair_type' => $basePlan->repair_type
            ])->first();

            if (!$existingPlan) {
                DB::beginTransaction();

                // 1. Create the new commercial car plan
                $newPlan = CarPlan::create(
                    [
                        'code' => null,
                        'text' => 'Commercial - ' . $this->getRepairTypeText($basePlan->repair_type),
                        'text_ar' => $basePlan->text_ar,
                        'provider_id' => $basePlan->provider_id,
                        'repair_type' => $basePlan->repair_type,
                        'plan_type_id' => $basePlan->plan_type_id,
                        'is_active' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'policy_document_link' => null,
                        'insurance_type' => $basePlan->insurance_type,
                        'deleted_at' => $basePlan->deleted_at,
                        'created_by' => 'danish.mehmood@myalfred.com',
                        'updated_by' => null,
                        'is_hidden' => $basePlan->is_hidden,
                        'pua_enabled' => 0,
                        'quote_type_id' => QuoteTypeId::CompanyCar,
                        'is_commercial' => 1
                    ]
                );

                $newPlanId = $newPlan->id;

                // 2. Create add-ons for the plan
                $this->createAddonsAndOptions($newPlanId);

                DB::commit();
            }
        }
    }

    /**
     * Get the human-readable repair type text
     *
     * @param string $repairType
     * @return string
     */
    private function getRepairTypeText(string $repairType): string
    {
        return match ($repairType) {
            'COMP' => 'Non Agency',
            'AGENCY' => 'Agency',
            'TPL' => 'Third Party',
            default => $repairType,
        };
    }

    /**
     * Create addons and their options for a given plan
     *
     * @param int $planId
     * @return void
     */
    private function createAddonsAndOptions(int $planId): void
    {
        // Define the addons
        $addons = [
            [
                'code' => 'myAlfred',
                'text' => 'myAlfred membership',
                'text_ar' => null,
                'description' => "As an InsuranceMarket.ae customer you get access to the myAlfred app, which includes exclusive offers and discounts from a whole host of non-insurance brands which add up to total savings of over AED 8,000.",
                'description_ar' => null,
                'type' => 'checkbox',
                'options' => [
                    [
                        'value' => 'Included',
                        'value_ar' => null,
                        'price' => 0,
                        'description' => 'As an InsuranceMarket.ae customer you get access to the myAlfred app, which includes exclusive offers and discounts...',
                    ]
                ]
            ],
            [
                'code' => 'fastTrackClaim',
                'text' => 'Fast track claims service',
                'text_ar' => null,
                'description' => "Through the fast track claims service, a dedicated claims manager mediates your claim with the insurance companies, right from claim registration to the the completion of vehicle repairs, all in a timely manner helping you every step of the way.",
                'description_ar' => null,
                'type' => 'checkbox',
                'options' => [
                    [
                        'value' => 'Included',
                        'value_ar' => null,
                        'price' => 0,
                        'description' => null,
                    ]
                ]
            ],
            [
                'code' => 'driverCover',
                'text' => 'Driver cover',
                'text_ar' => null,
                'description' => "Provides financial benefits should you sustain bodily injury as a result of an incident whilst driving your vehicle.",
                'description_ar' => null,
                'type' => 'checkbox',
                'options' => [
                    [
                        'value' => 'Included',
                        'value_ar' => null,
                        'price' => 0,
                        'description' => 'Provides financial benefits...',
                    ],
                    [
                        'value' => 'Optional',
                        'value_ar' => null,
                        'price' => 120,
                        'description' => 'Provides financial benefits...',
                    ]
                ]
            ],
            [
                'code' => 'passengerCover',
                'text' => 'Passenger Cover',
                'text_ar' => null,
                'description' => "Provides financial benefits should you sustain bodily injury as a result of an incident whilst travelling as a passenger in the insured person's vehicle.",
                'description_ar' => null,
                'type' => 'checkbox',
                'options' => [
                    [
                        'value' => 'Included',
                        'value_ar' => null,
                        'price' => 0,
                        'description' => 'Provides financial benefits should you sustain bodily injury...',
                    ],
                    [
                        'value' => 'Optional',
                        'value_ar' => null,
                        'price' => 30,
                        'description' => 'Provides financial benefits should you sustain bodily injury...',
                    ]
                ]
            ],
            [
                'code' => 'carHire',
                'text' => 'Car hire cover',
                'text_ar' => null,
                'description' => null,
                'description_ar' => null,
                'type' => 'checkbox',
                'options' => [
                    [
                        'value' => 'Included',
                        'value_ar' => null,
                        'price' => 0,
                        'description' => null,
                    ],
                    [
                        'value' => 'Optional',
                        'value_ar' => null,
                        'price' => 150,
                        'description' => null,
                    ]
                ]
            ],
            [
                'code' => 'breakdownCover',
                'text' => 'Roadside assistance',
                'text_ar' => null,
                'description' => "Comes to your roadside rescue when there's something wrong with your vehicle. From a flat tyre to a flat battery and more besides, this cover gets you moving again. Some policies even include fuel replacement (for when your tank is dry & you can't reach the nearest fuel station).",
                'description_ar' => null,
                'type' => 'checkbox',
                'options' => [
                    [
                        'value' => 'Included',
                        'value_ar' => null,
                        'price' => 0,
                        'description' => "Comes to your roadside rescue when there's something wrong with your vehicle...",
                    ],
                    [
                        'value' => 'Optional',
                        'value_ar' => null,
                        'price' => 25,
                        'description' => "Comes to your roadside rescue when there's something wrong with your vehicle...",
                    ]
                ]
            ],
            [
                'code' => 'omanCover',
                'text' => 'Oman Cover',
                'text_ar' => null,
                'description' => 'Covers your vehicle for loss and/or damage whilst it is being driven in the Sultanate of Oman (note this does not include third party liability for which an orange card is required).',
                'description_ar' => null,
                'type' => 'checkbox',
                'options' => [
                    [
                        'value' => 'Included',
                        'value_ar' => null,
                        'price' => 0,
                        'description' => null,
                    ]
                ]
            ],
        ];

        foreach ($addons as $addonData) {

            // Create the addon
            $addon = CarAddOn::create([
                'code' => $addonData['code'],
                'text' => $addonData['text'],
                'text_ar' => $addonData['text_ar'],
                'description' => $addonData['description'],
                'description_ar' => $addonData['description_ar'],
                'type' => $addonData['type'],
                'sort_order' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null
            ]);

            // Link the addon to the plan
            CarPlanAddon::create([
                'addon_id' => $addon->id,
                'plan_id' => $planId,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Create options for this addon
            foreach ($addonData['options'] as $optionData) {
                CarAddOnOption::create([
                    'value' => $optionData['value'],
                    'value_ar' => $optionData['value_ar'],
                    'addon_id' => $addon->id,
                    'price' => $optionData['price'],
                    'sort_order' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'description' => $optionData['description'],
                    'description_ar' => null,
                    'vehicle_type_id' => null
                ]);
            }
        }
    }
}
