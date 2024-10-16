<?php

namespace Database\Seeders;

use App\Enums\quoteBusinessTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\BusinessInsuranceType;
use App\Models\Lookup;
use Illuminate\Database\Seeder;

class SendUpdateAdditionalTaxInvoice extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $allLOBs = $this->getAllLOBs();

        $parent = Lookup::where('code', SendUpdateLogStatusEnum::EF)->first();

        foreach ($allLOBs as $lob) {
            $businessInsuranceTypeId = null;
            if (in_array($lob, ['GroupMedical', 'MotorFleet'])) {
                if ($lob === 'GroupMedical') {
                    $businessInsuranceType = quoteBusinessTypeCode::groupMedical;
                } elseif ($lob === 'MotorFleet') {
                    $businessInsuranceType = quoteBusinessTypeCode::carFleet;
                }

                $businessInsuranceTypeId = BusinessInsuranceType::where('code', $businessInsuranceType)->first()->id;
            }

            Lookup::firstOrCreate([
                'quote_type_id' => $lob->id,
                'business_insurance_type_id' => $businessInsuranceTypeId ?? null,
                'key' => 'additional-tax-invoice-commission-booking',
                'text' => 'Additional tax invoice and commission booking',
                'code' => SendUpdateLogStatusEnum::ATICB,
                'parent_id' => $parent->id,
            ], [
                'description' => 'Select this option when you need to book additional tax invoices and commission related to the initial policy. This might include the additional tax invoices for subgroups.',
            ]);
        }
    }

    private function getAllLOBs()
    {
        return collect(QuoteTypeId::getOptions())->map(function ($value, $key) {
            return [
                'id' => $key,
                'name' => $value,
            ];
        });
    }
}
