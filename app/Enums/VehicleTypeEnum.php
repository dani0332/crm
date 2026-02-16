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
     * @var array<self, string>
     */
    public const TEXTS = [
        self::BIKE->value => 'BIKE',
        self::SPORTS_BIKE->value => 'SPORTS BIKE',
        self::MOTOR_CYCLE->value => 'MOTOR CYCLE',
        self::MOTORCYCLES->value => 'Motorcycles',
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

    public function text(): string
    {
        return self::TEXTS[$this->value];
    }

    public static function fromId(int $id): ?self
    {
        $found = array_search($id, self::IDS, true);

        return $found !== false ? $found : null;
    }

    public static function fromText(string $text): ?self
    {
        $found = array_search($text, self::TEXTS, true);

        return $found !== false ? $found : null;
    }
}
