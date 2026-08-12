<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use BackedEnum;
use Yiisoft\Queue\QueueConsumerInterface;

/** Finds consumer capabilities by logical queue name. */
interface QueueConsumerProviderInterface
{
    /** @throws InvalidQueueConfigException|QueueNotFoundException|QueueProviderException */
    public function getConsumer(string|BackedEnum $queueName): QueueConsumerInterface;

    /** Whether this queue name has a configured consumer role. */
    public function hasConsumer(string|BackedEnum $queueName): bool;

    /** @return list<string> Queue names which have a configured consumer role. */
    public function getConsumerQueueNames(): array;
}
