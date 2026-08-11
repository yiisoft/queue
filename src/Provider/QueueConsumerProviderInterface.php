<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use BackedEnum;
use Yiisoft\Queue\QueueConsumerInterface;

/** Finds consumer capabilities by logical queue. */
interface QueueConsumerProviderInterface
{
    /** @throws InvalidQueueConfigException|QueueNotFoundException|QueueProviderException */
    public function getConsumer(string|BackedEnum $queue): QueueConsumerInterface;

    /** Whether this queue has a configured consumer role. */
    public function hasConsumer(string|BackedEnum $queue): bool;

    /** @return list<string> Queues which have a configured consumer role. */
    public function getConsumerQueues(): array;
}
