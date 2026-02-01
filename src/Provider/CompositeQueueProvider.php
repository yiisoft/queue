<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use BackedEnum;
use Yiisoft\Queue\QueueInterface;

/**
 * Composite queue provider.
 */
final class CompositeQueueProvider implements QueueProviderInterface
{
    /**
     * @var QueueProviderInterface[]
     */
    private readonly array $providers;

    /**
     * @param QueueProviderInterface ...$providers Queue providers to use.
     */
    public function __construct(
        QueueProviderInterface ...$providers,
    ) {
        $this->providers = $providers;
    }

    public function get(string|BackedEnum $queueName): QueueInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->has($queueName)) {
                return $provider->get($queueName);
            }
        }
        throw new QueueNotFoundException($queueName);
    }

    public function has(string|BackedEnum $queueName): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->has($queueName)) {
                return true;
            }
        }
        return false;
    }
}
