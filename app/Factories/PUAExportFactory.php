<?php

namespace App\Factories;

use App\Enums\quoteTypeCode;
use App\Exports\NonPUAQuoteExport;
use App\Exports\PUAQuoteExport;
use App\Exports\PUAUpdatesExport;
use App\Exports\HomeNonPUAQuoteExport;
use App\Exports\HomePUAQuoteExport;
use App\Exports\HomePUAUpdatesExport;
use InvalidArgumentException;

class PUAExportFactory
{
    /**
     * Create all PUA export instances for the given quote type
     *
     * @param string $quoteType The quote type from quoteTypeCode enum
     * @param array $requestParams Optional request parameters for filtering
     * @return array Array containing export objects or empty array
     * @throws InvalidArgumentException
     */
    public static function createExports(string $quoteType, array $requestParams = []): array
    {
        // Validate quote type
        if (!self::isValidQuoteType($quoteType)) {
            throw new InvalidArgumentException("Unsupported quote type: {$quoteType}");
        }

        switch ($quoteType) {
            case quoteTypeCode::Car:
                return [
                    'pua_quote' => new PUAQuoteExport($requestParams),
                    'non_pua_quote' => new NonPUAQuoteExport($requestParams),
                    'pua_updates' => new PUAUpdatesExport($requestParams),
                ];

            case quoteTypeCode::Home:
                return [
                    'pua_quote' => new HomePUAQuoteExport($requestParams),
                    'non_pua_quote' => new HomeNonPUAQuoteExport($requestParams),
                    'pua_updates' => new HomePUAUpdatesExport($requestParams),
                ];

            default:
                throw new InvalidArgumentException("No PUA exports available for quote type: {$quoteType}");
        }
    }

    /**
     * Get available quote types that support PUA exports
     *
     * @return array
     */
    public static function getSupportedQuoteTypes(): array
    {
        return [
            quoteTypeCode::Car,
            quoteTypeCode::Home,
        ];
    }

    /**
     * Check if quote type supports PUA exports
     *
     * @param string $quoteType
     * @return bool
     */
    public static function isValidQuoteType(string $quoteType): bool
    {
        return in_array($quoteType, self::getSupportedQuoteTypes());
    }

    /**
     * Get available export types
     *
     * @return array
     */
    public static function getAvailableExportTypes(): array
    {
        return [
            'pua_quote',
            'non_pua_quote', 
            'pua_updates',
        ];
    }
}