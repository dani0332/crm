<?php

namespace App\Enums;

enum SageEmbeddedProductEnum: string
{
    use Enumable;
    case BOOKING_QUEUED = 'Booking Queued';
    case BOOKING_FAILED = 'Booking Failed';
    case BOOKING_COMPLETED = 'Booked';
    case BOOKING_CANCELLED = 'Cancelled';
    case BOOKING_REVERSAL_FAILED = 'Booking Reversal Failed';

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
            SageEmbeddedProductEnum::BOOKING_REVERSAL_FAILED => 5,
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
            5 => SageEmbeddedProductEnum::BOOKING_REVERSAL_FAILED,
        ];

        return isset($types[$value]) ? $types[$value] : null;
    }

    /**
     * Convert an array of enum VALUES (e.g. "Booked") into their numeric IDs for DB usage.
     */
    public static function idsFromValues(array $values): array
    {
        return collect($values)
            ->map(function ($value) {
                try {
                    return self::from($value)->id();
                } catch (\Throwable $e) {
                    return null;
                }
            })
            ->filter()
            ->values()
            ->all();
    }
}
