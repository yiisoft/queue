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
        QueueProviderInterface ...$providers
    ) {
        $this->providers = $providers;
    }

    public function get(string|BackedEnum $channel): QueueInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->has($channel)) {
                return $provider->get($channel);
            }
        }
        throw new ChannelNotFoundException($channel);
    }

    public function has(string|BackedEnum $channel): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->has($channel)) {
                return true;
            }
        }
        return false;
    }
}
