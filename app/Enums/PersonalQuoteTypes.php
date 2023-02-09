<?php

namespace App\Enums;

enum PersonalQuoteTypes: string {

    case BIKE = 'BIKE';

    /**
     * @return string
     */
    public function id(): string {
        return static::getId($this);
    }

    /**
     * @param PersonalQuoteTypes $value
     * @return int
     */
    public static function getId(self $value): int {
        return match ($value) {
            PersonalQuoteTypes::BIKE => 1
        };
    }
}
