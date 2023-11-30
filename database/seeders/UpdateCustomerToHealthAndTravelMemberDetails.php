<?php

namespace Database\Seeders;

use App\Enums\CustomerTypeEnum;
use App\Models\Customer;
use App\Models\HealthMemberDetail;
use App\Models\HealthQuote;
use App\Models\TravelMemberDetail;
use App\Models\TravelQuote;
use Illuminate\Database\Seeder;

class UpdateCustomerToHealthAndTravelMemberDetails extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update Customer Code into Customer Table
        // Customer::whereNull('code')->chunkById(100, function ($customers) {
        //     foreach ($customers as $customer) {
        //         if (! empty($customer->code)) {
        //             continue;
        //         }

        //         $customer->update(['code' => CustomerTypeEnum::IndividualShort.'-'.$customer->id]);
        //     }
        // });

        // Update Customer ID and Member Code into Health Members Table
        HealthMemberDetail::whereNull(['customer_id', 'code'])->chunkById(500, function ($healthMemberDetails) {
            foreach ($healthMemberDetails as $healthMemberDetail) {
                if (! empty($healthMemberDetail->customer_id) && ! empty($healthMemberDetail->code)) {
                    continue;
                }

                $healthQuoteLead = HealthQuote::where('id', $healthMemberDetail->health_quote_request_id)->first();
                if (isset($healthQuoteLead->customer)) {
                    $healthCustomerCount = HealthMemberDetail::where('customer_id', $healthQuoteLead->customer_id)->count();
                    $healthIterator = $healthCustomerCount;

                    // Fetch all Health Members against Quote ID
                    $healthMembers = HealthMemberDetail::where('health_quote_request_id', $healthMemberDetail->health_quote_request_id)->get();
                    foreach ($healthMembers as $healthMember) {
                        $healthIterator++;
                        $healthMember->update([
                            'customer_id' => $healthQuoteLead->customer_id,
                            'code' => CustomerTypeEnum::IndividualShort.'-'.$healthQuoteLead->customer_id.'-'.$healthIterator,
                        ]);
                    }
                }
            }
        });

        // Update Customer ID and Member Code into Travel Members Table
        TravelMemberDetail::whereNull(['customer_id', 'code'])->chunkById(500, function ($travelMemberDetails) {
            foreach ($travelMemberDetails as $travelMemberDetail) {
                if (! empty($travelMemberDetail->customer_id) && ! empty($travelMemberDetail->code)) {
                    continue;
                }

                $travelQuoteLead = TravelQuote::where('id', $travelMemberDetail->travel_quote_request_id)->first();
                if (isset($travelQuoteLead->customer)) {
                    $travelCustomerCount = TravelMemberDetail::where('customer_id', $travelQuoteLead->customer_id)->count();
                    $travelIterator = $travelCustomerCount;

                    // Fetch all Travel Members against Quote ID
                    $travelMembers = TravelMemberDetail::where('travel_quote_request_id', $travelMemberDetail->travel_quote_request_id)->get();
                    foreach ($travelMembers as $travelMember) {
                        $travelIterator++;
                        $travelMember->update([
                            'customer_id' => $travelQuoteLead->customer_id,
                            'code' => CustomerTypeEnum::IndividualShort.'-'.$travelQuoteLead->customer_id.'-'.$travelIterator,
                        ]);
                    }
                }
            }
        });
    }
}
