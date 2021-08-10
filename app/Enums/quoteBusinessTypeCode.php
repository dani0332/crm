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
    const several = "I need several insurances for my business";
    const office = "Office Insurance Package";
    const property = "Property";
    const publicLiability = "Public Liability (Premises, Third Party, Products and/or Pollution)";
    const groupMedical = "Group Medical";
    const groupLife = "Group Life";
    const groupTravel = "Group Travel";
    const proIndemnity = "Professional Indemnity";
    const carFleet = "Car Fleet (or Multiple Car Discount Scheme)";
    const marineCargo = "Marine Cargo";
    const marineHull = "Marine Hull (Yacht, Boat or Vessel)";
    const businessInterruption = "Business Interruption or Consequential Loss";
    const machineryBreakdown = "Machinery Breakdown";
    const erection = "Erection All Risks";
    const tradeCredit = "Trade Credit Insurance";
    const jewellersBlock = "Jewellers Block";
    const medicalMalpractices = "Medical Malpractices";
    const kidnapRansom = "Kidnap & Ransom";
    const directorsOfficers = "Directors & Officers Liability";
    const defenceBased = "Defence Based Act (DBA)";
    const extendedWarranties = "Extended Warranties";
    const drone = "Drone Insurance";
    const bancassurance = "Bancassurance";
    const cyber = "Cyber Insurance";
    const workmens = "Workmens Compensation & Employers Liability";
    const photographers = "Photographers Insurance";
    const event = "Event Insurance";
}
