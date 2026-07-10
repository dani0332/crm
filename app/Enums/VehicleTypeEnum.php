<?php

declare(strict_types=1);

namespace App\Enums;

enum VehicleTypeEnum: string
{
    use Enumable;

    case BIKE = 'BIKE';
    case SPORTS_BIKE = 'SPORTS_BIKE';
    case MOTOR_CYCLE = 'MOTOR_CYCLE';
    case MOTORCYCLES = 'MOTORCYCLES';

    /**
     * @var array<self, int>
     */
    public const IDS = [
        self::BIKE->value => 13,
        self::SPORTS_BIKE->value => 26,
        self::MOTOR_CYCLE->value => 21,
        self::MOTORCYCLES->value => 57,
    ];

    /**
     * @return list<int>
     */
    public static function ids(): array
    {
        return array_values(self::IDS);
    }

    public function id(): int
    {
        return self::IDS[$this->value];
    }
}
