<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class EpEcbExcludeVehicleEnum extends Enum
{
    // CarMake Code
    public const MAKE_LAMBORGHINI = 10297632;
    public const MAKE_MCLAREN = 10299137;
    public const MAKE_FERRARI = 10296083;
    public const MAKE_ROLLS_ROYCE = 10300288;
    public const MAKE_BENTLEY = 10302389;
    public const MAKE_KOENIGSEGG = 10304235;

    // CarModel Code
    public const MODEL_STREETFIGHTER_LAMBORGHINI_1103_CC = 10308369;
    public const MODEL_SLR_MCLAREN = 10298424;
    public const MODEL_AARTH_695_TRIBUTO_FERRARI = 10296231;
    public const MODEL_FERRARI_456 = 10296114;
    public const MODEL_FERRARI_ENZO = 10296128;
    public const MODEL_LAFERRARI = 10296162;
    public const MODEL_LAFERRARI_APERTA = 10304643;
    public const MODEL_BENTLEY = 10300295;

    // CarMake Codes
    public const CAR_MAKE_CODES = [
        self::MAKE_LAMBORGHINI,
        self::MAKE_MCLAREN,
        self::MAKE_FERRARI,
        self::MAKE_ROLLS_ROYCE,
        self::MAKE_BENTLEY,
        self::MAKE_KOENIGSEGG,
    ];

    // CarModel Codes
    public const CAR_MODEL_CODES = [
        self::MODEL_STREETFIGHTER_LAMBORGHINI_1103_CC,
        self::MODEL_SLR_MCLAREN,
        self::MODEL_AARTH_695_TRIBUTO_FERRARI,
        self::MODEL_FERRARI_456,
        self::MODEL_FERRARI_ENZO,
        self::MODEL_LAFERRARI,
        self::MODEL_LAFERRARI_APERTA,
        self::MODEL_BENTLEY,
    ];
}
