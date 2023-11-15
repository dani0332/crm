<?php

namespace Database\Seeders;

use App\Enums\LookupsEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LookupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (! $exists = DB::table('lookups')->where('key', LookupsEnum::JETSKI_MATERIALS)->first()) {
            DB::table('lookups')->insert([
                ['quote_type_id' => QuoteTypes::JETSKI->id(), 'key' => LookupsEnum::JETSKI_MATERIALS, 'code' => 'matFrp', 'text' => 'FRP', 'created_at' => now(), 'updated_at' => now()],
                ['quote_type_id' => QuoteTypes::JETSKI->id(), 'key' => LookupsEnum::JETSKI_MATERIALS, 'code' => 'matGrp', 'text' => 'GRP', 'created_at' => now(), 'updated_at' => now()],
                ['quote_type_id' => QuoteTypes::JETSKI->id(), 'key' => LookupsEnum::JETSKI_MATERIALS, 'code' => 'matOther', 'text' => 'OTHER', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        if (! $exists = DB::table('lookups')->where('key', LookupsEnum::JETSKI_USES)->first()) {
            DB::table('lookups')->insert([
                ['quote_type_id' => QuoteTypes::JETSKI->id(), 'key' => LookupsEnum::JETSKI_USES, 'code' => 'commercialUse', 'text' => 'Commercial use (Rental business)', 'created_at' => now(), 'updated_at' => now()],
                ['quote_type_id' => QuoteTypes::JETSKI->id(), 'key' => LookupsEnum::JETSKI_USES, 'code' => 'privateUse', 'text' => 'Private use', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        if (! $exists = DB::table('lookups')->where('key', LookupsEnum::PET_TYPES)->first()) {
            DB::table('lookups')->insert([
                ['quote_type_id' => QuoteTypes::PET->id(), 'key' => LookupsEnum::PET_TYPES, 'code' => 'dog', 'text' => 'Dog', 'created_at' => now(), 'updated_at' => now()],
                ['quote_type_id' => QuoteTypes::PET->id(), 'key' => LookupsEnum::PET_TYPES, 'code' => 'cat', 'text' => 'Cat', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        if (! $exists = DB::table('lookups')->where('key', LookupsEnum::PET_AGES)->first()) {
            DB::table('lookups')->insert([
                ['quote_type_id' => QuoteTypes::PET->id(), 'key' => LookupsEnum::PET_AGES, 'code' => 'lessThan1Year', 'text' => 'Less than 1 year old', 'created_at' => now(), 'updated_at' => now()],
                ['quote_type_id' => QuoteTypes::PET->id(), 'key' => LookupsEnum::PET_AGES, 'code' => '1yearOld', 'text' => '1 year old', 'created_at' => now(), 'updated_at' => now()],
                ['quote_type_id' => QuoteTypes::PET->id(), 'key' => LookupsEnum::PET_AGES, 'code' => '2yearOld', 'text' => '2 year old', 'created_at' => now(), 'updated_at' => now()],
                ['quote_type_id' => QuoteTypes::PET->id(), 'key' => LookupsEnum::PET_AGES, 'code' => '3yearOld', 'text' => '3 year old', 'created_at' => now(), 'updated_at' => now()],
                ['quote_type_id' => QuoteTypes::PET->id(), 'key' => LookupsEnum::PET_AGES, 'code' => '4yearOld', 'text' => '4 year old', 'created_at' => now(), 'updated_at' => now()],
                ['quote_type_id' => QuoteTypes::PET->id(), 'key' => LookupsEnum::PET_AGES, 'code' => '5yearOld', 'text' => '5 year old', 'created_at' => now(), 'updated_at' => now()],
                ['quote_type_id' => QuoteTypes::PET->id(), 'key' => LookupsEnum::PET_AGES, 'code' => '6yearOld', 'text' => '6 year old', 'created_at' => now(), 'updated_at' => now()],
                ['quote_type_id' => QuoteTypes::PET->id(), 'key' => LookupsEnum::PET_AGES, 'code' => '7yearOld', 'text' => '7 year old', 'created_at' => now(), 'updated_at' => now()],
                ['quote_type_id' => QuoteTypes::PET->id(), 'key' => LookupsEnum::PET_AGES, 'code' => '8yearOld', 'text' => '8 year old', 'created_at' => now(), 'updated_at' => now()],
                ['quote_type_id' => QuoteTypes::PET->id(), 'key' => LookupsEnum::PET_AGES, 'code' => '9yearOld', 'text' => '9 year old', 'created_at' => now(), 'updated_at' => now()],
                ['quote_type_id' => QuoteTypes::PET->id(), 'key' => LookupsEnum::PET_AGES, 'code' => '10yearOld', 'text' => '10 year old', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        if (! DB::table('lookups')->where('key', LookupsEnum::CAR_LOST_REJECT_REASONS)->first()) {
            DB::table('lookups')->insert([
                ['quote_type_id' => QuoteTypeId::Car, 'key' => LookupsEnum::CAR_LOST_REJECT_REASONS, 'code' => 'rejectInvalidProof', 'text' => 'Incorrect proof attached', 'created_at' => now(), 'updated_at' => now()],
                ['quote_type_id' => QuoteTypeId::Car, 'key' => LookupsEnum::CAR_LOST_REJECT_REASONS, 'code' => 'rejectMissingContact', 'text' => 'Proof attached does not show the contact information', 'created_at' => now(), 'updated_at' => now()],
                ['quote_type_id' => QuoteTypeId::Car, 'key' => LookupsEnum::CAR_LOST_REJECT_REASONS, 'code' => 'rejectNoFollowup', 'text' => 'No follow-up call or email has been made by the advisor', 'created_at' => now(), 'updated_at' => now()],
                ['quote_type_id' => QuoteTypeId::Car, 'key' => LookupsEnum::CAR_LOST_REJECT_REASONS, 'code' => 'rejectReachable', 'text' => 'Client is reachable', 'created_at' => now(), 'updated_at' => now()],
                ['quote_type_id' => QuoteTypeId::Car, 'key' => LookupsEnum::CAR_LOST_REJECT_REASONS, 'code' => 'rejectResponded', 'text' => 'Client responded on email', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        if (! DB::table('lookups')->where('key', LookupsEnum::CAR_LOST_APPROVE_REASONS)->first()) {
            DB::table('lookups')->insert([
                ['quote_type_id' => QuoteTypeId::Car, 'key' => LookupsEnum::CAR_LOST_APPROVE_REASONS, 'code' => 'confirmedSold', 'text' => 'Confirmed Sold', 'created_at' => now(), 'updated_at' => now()],
                ['quote_type_id' => QuoteTypeId::Car, 'key' => LookupsEnum::CAR_LOST_APPROVE_REASONS, 'code' => 'approveUnreachable', 'text' => 'Client is unreachable on call and email', 'created_at' => now(), 'updated_at' => now()],
                ['quote_type_id' => QuoteTypeId::Car, 'key' => LookupsEnum::CAR_LOST_APPROVE_REASONS, 'code' => 'approveInvalidNumber', 'text' => 'Invalid number', 'created_at' => now(), 'updated_at' => now()],
                ['quote_type_id' => QuoteTypeId::Car, 'key' => LookupsEnum::CAR_LOST_APPROVE_REASONS, 'code' => 'approveIncorrectNumber', 'text' => 'Incorrect number - number belongs to a different person', 'created_at' => now(), 'updated_at' => now()],
                ['quote_type_id' => QuoteTypeId::Car, 'key' => LookupsEnum::CAR_LOST_APPROVE_REASONS, 'code' => 'approveInvalidEmail', 'text' => 'Invalid email ID', 'created_at' => now(), 'updated_at' => now()],
                ['quote_type_id' => QuoteTypeId::Car, 'key' => LookupsEnum::CAR_LOST_APPROVE_REASONS, 'code' => 'approveIncorrectEmail', 'text' => 'Incorrect email ID - email ID belongs to a different person', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        if (! DB::table('lookups')->where('key', LookupsEnum::TRANSACTION_TYPES)->first()) {
            DB::table('lookups')->insert([
                ['key' => LookupsEnum::TRANSACTION_TYPES, 'code' => 'newBusiness', 'text' => 'New Business', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::TRANSACTION_TYPES, 'code' => 'extCustomerRenewal', 'text' => "Existing Customer's Renewal", 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::TRANSACTION_TYPES, 'code' => 'extCustomerNewBusiness', 'text' => "Existing Customer's New Business", 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::TRANSACTION_TYPES, 'code' => 'endorsement', 'text' => 'Endorsement', 'created_at' => now(), 'updated_at' => now()],

            ]);
        }
    }
}
