<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use BackedEnum;
use Yiisoft\Queue\QueueProducerInterface;

/** Finds producer capabilities by logical queue. */
interface QueueProducerProviderInterface
{
    /** @throws InvalidQueueConfigException|QueueNotFoundException|QueueProviderException */
    public function getProducer(string|BackedEnum $queue): QueueProducerInterface;

    /** Whether this queue has a configured producer role. */
    public function hasProducer(string|BackedEnum $queue): bool;

    /** @return list<string> Queues which have a configured producer role. */
    public function getProducerQueues(): array;
}
