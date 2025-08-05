<?php

namespace Database\Seeders;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypeId;
use App\Models\LeadSource;
use App\Models\Rule;
use App\Models\RuleDetail;
use App\Models\RuleLeadSource;
use Illuminate\Database\Seeder;
use App\Services\Logger\LoggerService;

class CPARulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create CPA Australia - CAR rule
        $leadSourceForCar = $this->createLeadSource(QuoteTypeId::Car);
        $ruleforCPA = $this->createRule('CPA Australia - CAR Insurance', QuoteTypeId::Car, $leadSourceForCar);

        // Create CPA Australia - Home Insurance rule
        $leadSourceForHome = $this->createLeadSource(QuoteTypeId::Home);
        $ruleforCPA = $this->createRule('CPA Australia - Home Insurance', QuoteTypeId::Home, $leadSourceForHome);

        // Create CPA Australia - Life Insurance rule
        $leadSourceForLife = $this->createLeadSource(QuoteTypeId::Life);
        $ruleforCPA = $this->createRule('CPA Australia - Life Insurance', QuoteTypeId::Life, $leadSourceForLife);

        // Create CPA Australia - Health Insurance rule
        $leadSourceForHealth = $this->createLeadSource(QuoteTypeId::Health);
        $ruleforCPA = $this->createRule('CPA Australia - Health Insurance', QuoteTypeId::Health, $leadSourceForHealth);

        // Create CPA Australia - Travel Insurance rule
        $leadSourceForTravel = $this->createLeadSource(QuoteTypeId::Travel);
        $ruleforCPA = $this->createRule('CPA Australia - Travel Insurance', QuoteTypeId::Travel, $leadSourceForTravel);

        // Create CPA Australia - Pet Insurance rule
        $leadSourceForPet = $this->createLeadSource(QuoteTypeId::Pet);
        $ruleforCPA = $this->createRule('CPA Australia - Pet Insurance', QuoteTypeId::Pet, $leadSourceForPet);

        // Create CPA Australia - Business Insurance rule
        $leadSourceForBusiness = $this->createLeadSource(QuoteTypeId::Business);
        $ruleforCPA = $this->createRule('CPA Australia - Business Insurance', QuoteTypeId::Business, $leadSourceForBusiness);

        // Create CPA Australia - Cycle Insurance rule
        $leadSourceForCycle = $this->createLeadSource(QuoteTypeId::Cycle);
        $ruleforCPA = $this->createRule('CPA Australia - Cycle Insurance', QuoteTypeId::Cycle, $leadSourceForCycle);

        // Create CPA Australia - Bike Insurance rule
         $leadSourceForBike = $this->createLeadSource(QuoteTypeId::Bike);
        $ruleforCPA = $this->createRule('CPA Australia - Bike Insurance', QuoteTypeId::Bike, $leadSourceForBike);

        // Create CPA Australia - Yacht Insurance rule
        $leadSourceForYacht = $this->createLeadSource(QuoteTypeId::Yacht);
        $ruleforCPA = $this->createRule('CPA Australia - Yacht Insurance', QuoteTypeId::Yacht, $leadSourceForYacht);



    }

    private function createLeadSource($quoteTypeId)
    {
        try{

        $name = '';
        switch ($quoteTypeId) {
            case QuoteTypeId::Car:
                $name = 'https://im-cpa-australia.insurancemarket.ae/car-insurance/get-quote/';
                break;
            case QuoteTypeId::Home:
                $name = 'https://im-cpa-australia.insurancemarket.ae/home-insurance/get-quote/';
                break;
            case QuoteTypeId::Health:
                $name = 'https://im-cpa-australia.insurancemarket.ae/health-insurance/';
                break;
            case QuoteTypeId::Life:
                $name = 'https://im-cpa-australia.insurancemarket.ae/life-insurance/get-quote/';
                break;
            case QuoteTypeId::Travel:
                $name = 'https://im-cpa-australia.insurancemarket.ae/travel-insurance/get-quote/';
                break;
            case QuoteTypeId::Pet:
                $name = 'https://im-cpa-australia.insurancemarket.ae/pet-insurance/';
                break;
            case QuoteTypeId::Business:
                $name = 'https://im-cpa-australia.insurancemarket.ae/business-insurance/';
                break;
            case QuoteTypeId::Cycle:
                $name = 'https://im-cpa-australia.insurancemarket.ae/cycle-insurance/';
                break;
            case QuoteTypeId::Bike:
                $name = 'https://im-cpa-australia.insurancemarket.ae/bike-insurance/get-quote/';
                break;
            case QuoteTypeId::Yacht:
                $name = 'https://im-cpa-australia.insurancemarket.ae/yacht-insurance/';
                break;
      
        }
        if (empty($name)) {
            return null;
        }
        $leadSource = LeadSource::where('name', $name)->first();
        if (empty($leadSource)) {
            $leadSource = LeadSource::create([
                'name' => $name,
                'is_active' => 1,
                'is_applicable_for_rules' => 1,
            ]);
            LoggerService::info("Lead source created: {$name}");
        }
        return $leadSource;
        } catch (\Throwable $th) {  
            LoggerService::error("Error creating lead source: " . $th->getMessage()." | Line: ".$th->getLine());
            return null;
        }
    }

    private function createRule($name, $quoteTypeId, $leadSource)
    {
        try {
        
        $rule = Rule::where('name', $name)->where('quote_type_id', $quoteTypeId)->first();
        if (empty($rule)) {
            // Create CPA Australia rule
            $rule = Rule::create([
                'name' => $name,
                'quote_type_id' => $quoteTypeId,
                'rule_start_date' => null,
                'rule_end_date' => null,
                'is_active' => 0,
                'rule_type' => 1,
            ]);
            LoggerService::info("Rule created: {$name} | QuoteTypeID: {$quoteTypeId}");
        }

        $ruleLeadSource = RuleLeadSource::where('rule_id', $rule->id)
                                         ->where('lead_source_id',  $leadSource->id)
                                         ->first();
        if (empty($ruleLeadSource)) {
            // Create rule lead source mapping
            RuleLeadSource::create([
                'rule_id' => $rule->id,
                'lead_source_id' => $leadSource->id,
            ]);
            LoggerService::info("Rule lead source created: {$rule->name} | QuoteTypeID: {$quoteTypeId}");
        }

        $ruleDetail = RuleDetail::where('rule_id', $rule->id)->where('lead_source_id', $leadSource->id)->first();
        if (empty($ruleDetail)) {
            // Create rule details
            RuleDetail::create([
                'rule_id' => $rule->id,
                'car_make_id' => null,
                'car_model_id' => null,
                'lead_source_id' => $leadSource->id,
            ]);
            LoggerService::info("Rule detail created: {$rule->name} | QuoteTypeID: {$quoteTypeId}");
        }
        return $rule;
        } catch (\Throwable $th) {
            LoggerService::error("Error creating rule: " . $th->getMessage()." | Line: ".$th->getLine());
            return null;
        }
    }
}
