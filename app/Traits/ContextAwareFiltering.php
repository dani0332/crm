<?php

namespace App\Traits;

trait ContextAwareFiltering
{
    /**
     * Get filter value from requestParams or request object.
     */
    private function getFilterValue($filterName, $requestParams = [])
    {
        // First check if we have requestParams (for export context)
        if (! empty($requestParams) && isset($requestParams[$filterName])) {
            return $requestParams[$filterName];
        }

        // Fallback to request object
        return request($filterName);
    }

    /**
     * Check if filter value exists in requestParams or request object.
     */
    private function hasFilterValue($filterName, $requestParams = [])
    {
        // First check if we have requestParams (for export context)
        if (! empty($requestParams) && isset($requestParams[$filterName])) {
            $value = $requestParams[$filterName];

            return ! empty($value) || (is_array($value) && count($value) > 0);
        }

        // Fallback to request object
        return request()->filled($filterName);
    }

    /**
     * Map common parameter name variations to expected filter names
     */
    private function mapParameterVariations(array $requestParams): array
    {
        // Map lead status parameter variations to quote_status_id
        $leadStatusVariations = ['leadStatus', 'lead_status', 'status', 'quote_status'];

        foreach ($leadStatusVariations as $variation) {
            if (isset($requestParams[$variation]) && ! isset($requestParams['quote_status_id'])) {
                $requestParams['quote_status_id'] = $requestParams[$variation];
                unset($requestParams[$variation]); // Remove the original to avoid confusion
            }
        }

        return $requestParams;
    }
}
