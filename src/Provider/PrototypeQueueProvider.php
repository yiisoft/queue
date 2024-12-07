<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use Yiisoft\Queue\QueueInterface;

/**
 * Queue provider that only changes the channel name of the base queue.
 * It can be useful when your queues used the same adapter.
 */
final class PrototypeQueueProvider implements QueueProviderInterface
{
    /**
     * @param QueueInterface $baseQueue Base queue to use for creating queues.
     */
    public function __construct(
        private readonly QueueInterface $baseQueue,
    ) {
    }

    public function get(string $channel): QueueInterface
    {
        return $this->baseQueue->withChannelName($channel);
    }

    public function has(string $channel): bool
    {
        return true;
    }
}
