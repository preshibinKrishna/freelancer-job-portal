<?php

declare(strict_types=1);

namespace App\Domain\Payments\Services;

use App\Domain\Payments\Contracts\PaymentProvider;
use InvalidArgumentException;

class PaymentProviderRegistry
{
    /** @var array<string, PaymentProvider> */
    private array $providers = [];

    /** @param iterable<PaymentProvider> $providers */
    public function __construct(iterable $providers = [])
    {
        foreach ($providers as $provider) {
            $this->register($provider);
        }
    }

    public function register(PaymentProvider $provider): void
    {
        if (isset($this->providers[$provider->name()])) {
            throw new InvalidArgumentException('A payment provider with this name is already registered.');
        }

        $this->providers[$provider->name()] = $provider;
    }

    public function resolve(string $name): PaymentProvider
    {
        return $this->providers[$name] ?? throw new InvalidArgumentException('The requested payment provider is not configured.');
    }

    public function has(string $name): bool
    {
        return isset($this->providers[$name]);
    }
}
