<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use BackedEnum;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\QueueInterface;

/**
 * Queue provider that only changes the channel name of the base adapter and sets queue name to the same value.
 * It can be useful when your queues use the same adapter, which only changes the broker channel name.
 */
final class PrototypeQueueProvider implements QueueProviderInterface
{
    /**
     * @param QueueInterface $baseQueue Base queue to use for creating queues.
     */
    public function __construct(
        private readonly QueueInterface $baseQueue,
        private readonly AdapterInterface $baseAdapter,
    ) {}

    public function get(string|BackedEnum $queueName): QueueInterface
    {
        return $this->baseQueue->withAdapter($this->baseAdapter->withChannel($queueName), $queueName);
    }

    public function has(string|BackedEnum $queueName): bool
    {
        return true;
    }
}
