<?php

namespace Database\Seeders;

use App\Enums\InsuranceProvidersEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\InsuranceProvider;
use App\Models\QuoteType;
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

        if( Schema::hasTable('insurer_quote_type_mapping') ){

            InsuranceProvider::create([
                ['code' => 'OUNB', 'text' => 'Orient UNB', 'text_lms' => 'Orient UNB'],
                ['code' => 'ASCANA', 'text' => 'ASCANA Takaful', 'text_lms' => 'ASCANA Takaful'],
                ['code' => 'EI', 'text' => 'Emirates Insurance', 'text_lms' => 'Emirates Insurance'],
                ['code' => 'MOPT', 'text' => 'Moopet', 'text_lms' => 'Moopet'],
                ['code' => 'NTPJSC', 'text' => 'Noor Takaful General PJSC', 'text_lms' => 'Noor Takaful General PJSC'],
                ['code' => 'ASI', 'text' => 'Al Sagr Insurance', 'text_lms' => 'Al Sagr Insurance'],
                ['code' => 'MDG', 'text' => 'Medgulf', 'text_lms' => 'Medgulf'],
                ['code' => 'MTL', 'text' => 'Metlife', 'text_lms' => 'Metlife'],
                ['code' => 'SAICO', 'text' => 'Saico', 'text_lms' => 'Saico'],
                ['code' => 'NLGIC', 'text' => 'NLGIC', 'text_lms' => 'NLGIC'],
            ]);

            $insurenceProviders = InsuranceProvider::get();
            $quoteTypes = QuoteType::get();

            foreach ($insurenceProviders as $insurenceProvider){

                foreach ($quoteTypes as $quoteType) {

                    // AIG-AMERICAN INTERNATIONAL GROUP INC
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::AIG):
                        if(in_array($quoteType['id'], [QuoteTypeId::Business, QuoteTypeId::Travel]))
                            $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // Royal & Sun Alliance Insurance (RSA)
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::RSA):
                        if(in_array($quoteType['id'], [QuoteTypeId::Business, QuoteTypeId::Home, QuoteTypeId::Car, QuoteTypeId::Bike, QuoteTypeId::Travel]))
                            $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // Tokio Marine & Nichido Fire Insurance Co
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::TM):
                        if(in_array($quoteType['id'], [QuoteTypeId::Business, QuoteTypeId::Home, QuoteTypeId::Car]))
                            $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // Fidelity United
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::FID):
                        if(in_array($quoteType['id'], [QuoteTypeId::Business, QuoteTypeId::Pet, QuoteTypeId::Car, QuoteTypeId::Health]))
                            $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // Orient Insurance
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::OI2):
                        if(in_array($quoteType['id'], [QuoteTypeId::Business, QuoteTypeId::Car, QuoteTypeId::Bike, QuoteTypeId::Travel, QuoteTypeId::Health]))
                            $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // Watania Takaful
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::NT):
                        if(in_array($quoteType['id'], [QuoteTypeId::Business, QuoteTypeId::Home, QuoteTypeId::Car, QuoteTypeId::Travel]))
                            $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // Sukoon (Oman Insurance)
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::OIC):
                        if(in_array($quoteType['id'], [QuoteTypeId::Business, QuoteTypeId::Home, QuoteTypeId::Car, QuoteTypeId::Bike, QuoteTypeId::Travel, QuoteTypeId::Health]))
                            $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // AL WHATBA
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::Al_Jalil):
                        if(in_array($quoteType['id'], [QuoteTypeId::Business, QuoteTypeId::Travel]))
                            $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // Abu Dhabi National Takaful
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::ADNT):
                        if(in_array($quoteType['id'], [QuoteTypeId::Business, QuoteTypeId::Car, QuoteTypeId::Health]))
                            $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // GIG Gulf (AXA)
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::AXA):
                        if(in_array($quoteType['id'], [QuoteTypeId::Business, QuoteTypeId::Home, QuoteTypeId::Car, QuoteTypeId::Bike, QuoteTypeId::Travel, QuoteTypeId::Health]))
                            $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // Insurance House
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::IHC):
                        if(in_array($quoteType['id'], [QuoteTypeId::Business, QuoteTypeId::Car]))
                            $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // Dubai Insurance
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::DIC):
                        if(in_array($quoteType['id'], [QuoteTypeId::Business, QuoteTypeId::Home, QuoteTypeId::Health]))
                            $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // Dubai National Insurance
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::DNIRC):
                        if(in_array($quoteType['id'], [QuoteTypeId::Business, QuoteTypeId::Home, QuoteTypeId::Cycle, QuoteTypeId::Car]))
                            $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // New India Assurance
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::NIA):
                        if(in_array($quoteType['id'], [QuoteTypeId::Business, QuoteTypeId::Car]))
                            $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // National General Insurance
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::NGI):
                        if(in_array($quoteType['id'], [QuoteTypeId::Business, QuoteTypeId::Car]))
                            $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // Qatar Insurance Company
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::QIC):
                        if(in_array($quoteType['id'], [QuoteTypeId::Business, QuoteTypeId::Home, QuoteTypeId::Car, QuoteTypeId::Bike]))
                            $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // Alliance Insurance
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::ALNC):
                        if(in_array($quoteType['id'], [QuoteTypeId::Business, QuoteTypeId::Home, QuoteTypeId::Travel]))
                            $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // RAK Insurance
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::RAK):
                        if(in_array($quoteType['id'], [QuoteTypeId::Business]))
                            $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // Salama Insurance
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::SI):
                        if(in_array($quoteType['id'], [QuoteTypeId::Business, QuoteTypeId::Home, QuoteTypeId::Pet, QuoteTypeId::Car]))
                            $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // Union Insurance
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::UI):
                        if(in_array($quoteType['id'], [QuoteTypeId::Business, QuoteTypeId::Car]))
                            $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // Oriental Insurance
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::OI):
                        if(in_array($quoteType['id'], [QuoteTypeId::Car, QuoteTypeId::Bike]))
                            $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // Takaful Emarat Insurance
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::TE):
                        if(in_array($quoteType['id'], [QuoteTypeId::Health]))
                            $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // Cigna Insurance
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::CIG):
                        if(in_array($quoteType['id'], [QuoteTypeId::Health]))
                            $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // BUPA
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::BUP):
                        if(in_array($quoteType['id'], [QuoteTypeId::Health]))
                            $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // Orient UNB
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::OUNB):
                        if(in_array($quoteType['id'], [QuoteTypeId::Business]))
                            $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // ASCANA Takaful
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::ASCANA):
                        if(in_array($quoteType['id'], [QuoteTypeId::Business, QuoteTypeId::Car]))
                        $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // Emirates Insurance
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::EI):
                        if(in_array($quoteType['id'], [QuoteTypeId::Business, QuoteTypeId::Home, QuoteTypeId::Car]))
                        $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // Moopet
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::MOPT):
                        if(in_array($quoteType['id'], [QuoteTypeId::Pet]))
                        $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // Noor Takaful General PJSC
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::NTPJSC):
                        if(in_array($quoteType['id'], []))
                        $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // Al Sagr Insurance
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::ASI):
                        if(in_array($quoteType['id'], [QuoteTypeId::Business, QuoteTypeId::Home, QuoteTypeId::Car, QuoteTypeId::Bike, QuoteTypeId::Travel, QuoteTypeId::Health]))
                        $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // Medgulf
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::MDG):
                        if(in_array($quoteType['id'], []))
                            $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // Metlife
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::MTL):
                        if(in_array($quoteType['id'], []))
                            $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // Saico
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::SAICO):
                        if(in_array($quoteType['id'], []))
                            $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                    // NLGIC
                    if ($insurenceProvider['code'] == InsuranceProvidersEnum::NLGIC):
                        if(in_array($quoteType['id'], []))
                            $this->insertMappingRecords($quoteType['id'], $insurenceProvider['id']);
                    endif;

                }
            }
        }
    }

    protected function insertMappingRecords($quoteTypeId, $insuranceProviderId)
    {
        \DB::table('insurer_quote_type_mapping')->insert(['quote_type_id' => $quoteTypeId, 'insurance_provider_id' => $insuranceProviderId ]);
    }
}
