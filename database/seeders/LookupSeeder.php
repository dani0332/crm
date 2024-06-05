<?php

namespace Database\Seeders;

use App\Enums\LookupsEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\Lookup;
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
                ['key' => LookupsEnum::TRANSACTION_TYPES, 'code' => LookupsEnum::NEW_BUSINESS, 'text' => 'New Business', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::TRANSACTION_TYPES, 'code' => LookupsEnum::EXT_CUSTOMER_RENWAL, 'text' => "Existing Customer's Renewal", 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::TRANSACTION_TYPES, 'code' => LookupsEnum::EXT_CUSTOMER_NEW_BUSINESS, 'text' => "Existing Customer's New Business", 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::TRANSACTION_TYPES, 'code' => LookupsEnum::ENDORSEMENT, 'text' => 'Endorsement', 'created_at' => now(), 'updated_at' => now()],

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
        Lookup::firstOrCreate(['key' => LookupsEnum::LEGAL_STRUCTURE, 'code' => 'freeZoneEstablish'], ['text' => 'Free Zone Establishment']);

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
        Lookup::firstOrCreate(['key' => LookupsEnum::ISSUING_AUTHORITY, 'code' => 'FAICCPS'], ['text' => 'Federal Authority for Identity, Citizenship, Customs and Port Security']);
        Lookup::firstOrCreate(['key' => LookupsEnum::ISSUING_AUTHORITY, 'code' => 'issuingAuthOthers'], ['text' => 'Others']);

        if (! DB::table('lookups')->where('key', LookupsEnum::EMPLOYMENT_SECTOR)->first()) {
            DB::table('lookups')->insert([
                ['key' => LookupsEnum::EMPLOYMENT_SECTOR, 'code' => 'government', 'text' => 'Government Sector', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::EMPLOYMENT_SECTOR, 'code' => 'semiGovernment', 'text' => 'Semi Government Sector', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::EMPLOYMENT_SECTOR, 'code' => 'private', 'text' => 'Private Sector', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::EMPLOYMENT_SECTOR, 'code' => 'freezone', 'text' => 'Freezone Sector', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
        Lookup::firstOrCreate(['key' => LookupsEnum::EMPLOYMENT_SECTOR, 'code' => 'unspecifiedEmpSec'], ['text' => 'Unspecified']);

        if (! DB::table('lookups')->where('key', LookupsEnum::COMPANY_POSITION)->first()) {
            DB::table('lookups')->insert([
                ['key' => LookupsEnum::COMPANY_POSITION, 'code' => 'owner', 'text' => 'Owner', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::COMPANY_POSITION, 'code' => 'partner', 'text' => 'Partner', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::COMPANY_POSITION, 'code' => 'shareholder', 'text' => 'Shareholder', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::COMPANY_POSITION, 'code' => 'manager', 'text' => 'Manager', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        if (! DB::table('lookups')->where('key', LookupsEnum::COMPANY_TYPE)->first()) {
            DB::table('lookups')->insert([
                ['key' => LookupsEnum::COMPANY_TYPE, 'code' => 'Consultancy', 'text' => 'Consultancy', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::COMPANY_TYPE, 'code' => 'Manufacturing', 'text' => 'Manufacturing', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::COMPANY_TYPE, 'code' => 'LegalServices', 'text' => 'Legal Services', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::COMPANY_TYPE, 'code' => 'Brokers', 'text' => 'Brokers', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::COMPANY_TYPE, 'code' => 'Construction', 'text' => 'Construction', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::COMPANY_TYPE, 'code' => 'FoodBeverages', 'text' => 'Food & Beverages', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::COMPANY_TYPE, 'code' => 'ServiceProvider', 'text' => 'Service Provider', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        if (! DB::table('lookups')->where('key', LookupsEnum::PROFESSIONAL_TITLE)->first()) {
            DB::table('lookups')->insert([
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'accountant', 'text' => 'Accountant', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'actor-actress', 'text' => 'Actor / Actress', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'air-traffic-controller', 'text' => 'Air Traffic Controller', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'architect', 'text' => 'Architect', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'artist', 'text' => 'Artist', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'auditor', 'text' => 'Auditor', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'businessman', 'text' => 'Businessman', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'businesswoman', 'text' => 'Businesswoman', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'carpenter', 'text' => 'Carpenter', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'cashier', 'text' => 'Cashier', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'designer', 'text' => 'Designer', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'doctor', 'text' => 'Doctor', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'editor', 'text' => 'Editor', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'educator-teacher', 'text' => 'Educator / Teacher', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'electrician', 'text' => 'Electrician', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'engineer', 'text' => 'Engineer', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'entertainer', 'text' => 'Entertainer', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'farmer', 'text' => 'Farmer', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'host-babysitter-day-care-worker', 'text' => 'Host Babysitter / Day Care Worker', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'house-wife', 'text' => 'House Wife', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'hr-manager', 'text' => 'HR Manager', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'lawyer', 'text' => 'Lawyer', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'legal-secretary', 'text' => 'Legal Secretary', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'marketing-assistant-manager', 'text' => 'Marketing Assistant / Manager', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'marketing-coordinator', 'text' => 'Marketing Coordinator', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'military-service-person', 'text' => 'Military Service Person', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'musician', 'text' => 'Musician', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'nail-technician-perfumer', 'text' => 'Nail Technician / Perfumer', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'nanny-maid', 'text' => 'Nanny / Child-Care Provider / Caregiver Maid / Domestic Worker', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'nurse', 'text' => 'Nurse', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'medical-assistant', 'text' => 'Medical Assistant', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'nutritionist', 'text' => 'Nutritionist', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'painter', 'text' => 'Painter', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'pharmacist', 'text' => 'Pharmacist', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'pharmacy-assistant', 'text' => 'Pharmacy Assistant', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'photographer', 'text' => 'Photographer', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'physician', 'text' => 'Physician', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'pilot', 'text' => 'Pilot', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'plumber', 'text' => 'Plumber', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'police-officer', 'text' => 'Police Officer', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'politician', 'text' => 'Politician', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'production-manager', 'text' => 'Production Manager', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'professor', 'text' => 'Professor', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'programmer', 'text' => 'Programmer', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'promotions-manager', 'text' => 'Promotions Manager', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'psychologist', 'text' => 'Psychologist', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'receptionist', 'text' => 'Receptionist', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'reporter,-writer', 'text' => 'Reporter, Writer', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'safety-officer', 'text' => 'Safety Officer', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'sales-representative', 'text' => 'Sales Representative', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'secretary', 'text' => 'Secretary', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'student', 'text' => 'Student', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'teacher', 'text' => 'Teacher', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'therapist', 'text' => 'Therapist', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'tour-guide', 'text' => 'Tour Guide', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
        Lookup::firstOrCreate(['key' => LookupsEnum::PROFESSIONAL_TITLE, 'code' => 'pro-unspecified'], ['text' => 'Unspecified']);

        if (! DB::table('lookups')->where('key', LookupsEnum::MEMBER_RELATION)->first()) {
            DB::table('lookups')->insert([
                ['key' => LookupsEnum::MEMBER_RELATION, 'code' => 'relHusband', 'text' => 'Husband', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::MEMBER_RELATION, 'code' => 'relWife', 'text' => 'Wife', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::MEMBER_RELATION, 'code' => 'relMother', 'text' => 'Mother', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::MEMBER_RELATION, 'code' => 'relFather', 'text' => 'Father', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::MEMBER_RELATION, 'code' => 'reSon', 'text' => 'Son', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::MEMBER_RELATION, 'code' => 'relDaughter', 'text' => 'Daughter', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::MEMBER_RELATION, 'code' => 'relBrother', 'text' => 'Brother', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::MEMBER_RELATION, 'code' => 'relSister', 'text' => 'Sister', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::MEMBER_RELATION, 'code' => 'relFriend', 'text' => 'Friend', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::MEMBER_RELATION, 'code' => 'relBusinessPartner', 'text' => 'Business Partner', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        if (! DB::table('lookups')->where('key', LookupsEnum::UBO_RELATION)->first()) {
            DB::table('lookups')->insert([
                ['key' => LookupsEnum::UBO_RELATION, 'code' => 'relOwner', 'text' => 'Owner', 'created_at' => now(), 'updated_at' => now()],
                ['key' => LookupsEnum::UBO_RELATION, 'code' => 'relPartner', 'text' => 'Partner', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        if (Lookup::where('key', LookupsEnum::MODE_OF_DELIVERY)->count() == 3) {
            Lookup::where('key', LookupsEnum::MODE_OF_DELIVERY)->update(['key' => LookupsEnum::DELETED_MODE_OF_DELIVERY]);
        }

        Lookup::updateOrCreate(['key' => LookupsEnum::MODE_OF_DELIVERY, 'code' => 'mod-delivery-car'], ['text' => 'Company\'s Authorised Representative']);
        Lookup::updateOrCreate(['key' => LookupsEnum::MODE_OF_DELIVERY, 'code' => 'mod-delivery-atp'], ['text' => 'Authorised Third party']);
        Lookup::updateOrCreate(['key' => LookupsEnum::MODE_OF_DELIVERY, 'code' => 'mod-delivery-unkown'], ['text' => 'Unknown']);
        Lookup::updateOrCreate(['key' => LookupsEnum::MODE_OF_DELIVERY, 'code' => 'mod-delivery-pse'], ['text' => 'Policy sent via email']);
        Lookup::updateOrCreate(['key' => LookupsEnum::MODE_OF_DELIVERY, 'code' => 'mod-delivery-psc'], ['text' => 'Policy sent via courier']);
        Lookup::updateOrCreate(['key' => LookupsEnum::MODE_OF_DELIVERY, 'code' => 'mod-delivery-cco'], ['text' => 'Collected by customer from office']);
    }
}
