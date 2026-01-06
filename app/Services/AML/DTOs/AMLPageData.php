<?php

namespace App\Services\AML\DTOs;

/**
 * Generic Data Transfer Object for AML page data
 * Used for both simple show pages and complex detail/screening pages
 * Provides a flexible structure for Inertia page responses
 */
class AMLPageData
{
    public function __construct(
        private readonly array $data
    ) {}

    /**
     * Convert to array for Inertia response
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Get a specific value from the data
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Check if a key exists in the data
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Merge additional data into the page data
     *
     * @param array $additionalData
     * @return self
     */
    public function merge(array $additionalData): self
    {
        return new self(array_merge($this->data, $additionalData));
    }
}

