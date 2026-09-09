<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use BackedEnum;
use Yiisoft\Queue\QueueProducerStatusInterface;

/** Finds producer status capabilities by logical queue name. */
interface QueueProducerStatusProviderInterface
{
    /** @throws InvalidQueueConfigException|QueueNotFoundException|QueueProviderException */
    public function getStatus(string|BackedEnum $queueName): QueueProducerStatusInterface;

    /** Whether this queue name has a configured producer status capability. */
    public function hasStatus(string|BackedEnum $queueName): bool;

    /** @return list<string> Queue names which have a configured producer status capability. */
    public function getStatusQueueNames(): array;
}
