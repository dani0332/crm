<?php

declare(strict_types=1);

namespace App\Enums;

enum CustomerVerificationStatus: string
{
    case VERIFIED = 'verified';
    case REQUIRES_VERIFICATION = 'requires_verification';

    public function getText(): string
    {
        return match ($this) {
            self::VERIFIED => 'Details Verified',
            self::REQUIRES_VERIFICATION => 'Verification Required',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::VERIFIED => 'green',
            self::REQUIRES_VERIFICATION => 'red',
        };
    }

    public function getClass(): string
    {
        return match ($this) {
            self::VERIFIED => 'font-semibold shadow-md border-green-300',
            self::REQUIRES_VERIFICATION => 'font-bold shadow-lg border-red-300 animate-pulse',
        };
    }

    public function getButtonConfig(): array
    {
        return [
            'text' => $this->getText(),
            'color' => $this->getColor(),
            'class' => $this->getClass(),
        ];
    }

    public function isVerified(): bool
    {
        return $this === self::VERIFIED;
    }

    public function requiresAction(): bool
    {
        return $this === self::REQUIRES_VERIFICATION;
    }

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function getOptions(): array
    {
        return array_map(
            fn(self $status) => [
                'value' => $status->value,
                'label' => $status->getText(),
                'color' => $status->getColor(),
            ],
            self::cases()
        );
    }
}
