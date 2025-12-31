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

    public const AWNI = 'AWNI';
    public const ASNIC = 'ASNIC';
    public const AHAC = 'AHAC';
    public const ABNIC = 'ABNIC';
    public const DATPJSC = 'DATPJSC';
    public const DICPSC = 'DICPSC';
    public const DICORI = 'DICORI';
    public const AMAN = 'AMAN';
    public const MAXMED = 'MAXMED';
    public const NLAGICSAOC = 'NLAGICSAOC';
    public const NTCWATANIA = 'NTCWATANIA';
    public const NIADB = 'NIADB';
    public const NTFPJSC = 'NTFPJSC';
    public const EECIC = 'EECIC';
    public const VIV = 'VIV';
    public const NOW = 'NOW';
    public const HYH = 'HYH';
    public const YAS = 'YAS';
    public const ISON = 'ISON';
    public const MAXHEALTH = 'MAXHEALTH';
    public const ORITAK = 'ORITAK';
    public const APR_HYH = 'APR_HYH';
    public const MTI = 'MTI';

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

    public static function asArray(): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            $result[$case->name] = $case->value;
        }

        return $result;
    }

    public static function getProviderCodeFromConstantName(string $constant): ?string
    {
        return match ($constant) {
            'GIG_INSURANCE' => self::AXA->value,
            'QATAR_INSURANCE' => self::QIC->value,
            'RAK_INSURANCE' => self::RAK->value,
            'TOKIO_MARINE' => self::TM->value,
            'ALLIANCE_INSURANCE' => self::ALNC->value,
            'ABU_DHABI_NATIONAL_INSURANCE' => self::ADNIC->value,
            'SUKOON_OMAN_INSURANCE' => self::OIC->value,
            'WATANIA_TAKAFUL' => self::NT->value,
            'ORIENT_INSURANCE' => self::OI2->value,
            'LIVANA_INSURANCE' => self::RSA->value,
            'EMIRATES_INSURANCE' => self::EI->value,
            'TAKAFUL_EMARAT_INSURANCE' => self::TE->value,
            'NATIONAL_GENERAL_INSURANCE' => self::NGI->value,
            'METLIFE_INSURANCE' => self::MTL->value,
            'DUBAI_NATIONAL_INSURANCE' => self::DNIRC->value,
            'DUBAI_INSURANCE_COMPANY' => self::DIC->value,
            'CIGNA_INSURANCE' => self::CIG->value,
            'SALAMA_INSURANCE' => self::SI->value,
            default => null,
        };
    }

    public static function getTextByCode($value)
    {
        return match ($value) {
            self::RSA => 'Liva',
            self::AXA => 'GIG',
            self::OIC => 'Sukoon',
            default => 'GIG',
        };
    }
}
