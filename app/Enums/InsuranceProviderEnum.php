<?php

declare(strict_types=1);

namespace App\Enums;

enum InsuranceProviderEnum: string
{
    case ADNIC = 'ADNIC';
    case ADNT = 'ADNT';
    case AIG = 'AIG';
    case ALJALIL = 'ALJALIL';
    case ALNC = 'ALNC';
    case AMJ = 'AMJ';
    case AXA = 'AXA';
    case BUP = 'BUP';
    case CIG = 'CIG';
    case DIC = 'DIC';
    case DNIRC = 'DNIRC';
    case EI = 'EI';
    case FID = 'FID';
    case IHC = 'IHC';
    case MTL = 'MTL';
    case NA = 'NA';
    case NGI = 'NGI';
    case NIA = 'NIA';
    case NT = 'NT';
    case OI = 'OI';
    case OI2 = 'OI2';
    case OIC = 'OIC';
    case OTHER = 'Other';
    case PAK = 'Pak';
    case QIC = 'QIC';
    case RAK = 'RAK';
    case RSA = 'RSA';
    case SI = 'SI';
    case TE = 'TE';
    case TM = 'TM';
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

    public function getFullName(): string
    {
        return match ($this) {
            self::AXA => 'GIG Insurance',
            self::QIC => 'Qatar Insurance',
            self::RAK => 'RAK Insurance',
            self::TM => 'Tokio Marine',
            self::ALNC => 'Alliance Insurance',
            self::ADNIC => 'Abu Dhabi National Insurance',
            self::OIC => 'Sukoon Oman Insurance',
            self::NT => 'Watania Takaful',
            self::OI2 => 'Orient Insurance',
            self::RSA => 'Livana Insurance',
            self::EI => 'Emirates Insurance',
            self::TE => 'Takaful Emarat Insurance',
            self::NGI => 'National General Insurance',
            self::MTL => 'MetLife Insurance',
            self::DNIRC => 'Dubai National Insurance',
            self::DIC => 'Dubai Insurance Company',
            self::CIG => 'Cigna Insurance',
            default => $this->value,
        };
    }

    public static function getProviderCode(string $code): ?string
    {
        return match ($code) {
            'AXA' => self::AXA,
            'QIC' => self::QIC,
            'RAK' => self::RAK,
            'TM' => self::TM,
            'ALNC' => self::ALNC,
            'ADNIC' => self::ADNIC,
            'OIC' => self::OIC,
            'NT' => self::NT,
            'OI2' => self::OI2,
            'RSA' => self::RSA,
            'EI' => self::EI,
            'TE' => self::TE,
            'NGI' => self::NGI,
            'MTL' => self::MTL,
            'DNIRC' => self::DNIRC,
            'DIC' => self::DIC,
            'CIG' => self::CIG,
            default => self::tryFrom($code),
        };
    }
}
