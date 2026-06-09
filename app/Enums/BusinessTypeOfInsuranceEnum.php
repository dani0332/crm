<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class BusinessTypeOfInsuranceEnum extends Enum
{
    const LIVESTOCK_INSURANCE = 'Livestock Insurance';
    const MARINE_CARGO_OPEN_COVER = 'Marine Cargo - Open Cover';
    const HOLIDAY_HOME = 'Holiday Homes';
    const GOODS_IN_TRANSIT = 'Goods In Transit';
    const PROPERTY = 'Property';
    const SEVERAL_INSURANCES = 'I need several insurances for my business';
    const OFFICE_INSURANCE_PACKAGE = 'Office Insurance Package';
    const PUBLIC_LIABILITY = 'Public Liability (Premises, Third Party, Products and/or Pollution)';
    const GROUP_LIFE = 'Group Life';
    const PROFESSIONAL_INDEMNITY = 'Professional Indemnity';
    const MARINE_CARGO_INDIVIDUAL = 'Marine Cargo (individual shipment) insurance';
    const MARINE_HULL = 'Marine Hull (Yacht, Boat or Vessel)';
    const BUSINESS_INTERRUPTION = 'Business Interruption or Consequential Loss';
    const MACHINERY_BREAKDOWN = 'Machinery Breakdown';
    const CONTRACTORS_ALL_RISKS = 'Contractors All Risks';
    const ERECTION_ALL_RISKS = 'Erection All Risks';
    const JEWELLERS_BLOCK = 'Jewellers Block';
    const MEDICAL_MALPRACTICES = 'Medical Malpractices';
    const KIDNAP_AND_RANSOM = 'Kidnap & Ransom';
    const DIRECTORS_AND_OFFICERS_LIABILITY = 'Directors & Officers Liability';
    const DEFENCE_BASED_ACT = 'Defence Based Act (DBA)';
    const EXTENDED_WARRANTIES = 'Extended Warranties';
    const DRONE_INSURANCE = 'Drone Insurance';
    const CYBER_INSURANCE = 'Cyber Insurance';
    const WORKMENS_COMPENSATION = 'Workmens Compensation & Employers Liability';
    const PHOTOGRAPHERS_INSURANCE = 'Photographers Insurance';
    const EVENT_INSURANCE = 'Event Insurance';
    const SME_INSURANCE = 'SME Insurance';
    const MONEY_INSURANCE = 'Money Insurance';
    const FIDELITY_GUARANTEE = 'Fidelity Guarantee';
    const POLITICAL_VIOLENCE = 'Political Violence';
}
