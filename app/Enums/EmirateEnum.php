<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class EmirateEnum extends Enum
{
    const AJMAN = 1;
    const DUBAI = 2;
    const FUJAIRAH = 3;
    const RAS_AL_KHAIMAH = 4;
    const SHARJAH = 5;
    const UMM_AL_QUWAIN = 6;
    const ABU_DHABI = 7;

    /**
     * Get the branch based on the emirate
     *
     * @param int $emirate
     * @return string
     */
    public static function getBranch(int $emirate): string
    {
        return $emirate === self::ABU_DHABI
            ? 'Abu Dhabi'
            : 'Dubai & Northern Emirates';
    }

    /**
     * Get emirate mapping with branches for frontend
     *
     * @return array
     */
    public static function getBranchMapping(): array
    {
        return [
            ['id' => self::AJMAN, 'branch' => self::getBranch(self::AJMAN)],
            ['id' => self::DUBAI, 'branch' => self::getBranch(self::DUBAI)],
            ['id' => self::FUJAIRAH, 'branch' => self::getBranch(self::FUJAIRAH)],
            ['id' => self::RAS_AL_KHAIMAH, 'branch' => self::getBranch(self::RAS_AL_KHAIMAH)],
            ['id' => self::SHARJAH, 'branch' => self::getBranch(self::SHARJAH)],
            ['id' => self::UMM_AL_QUWAIN, 'branch' => self::getBranch(self::UMM_AL_QUWAIN)],
            ['id' => self::ABU_DHABI, 'branch' => self::getBranch(self::ABU_DHABI)],
        ];
    }
}
