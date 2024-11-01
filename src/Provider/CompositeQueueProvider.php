<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use Yiisoft\Queue\QueueInterface;

final class CompositeQueueProvider implements QueueProviderInterface
{
    /**
     * @var QueueProviderInterface[]
     */
    private readonly array $providers;

    public function __construct(
        QueueProviderInterface ...$providers
    ) {
        $this->providers = $providers;
    }

    public function get(string $channel): QueueInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->has($channel)) {
                return $provider->get($channel);
            }
        }
        throw new ChannelNotFoundException($channel);
    }

    public function has(string $channel): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->has($channel)) {
                return true;
            }
        }
        return false;
    }
}
