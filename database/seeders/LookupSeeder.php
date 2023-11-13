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

        if (! DB::table('lookups')->where('key', LookupsEnum::COMPANY_TYPE)->first()) {
            DB::table('lookups')->insert([
                ['key' => LookupsEnum::COMPANY_TYPE, 'code' => 'Consultancy', 'text' => 'Consultancy'],
                ['key' => LookupsEnum::COMPANY_TYPE, 'code' => 'Manufacturing', 'text' => 'Manufacturing'],
                ['key' => LookupsEnum::COMPANY_TYPE, 'code' => 'LegalServices', 'text' => 'Legal Services'],
                ['key' => LookupsEnum::COMPANY_TYPE, 'code' => 'Brokers', 'text' => 'Brokers'],
                ['key' => LookupsEnum::COMPANY_TYPE, 'code' => 'Construction', 'text' => 'Construction'],
                ['key' => LookupsEnum::COMPANY_TYPE, 'code' => 'FoodBeverages', 'text' => 'Food & Beverages'],
                ['key' => LookupsEnum::COMPANY_TYPE, 'code' => 'ServiceProvider', 'text' => 'Service Provider'],
            ]);
        }

        if (! DB::table('lookups')->where('key', LookupsEnum::MEMBER_RELATION)->first()) {
            DB::table('lookups')->insert([
                ['key' => LookupsEnum::MEMBER_RELATION, 'code' => 'relHusband', 'text' => 'Husband'],
                ['key' => LookupsEnum::MEMBER_RELATION, 'code' => 'relWife', 'text' => 'Wife'],
                ['key' => LookupsEnum::MEMBER_RELATION, 'code' => 'relMother', 'text' => 'Mother'],
                ['key' => LookupsEnum::MEMBER_RELATION, 'code' => 'relFather', 'text' => 'Father'],
                ['key' => LookupsEnum::MEMBER_RELATION, 'code' => 'reSon', 'text' => 'Son'],
                ['key' => LookupsEnum::MEMBER_RELATION, 'code' => 'relDaughter', 'text' => 'Daughter'],
                ['key' => LookupsEnum::MEMBER_RELATION, 'code' => 'relBrother', 'text' => 'Brother'],
                ['key' => LookupsEnum::MEMBER_RELATION, 'code' => 'relSister', 'text' => 'Sister'],
                ['key' => LookupsEnum::MEMBER_RELATION, 'code' => 'relFriend', 'text' => 'Friend'],
                ['key' => LookupsEnum::MEMBER_RELATION, 'code' => 'relBusinessPartner', 'text' => 'Business Partner'],
            ]);
        }

        if (! DB::table('lookups')->where('key', LookupsEnum::UBO_RELATION)->first()) {
            DB::table('lookups')->insert([
                ['key' => LookupsEnum::UBO_RELATION, 'code' => 'relOwner', 'text' => 'Owner'],
                ['key' => LookupsEnum::UBO_RELATION, 'code' => 'relPartner', 'text' => 'Partner'],
            ]);
        }

        if (! DB::table('lookups')->where('key', LookupsEnum::ENTITY_TYPE)->first()) {
            DB::table('lookups')->insert([
                ['key' => LookupsEnum::ENTITY_TYPE, 'code' => 'Parent', 'text' => 'Parent'],
                ['key' => LookupsEnum::ENTITY_TYPE, 'code' => 'SubEntity', 'text' => 'Sub Entity'],
            ]);
        }

        if (! DB::table('lookups')->where('key', LookupsEnum::RESIDENT_STATUS)->first()) {
            DB::table('lookups')->insert([
                ['key' => LookupsEnum::RESIDENT_STATUS, 'code' => 'uaeResident', 'text' => 'UAE resident', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::RESIDENT_STATUS, 'code' => 'nonUaeResident', 'text' => 'Non UAE resident', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        if (! DB::table('lookups')->where('key', LookupsEnum::DOCUMENT_ID_TYPE)->first()) {
            DB::table('lookups')->insert([
                ['key' => LookupsEnum::DOCUMENT_ID_TYPE, 'code' => 'emiratesId', 'text' => 'Emirates Id', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::DOCUMENT_ID_TYPE, 'code' => 'passport', 'text' => 'Passport', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::DOCUMENT_ID_TYPE, 'code' => 'homeCountryId', 'text' => 'Home Country ID', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::DOCUMENT_ID_TYPE, 'code' => 'drivingLicense', 'text' => 'Driving License', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::DOCUMENT_ID_TYPE, 'code' => 'visa', 'text' => 'Visa', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        if (! DB::table('lookups')->where('key', LookupsEnum::MODE_OF_CONTACT)->first()) {
            DB::table('lookups')->insert([
                ['key' => LookupsEnum::MODE_OF_CONTACT, 'code' => 'phone', 'text' => 'Phone', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::MODE_OF_CONTACT, 'code' => 'email', 'text' => 'Email', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::MODE_OF_CONTACT, 'code' => 'phoneAndEmail', 'text' => 'Phone and Email', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::MODE_OF_CONTACT, 'code' => 'walkIn', 'text' => 'Walk-in', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        if (! DB::table('lookups')->where('key', LookupsEnum::LEGAL_STRUCTURE)->first()) {
            DB::table('lookups')->insert([
                ['key' => LookupsEnum::LEGAL_STRUCTURE, 'code' => 'establishment', 'text' => 'Establishment', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::LEGAL_STRUCTURE, 'code' => 'soleProprietorship', 'text' => 'Sole proprietorship', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::LEGAL_STRUCTURE, 'code' => 'privateJointStockCompany', 'text' => 'Private joint stock company', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::LEGAL_STRUCTURE, 'code' => 'limitedLiabilityCompany', 'text' => 'Limited liability company', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::LEGAL_STRUCTURE, 'code' => 'publicJointStockCompany', 'text' => 'Public joint stock company', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::LEGAL_STRUCTURE, 'code' => 'branchOfForeignCompany', 'text' => 'Branch of a foreign company', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        if (! DB::table('lookups')->where('key', LookupsEnum::ISSUANCE_PLACE)->first()) {
            DB::table('lookups')->insert([
                ['key' => LookupsEnum::ISSUANCE_PLACE, 'code' => 'dubai', 'text' => 'Dubai', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::ISSUANCE_PLACE, 'code' => 'abuDhabi', 'text' => 'Abu Dhabi', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::ISSUANCE_PLACE, 'code' => 'sharjah', 'text' => 'Sharjah', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::ISSUANCE_PLACE, 'code' => 'ummAlQuwain', 'text' => 'Umm Al Quwain', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::ISSUANCE_PLACE, 'code' => 'rasAlKhaima', 'text' => 'Ras Al Khaima', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::ISSUANCE_PLACE, 'code' => 'ajman', 'text' => 'Ajman', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::ISSUANCE_PLACE, 'code' => 'fujairah', 'text' => 'Fujairah', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        if (! DB::table('lookups')->where('key', LookupsEnum::ENTITY_DOCUMENT_TYPE)->first()) {
            DB::table('lookups')->insert([
                ['key' => LookupsEnum::ENTITY_DOCUMENT_TYPE, 'code' => 'tradeLicense', 'text' => 'Trade License', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::ENTITY_DOCUMENT_TYPE, 'code' => 'moa', 'text' => 'MOA', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::ENTITY_DOCUMENT_TYPE, 'code' => 'others', 'text' => 'Others', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        if (! DB::table('lookups')->where('key', LookupsEnum::ISSUING_AUTHORITY)->first()) {
            DB::table('lookups')->insert([
                ['key' => LookupsEnum::ISSUING_AUTHORITY, 'code' => 'DED', 'text' => 'Department of Economic Development (DED)', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::ISSUING_AUTHORITY, 'code' => 'FZA', 'text' => 'Free Zone Authorities', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::ISSUING_AUTHORITY, 'code' => 'DCCA', 'text' => 'Dubai Creative Clusters Authority (DCCA)', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::ISSUING_AUTHORITY, 'code' => 'ME', 'text' => 'Ministry of Economy', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::ISSUING_AUTHORITY, 'code' => 'DTCM', 'text' => 'Department of Tourism and Commerce Marketing (DTCM)', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::ISSUING_AUTHORITY, 'code' => 'DoHP', 'text' => 'Department of Health and Prevention (DoHP)', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::ISSUING_AUTHORITY, 'code' => 'MOHRE', 'text' => 'Ministry of Human Resources and Emiratisation (MOHRE)', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::ISSUING_AUTHORITY, 'code' => 'MI', 'text' => 'Ministry of Interior', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::ISSUING_AUTHORITY, 'code' => 'DoE', 'text' => 'Department of Energy (DoE)', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::ISSUING_AUTHORITY, 'code' => 'CBUAE', 'text' => 'Central Bank of the UAE', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::ISSUING_AUTHORITY, 'code' => 'TRA', 'text' => 'Telecommunications Regulatory Authority (TRA)', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::ISSUING_AUTHORITY, 'code' => 'DMCC', 'text' => 'Dubai Multi Commodities Centre (DMCC)', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        if (! DB::table('lookups')->where('key', LookupsEnum::EMPLOYMENT_SECTOR)->first()) {
            DB::table('lookups')->insert([
                ['key' => LookupsEnum::EMPLOYMENT_SECTOR, 'code' => 'government', 'text' => 'Government Sector', 'created_at' => now(), 'updated_at' =>
                    now()],
                ['key' => LookupsEnum::EMPLOYMENT_SECTOR, 'code' => 'semiGovernment', 'text' => 'Semi Government Sector', 'created_at' => now(), 'updated_at' =>
                    now()],
                ['key' => LookupsEnum::EMPLOYMENT_SECTOR, 'code' => 'private', 'text' => 'Private Sector', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::EMPLOYMENT_SECTOR, 'code' => 'freezone', 'text' => 'Freezone Sector', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        if (! DB::table('lookups')->where('key', LookupsEnum::COMPANY_POSITION)->first()) {
            DB::table('lookups')->insert([
                ['key' => LookupsEnum::COMPANY_POSITION, 'code' => 'owner', 'text' => 'Owner', 'created_at' => now(), 'updated_at' =>
                    now()],
                ['key' => LookupsEnum::COMPANY_POSITION, 'code' => 'partner', 'text' => 'Partner', 'created_at' => now(), 'updated_at' =>
                    now()],
                ['key' => LookupsEnum::COMPANY_POSITION, 'code' => 'shareholder', 'text' => 'Shareholder', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::COMPANY_POSITION, 'code' => 'manager', 'text' => 'Manager', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        if (! DB::table('lookups')->where('key', LookupsEnum::MODE_OF_DELIVERY)->first()) {
            DB::table('lookups')->insert([
                ['key' => LookupsEnum::MODE_OF_DELIVERY, 'code' => 'modSent', 'text' => 'Sent to customer via Email', 'created_at' => now(), 'updated_at' =>
                    now()],
                ['key' => LookupsEnum::MODE_OF_DELIVERY, 'code' => 'modDelivered', 'text' => 'Delivered to customer via Courier', 'created_at' => now(), 'updated_at' =>
                    now()],
                ['key' => LookupsEnum::MODE_OF_DELIVERY, 'code' => 'modCollected', 'text' => 'Collected by customer from office', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }
}
