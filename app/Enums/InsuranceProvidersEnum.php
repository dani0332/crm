<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class InsuranceProvidersEnum extends Enum
{
    public const ADNIC = 'ADNIC';
    public const ADNT = 'ADNT';
    public const AIG = 'AIG';
    public const ALJALIL = 'ALJALIL';
    public const ALNC = 'ALNC';
    public const AMJ = 'AMJ';
    public const AXA = 'AXA';
    public const BUP = 'BUP';
    public const CIG = 'CIG';
    public const DIC = 'DIC';
    public const DNIRC = 'DNIRC';
    public const FID = 'FID';
    public const IHC = 'IHC';
    public const NA = 'NA';
    public const NGI = 'NGI';
    public const NIA = 'NIA';
    public const NT = 'NT';
    public const OI = 'OI';
    public const OI2 = 'OI2';
    public const OIC = 'OIC';
    public const OTHER = 'Other';
    public const PAK = 'Pak';
    public const QIC = 'QIC';
    public const RAK = 'RAK';
    public const RSA = 'RSA';
    public const SI = 'SI';
    public const TE = 'TE';
    public const TM = 'TM';
    public const UI = 'UI';
    public const OUNB = 'OUNB';
    public const ASCANA = 'ASCANA';
    public const EI = 'EI';
    public const MOPT = 'MOPT';
    public const NTPJSC = 'NTPJSC';
    public const ASI = 'ASI';
    public const MDG = 'MDG';
    public const MTL = 'MTL';
    public const SAICO = 'SAICO';
    public const NLGIC = 'NLGIC';
    public const AFNIC = 'AFNIC';
    public const FPIL = 'FPIL';
    public const ZILL = 'ZILL';
    public const STF = 'STF';
    public const AIAW = 'AIAW';
    public const ARAB = 'ARAB';
    public const AAIC = 'AAIC';
    public const OALLIANZ = 'OALLIANZ';
    public const NHICD = 'NHICD';
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
