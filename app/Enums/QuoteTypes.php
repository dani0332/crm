<?php

namespace App\Enums;

enum QuoteTypes: string {

    case BIKE = 'Bike';

    /**
     * @return string
     */
    public function id(): string {
        return static::getId($this);
    }

    /**
     * @param QuoteTypes $value
     * @return int
     */
    public static function getId(self $value): int {
        return match ($value) {
            QuoteTypes::BIKE => 6
        };
    }
}
