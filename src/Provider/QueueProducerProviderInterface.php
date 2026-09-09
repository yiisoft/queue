<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use BackedEnum;
use Yiisoft\Queue\AsyncQueueProducer;
use Yiisoft\Queue\SyncQueueProducer;

/** Finds producer capabilities by logical queue name. */
interface QueueProducerProviderInterface
{
    /** @throws InvalidQueueConfigException|QueueNotFoundException|QueueProviderException */
    public function getProducer(string|BackedEnum $queueName): AsyncQueueProducer|SyncQueueProducer;

    /** Whether this queue name has a configured producer role. */
    public function hasProducer(string|BackedEnum $queueName): bool;

    /** @return list<string> Queue names which have a configured producer role. */
    public function getProducerQueueNames(): array;
}
