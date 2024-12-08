<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use Yiisoft\Queue\Adapter\AdapterInterface;
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
        private readonly AdapterInterface $baseAdapter,
    ) {
    }

    public function get(string $channel): QueueInterface
    {
        return $this->baseQueue->withAdapter($this->baseAdapter->withChannel($channel));
    }

    public function has(string $channel): bool
    {
        return true;
    }
}
