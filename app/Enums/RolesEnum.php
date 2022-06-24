<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class RolesEnum extends Enum
{
    const Admin = "ADMIN";
    const CarAdvisor = "CAR_ADVISOR";
    const BusinessAdvisor = "BUSINESS_ADVISOR";
    const HealthAdvisor = "HEALTH_ADVISOR";
    const HomeAdvisor = "HOME_ADVISOR";
    const LifeAdvisor = "LIFE_ADVISOR";
    const TravelAdvisor = "TRAVEL_ADVISOR";
    const GMAdvisor = "GM_ADVISOR";
    const RMAdvisor = "RM_ADVISOR";
    const CorpLineAdvisor = "CORPLINE_ADVISOR";
    const EBPAdvisor = "EBP_ADVISOR";
    const HealthWCUAdvisor = "HEALTH_WCU_ADVISOR";
    const HealthRenewalAdvisor = "HEALTH_RENEWAL_ADVISOR";
    const HealthNewBusinessAdvisor = "HEALTH_NEW_BUSINESS_ADVISOR";
    const TravelRenewalAdvisor = "TRAVEL_RENEWAL_ADVISOR";
    const TravelNewBusinessAdvisor = "TRAVEL_NEW_BUSINESS_ADVISOR";
    const LifeRenewalAdvisor = "LIFE_RENEWAL_ADVISOR";
    const LifeNewBusinessAdvisor = "LIFE_NEW_BUSINESS_ADVISOR";
    const HomeRenewalAdvisor = "HOME_RENEWAL_ADVISOR";
    const HomeNewBusinessAdvisor = "HOME_NEW_BUSINESS_ADVISOR";
    const GMRenewalAdvisor = "GM_RENEWAL_ADVISOR";
    const GMNewBusinessAdvisor = "GM_NEW_BUSINESS_ADVISOR";
    const CorpLineRenewalAdvisor = "CORPLINE_RENEWAL_ADVISOR";
    const CorpLineNewBusinessAdvisor = "CORPLINE_NEW_BUSINESS_ADVISOR";
    const PetRenewalAdvisor = "PET_RENEWAL_ADVISOR";
    const PetNewBusinessAdvisor = "PET_NEW_BUSINESS_ADVISOR";
    const CarRenewalAdvisor = "CAR_RENEWAL_ADVISOR";
    const CarRenewalManager = "CAR_RENEWAL_MANAGER";
    const CarNewBusinessAdvisor = "CAR_NEW_BUSINESS_ADVISOR";
    const TravelRenewalManager =  "TRAVEL_RENEWAL_MANAGER";
    const HealthRenewalManager =   "HEALTH_RENEWAL_MANAGER";
    const HomeRenewalManager =  "HOME_RENEWAL_MANAGER";
    const LifeRenewalManager =  "LIFE_RENEWAL_MANAGER";
    const GMRenewalManager =  "GM_RENEWAL_MANAGER";
    const CorpLineRenewalManager =   "CORPLINE_RENEWAL_MANAGER";
    const PetRenewalManager =   "PET_RENEWAL_MANAGER";
    const HealthNewBusinessManager = "HEALTH_NEW_BUSINESS_MANAGER";
    const TravelNewBusinessManager = "TRAVEL_NEW_BUSINESS_MANAGER";
    const HomeNewBusinessManager = "HOME_NEW_BUSINESS_MANAGER";
    const LifeNewBusinessManager = "LIFE_NEW_BUSINESS_MANAGER";
    const GMNewBusinessManager = "GM_NEW_BUSINESS_MANAGER";
    const CorpLineNewBusinessManager = "CORPLINE_NEW_BUSINESS_MANAGER";
    const PetNewBusinessManager = "PET_NEW_BUSINESS_MANAGER";
    const Advisor = "advisor";
    const PA = "pa";
    const Invoicing = "invoicing";
    const Payment = "payment";
    const ProductionApprovalManager = "production_approval_manager";
    const OE = "oe";
    const RoleSMDashboard = "ROLE_SM_DASHBOARD";
    const HealthDeputyManager = "HEALTH_DEPUTY_MANAGER";
    const HealthManager = "HEALTH_MANAGER";
}
