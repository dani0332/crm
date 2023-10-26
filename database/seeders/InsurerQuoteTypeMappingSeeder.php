<?php

namespace Database\Seeders;

use App\Enums\InsuranceProvidersEnum;
use App\Enums\QuoteTypeId;
use App\Models\InsuranceProvider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class InsurerQuoteTypeMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        if (Schema::hasTable('insurance_provider_quote_type')) {

            InsuranceProvider::updateOrCreate(['code' => 'OUNB'], ['text' => 'Orient UNB', 'text_lms' => 'Orient UNB']);
            InsuranceProvider::updateOrCreate(['code' => 'ASCANA'], ['text' => 'ASCANA Takaful', 'text_lms' => 'ASCANA Takaful']);
            InsuranceProvider::updateOrCreate(['code' => 'EI'], ['text' => 'Emirates Insurance', 'text_lms' => 'Emirates Insurance']);
            InsuranceProvider::updateOrCreate(['code' => 'MOPT'], ['text' => 'Moopet', 'text_lms' => 'Moopet']);
            InsuranceProvider::updateOrCreate(['code' => 'NTPJSC'], ['text' => 'Noor Takaful General PJSC', 'text_lms' => 'Noor Takaful General PJSC']);
            InsuranceProvider::updateOrCreate(['code' => 'ASI'], ['text' => 'Al Sagr Insurance', 'text_lms' => 'Al Sagr Insurance']);
            InsuranceProvider::updateOrCreate(['code' => 'MDG'], ['text' => 'Medgulf', 'text_lms' => 'Medgulf']);
            InsuranceProvider::updateOrCreate(['code' => 'MTL'], ['text' => 'Metlife', 'text_lms' => 'Metlife']);
            InsuranceProvider::updateOrCreate(['code' => 'SAICO'], ['text' => 'Saico', 'text_lms' => 'Saico']);
            InsuranceProvider::updateOrCreate(['code' => 'NLGIC'], ['text' => 'NLGIC', 'text_lms' => 'NLGIC']);
            InsuranceProvider::updateOrCreate(['code' => 'AFNIC'], ['text' => 'Al Fujairah National Insurance Company', 'text_lms' => 'Al Fujairah National Insurance Company']);
            InsuranceProvider::updateOrCreate(['code' => 'ALJALIL'], ['text' => 'Al Wathba National Insurance Company', 'text_lms' => 'Al Wathba National Insurance Company']);
            InsuranceProvider::updateOrCreate(['code' => 'AIG'], ['text' => 'AIG-AMERICAN INTERNATIONAL GROUP INC', 'text_lms' => 'AIG-AMERICAN INTERNATIONAL GROUP INC']);
            $insurenceProviders = InsuranceProvider::get();
            $quoteTypes = QuoteTypeId::getOptions();

            foreach ($insurenceProviders as $insurenceProvider) {
                foreach ($quoteTypes as $quoteKey => $quoteType) {

                    // AIG-AMERICAN INTERNATIONAL GROUP INC
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::AIG) {
                        if (in_array($quoteKey, [QuoteTypeId::Corpline, QuoteTypeId::Travel])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // Royal & Sun Alliance Insurance (RSA)
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::RSA) {
                        if (in_array($quoteKey, [QuoteTypeId::Corpline, QuoteTypeId::Home, QuoteTypeId::Car, QuoteTypeId::Bike, QuoteTypeId::Travel])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // Tokio Marine & Nichido Fire Insurance Co
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::TM) {
                        if (in_array($quoteKey, [QuoteTypeId::Corpline, QuoteTypeId::Home, QuoteTypeId::Car])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // Fidelity United
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::FID) {
                        if (in_array($quoteKey, [QuoteTypeId::Corpline, QuoteTypeId::Pet, QuoteTypeId::Car, QuoteTypeId::Health, QuoteTypeId::GroupMedical])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // Orient Insurance
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::OI2) {
                        if (in_array($quoteKey, [QuoteTypeId::Corpline, QuoteTypeId::Car, QuoteTypeId::Bike, QuoteTypeId::Travel, QuoteTypeId::Health, QuoteTypeId::GroupMedical])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // Watania Takaful
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::NT) {
                        if (in_array($quoteKey, [QuoteTypeId::Corpline, QuoteTypeId::Home, QuoteTypeId::Car, QuoteTypeId::Travel, QuoteTypeId::Health, QuoteTypeId::GroupMedical])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // Sukoon (Oman Insurance)
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::OIC) {
                        if (in_array($quoteKey, [QuoteTypeId::Corpline, QuoteTypeId::Home, QuoteTypeId::Car, QuoteTypeId::Bike, QuoteTypeId::Travel, QuoteTypeId::Health, QuoteTypeId::GroupMedical])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // AL WHATBA
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::ALJALIL) {
                        if (in_array($quoteKey, [QuoteTypeId::Corpline, QuoteTypeId::Travel])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // Abu Dhabi National Takaful
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::ADNT) {
                        if (in_array($quoteKey, [QuoteTypeId::Corpline, QuoteTypeId::Car, QuoteTypeId::Health, QuoteTypeId::GroupMedical])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // GIG Gulf (AXA)
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::AXA) {
                        if (in_array($quoteKey, [QuoteTypeId::Corpline, QuoteTypeId::Home, QuoteTypeId::Car, QuoteTypeId::Bike, QuoteTypeId::Travel, QuoteTypeId::Health, QuoteTypeId::GroupMedical])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // Insurance House
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::IHC) {
                        if (in_array($quoteKey, [QuoteTypeId::Corpline, QuoteTypeId::Car])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // Dubai Insurance
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::DIC) {
                        if (in_array($quoteKey, [QuoteTypeId::Corpline, QuoteTypeId::Home, QuoteTypeId::Health, QuoteTypeId::GroupMedical])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // Dubai National Insurance
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::DNIRC) {
                        if (in_array($quoteKey, [QuoteTypeId::Corpline, QuoteTypeId::Home, QuoteTypeId::Cycle, QuoteTypeId::Bike, QuoteTypeId::Car, QuoteTypeId::GroupMedical])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // New India Assurance
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::NIA) {
                        if (in_array($quoteKey, [QuoteTypeId::Corpline, QuoteTypeId::Car])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // National General Insurance
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::NGI) {
                        if (in_array($quoteKey, [QuoteTypeId::Corpline, QuoteTypeId::Car, QuoteTypeId::GroupMedical])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // Qatar Insurance Company
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::QIC) {
                        if (in_array($quoteKey, [QuoteTypeId::Corpline, QuoteTypeId::Home, QuoteTypeId::Car, QuoteTypeId::Bike])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // Alliance Insurance
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::ALNC) {
                        if (in_array($quoteKey, [QuoteTypeId::Corpline, QuoteTypeId::Home, QuoteTypeId::Travel, QuoteTypeId::GroupMedical])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // RAK Insurance
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::RAK) {
                        if (in_array($quoteKey, [QuoteTypeId::Corpline, QuoteTypeId::GroupMedical])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // Salama Insurance
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::SI) {
                        if (in_array($quoteKey, [QuoteTypeId::Corpline, QuoteTypeId::Home, QuoteTypeId::Pet, QuoteTypeId::Car, QuoteTypeId::GroupMedical])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // Union Insurance
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::UI) {
                        if (in_array($quoteKey, [QuoteTypeId::Corpline, QuoteTypeId::Car])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // Oriental Insurance
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::OI) {
                        if (in_array($quoteKey, [QuoteTypeId::Corpline, QuoteTypeId::Car, QuoteTypeId::Bike, QuoteTypeId::GroupMedical])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // Takaful Emarat Insurance
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::TE) {
                        if (in_array($quoteKey, [QuoteTypeId::Health, QuoteTypeId::GroupMedical])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // Cigna Insurance
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::CIG) {
                        if (in_array($quoteKey, [QuoteTypeId::Health, QuoteTypeId::GroupMedical])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // BUPA
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::BUP) {
                        if (in_array($quoteKey, [QuoteTypeId::Health, QuoteTypeId::GroupMedical])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // Orient UNB
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::OUNB) {
                        if (in_array($quoteKey, [QuoteTypeId::Corpline, QuoteTypeId::GroupMedical])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // ASCANA Takaful
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::ASCANA) {
                        if (in_array($quoteKey, [QuoteTypeId::Corpline, QuoteTypeId::Car])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // Emirates Insurance
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::EI) {
                        if (in_array($quoteKey, [QuoteTypeId::Corpline, QuoteTypeId::Home, QuoteTypeId::Car, QuoteTypeId::GroupMedical])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // Moopet
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::MOPT) {
                        if (in_array($quoteKey, [QuoteTypeId::Pet])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // Noor Takaful General PJSC
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::NTPJSC) {
                        if (in_array($quoteKey, [])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // Al Sagr Insurance
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::ASI) {
                        if (in_array($quoteKey, [QuoteTypeId::Corpline, QuoteTypeId::Home, QuoteTypeId::Car, QuoteTypeId::Bike, QuoteTypeId::Travel, QuoteTypeId::Health, QuoteTypeId::GroupMedical])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // Medgulf
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::MDG) {
                        if (in_array($quoteKey, [QuoteTypeId::GroupMedical])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // Metlife
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::MTL) {
                        if (in_array($quoteKey, [QuoteTypeId::GroupMedical])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // Saico
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::SAICO) {
                        if (in_array($quoteKey, [QuoteTypeId::GroupMedical])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }

                    // NLGIC
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::NLGIC) {
                        if (in_array($quoteKey, [QuoteTypeId::GroupMedical])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }
                    // AFNIC  Al Fujairah National Insurance Company
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::AFNIC) {
                        if (in_array($quoteKey, [QuoteTypeId::Corpline])) {
                            $this->insertMappingRecords($quoteKey, $insurenceProvider['id']);
                        }
                    }
                }
            }
        }
    }

    protected function insertMappingRecords($quoteTypeId, $insuranceProviderId)
    {
        $getMappedValue = \DB::table('insurance_provider_quote_type')->where(['quote_type_id' => $quoteTypeId, 'insurance_provider_id' => $insuranceProviderId])->count();
        if (! $getMappedValue) {
            \DB::table('insurance_provider_quote_type')->insert(['quote_type_id' => $quoteTypeId, 'insurance_provider_id' => $insuranceProviderId]);
        }
    }
}
