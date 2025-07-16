<?php

namespace App\Enums;

enum SageEmbeddedProductEnum: string
{
    case BOOKING_QUEUED = 'Booking Queued';
    case BOOKING_FAILED = 'Booking Failed';
    case BOOKING_COMPLETED = 'Booked';
    case BOOKING_CANCELLED = 'Cancelled';

    public function id()
    {
        return self::getId($this) ?? null;
    }

    public static function getId(self $value): ?int
    {
        return match ($value) {
            SageEmbeddedProductEnum::BOOKING_QUEUED => 1,
            SageEmbeddedProductEnum::BOOKING_FAILED => 2,
            SageEmbeddedProductEnum::BOOKING_COMPLETED => 3,
            SageEmbeddedProductEnum::BOOKING_CANCELLED => 4,
            default => null,
        };
    }
    public static function getStatusById($value)
    {
        $types = [
            1 => SageEmbeddedProductEnum::BOOKING_QUEUED,
            2 => SageEmbeddedProductEnum::BOOKING_FAILED,
            3 => SageEmbeddedProductEnum::BOOKING_COMPLETED,
            4 => SageEmbeddedProductEnum::BOOKING_CANCELLED,
        ];

        return isset($types[$value]) ? $types[$value] : null;
    }
}
