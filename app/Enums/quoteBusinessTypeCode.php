<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class quoteBusinessTypeCode extends Enum
{
    const several = 'I need several insurances for my business';
    const office = 'Office Insurance Package';
    const property = 'Property';
    const publicLiability = 'Public Liability (Premises, Third Party, Products and/or Pollution)';
    const groupMedical = 'Group Medical';
    const groupLife = 'Group Life';
    const groupTravel = 'Group Travel';
    const proIndemnity = 'Professional Indemnity';
    const carFleet = 'Car Fleet (or Multiple Car Discount Scheme)';
    const marineCargoIndividual = 'Marine Cargo (individual shipment) insurance';
    const marineHull = 'Marine Hull (Yacht, Boat or Vessel)';
    const marineCargoOpenCover = 'Marine Cargo - Open Cover';
    const businessInterruption = 'Business Interruption or Consequential Loss';
    const machineryBreakdown = 'Machinery Breakdown';
    const erection = 'Erection All Risks';
    const tradeCredit = 'Trade Credit Insurance';
    const jewellersBlock = 'Jewellers Block';
    const medicalMalpractices = 'Medical Malpractices';
    const kidnapRansom = 'Kidnap & Ransom';
    const directorsOfficers = 'Directors & Officers Liability';
    const defenceBased = 'Defence Based Act (DBA)';
    const extendedWarranties = 'Extended Warranties';
    const drone = 'Drone Insurance';
    const bancassurance = 'Bancassurance';
    const cyber = 'Cyber Insurance';
    const workmens = 'Workmens Compensation & Employers Liability';
    const photographers = 'Photographers Insurance';
    const event = 'Event Insurance';
    const contractorsRisk = 'Contractors All Risks';
    const holidayHomes = 'Holiday Homes';
    const liveStock = 'Livestock Insurance';
    const moneyInsurance = 'Money Insurance';
    const smeInsurance = 'SME Insurance';
    const fidelityGuarantee = 'Fidelity Guarantee';
    const goodsInTransit = 'Goods In Transit';
    const MedicalMalpracticeInsurance = 'Medical Malpractice Insurance';
    const marineCargo = 'Marine Cargo';

    public static function getId($value): int
    {
        return match ($value) {
            quoteBusinessTypeCode::several => 1,
            quoteBusinessTypeCode::office => 2,
            quoteBusinessTypeCode::property => 3,
            quoteBusinessTypeCode::publicLiability => 4,
            quoteBusinessTypeCode::groupMedical => 5,
            quoteBusinessTypeCode::groupLife => 6,
            quoteBusinessTypeCode::groupTravel => 7,
            quoteBusinessTypeCode::proIndemnity => 8,
            quoteBusinessTypeCode::carFleet => 9,
            quoteBusinessTypeCode::marineCargo => 10,
            quoteBusinessTypeCode::marineCargoIndividual => 10,
            quoteBusinessTypeCode::marineHull => 11,
            quoteBusinessTypeCode::businessInterruption => 12,
            quoteBusinessTypeCode::machineryBreakdown => 13,
            quoteBusinessTypeCode::contractorsRisk => 14,
            quoteBusinessTypeCode::erection => 15,
            quoteBusinessTypeCode::tradeCredit => 16,
            quoteBusinessTypeCode::jewellersBlock => 17,
            quoteBusinessTypeCode::medicalMalpractices => 18,
            quoteBusinessTypeCode::MedicalMalpracticeInsurance => 18,
            quoteBusinessTypeCode::kidnapRansom => 19,
            quoteBusinessTypeCode::directorsOfficers => 20,
            quoteBusinessTypeCode::defenceBased => 21,
            quoteBusinessTypeCode::extendedWarranties => 22,
            quoteBusinessTypeCode::drone => 23,
            quoteBusinessTypeCode::bancassurance => 24,
            quoteBusinessTypeCode::cyber => 25,
            quoteBusinessTypeCode::workmens => 26,
            quoteBusinessTypeCode::photographers => 27,
            quoteBusinessTypeCode::event => 28,
            quoteBusinessTypeCode::smeInsurance => 30,
            quoteBusinessTypeCode::moneyInsurance => 31,
            quoteBusinessTypeCode::liveStock => 32,
            quoteBusinessTypeCode::marineCargoOpenCover => 33,
            quoteBusinessTypeCode::holidayHomes => 34,
            quoteBusinessTypeCode::fidelityGuarantee => 35,
            quoteBusinessTypeCode::goodsInTransit => 36,
        };
    }
}
