<?php

declare(strict_types=1);

namespace App\Enums;

enum InsuranceProviderEnum: string
{
    case ADNIC = 'ADNIC';   // ABU_DHABI_NATIONAL_INSURANCE
    case ADNT = 'ADNT';
    case AIG = 'AIG';
    case ALJALIL = 'ALJALIL';
    case ALNC = 'ALNC';    // ALLIANCE_INSURANCE
    case AMJ = 'AMJ';
    case AXA = 'AXA';      // GIG_INSURANCE
    case BUP = 'BUP';
    case CIG = 'CIG';      // CIGNA_INSURANCE
    case DIC = 'DIC';      // DUBAI_INSURANCE_COMPANY
    case DNIRC = 'DNIRC';  // DUBAI_NATIONAL_INSURANCE
    case EI = 'EI';        // EMIRATES_INSURANCE
    case FID = 'FID';
    case IHC = 'IHC';
    case MTL = 'MTL';      // METLIFE_INSURANCE
    case NA = 'NA';
    case NGI = 'NGI';      // NATIONAL_GENERAL_INSURANCE
    case NIA = 'NIA';
    case NT = 'NT';        // WATANIA_TAKAFUL
    case OI = 'OI';
    case OI2 = 'OI2';      // ORIENT_INSURANCE
    case OIC = 'OIC';      // SUKOON_OMAN_INSURANCE
    case OTHER = 'Other';
    case PAK = 'Pak';
    case QIC = 'QIC';      // QATAR_INSURANCE
    case RAK = 'RAK';      // RAK_INSURANCE
    case RSA = 'RSA';      // LIVANA_INSURANCE
    case SI = 'SI';        // SALAMA_INSURANCE
    case TE = 'TE';        // TAKAFUL_EMARAT_INSURANCE
    case TM = 'TM';        // TOKIO_MARINE
    case UI = 'UI';
    case OUNB = 'OUNB';
    case ASCANA = 'ASCANA';
    case MOPT = 'MOPT';
    case NTPJSC = 'NTPJSC';
    case ASI = 'ASI';
    case MDG = 'MDG';
    case SAICO = 'SAICO';
    case NLGIC = 'NLGIC';
    case AFNIC = 'AFNIC';
    case FPIL = 'FPIL';
    case ZILL = 'ZILL';
    case STF = 'STF';
    case AIAW = 'AIAW';
    case ARAB = 'ARAB';
    case AAIC = 'AAIC';
    case OALLIANZ = 'OALLIANZ';
    case NHICD = 'NHICD';


    
    public static function getProviderCode(string $code): ?string
    {
        $provider = self::tryFrom($code);
        return $provider?->value;
    }
    
    public function isEligibleForAccuracyMatrix(): bool
    {
        return in_array($this, [
            self::AXA,  // GIG Insurance
            self::OIC,  // Sukoon Oman Insurance
            self::TE,   // Takaful Emarat Insurance
            self::OI2,  // Orient Insurance
            self::NGI,  // National General Insurance
            self::MTL,  // MetLife Insurance
            self::DNIRC, // Dubai National Insurance
            self::DIC,  // Dubai Insurance Company
            self::CIG,  // Cigna Insurance
            self::SI,   // Salama Insurance
        ]);
    }
}