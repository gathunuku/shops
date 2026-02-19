<?php

namespace Vuma\SaaS\Services\Regions;

class RegionService
{
    protected array $map;

    public function __construct(?array $map = null)
    {
        $this->map = $map ?? config('regions', []);
    }

    public function currency(string $country): string
    {
        return $this->map[strtoupper($country)]['currency'] ?? 'USD';
    }

    /**
     * Returns payment channels in priority order for a given country.
     * e.g. ['mpesa_ke', 'paystack', 'card']
     */
    public function defaultChannels(string $country): array
    {
        return $this->map[strtoupper($country)]['channels'] ?? ['paystack', 'card'];
    }

    /**
     * Returns the primary (first) payment channel for a country.
     */
    public function primaryChannel(string $country): string
    {
        return $this->defaultChannels($country)[0] ?? 'paystack';
    }

    public function all(): array
    {
        return $this->map;
    }

    public function supports(string $country): bool
    {
        return isset($this->map[strtoupper($country)]);
    }
}
