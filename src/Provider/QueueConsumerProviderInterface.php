<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use BackedEnum;
use Yiisoft\Queue\QueueConsumerInterface;

/** Finds consumer capabilities by logical queue name. */
interface QueueConsumerProviderInterface extends QueueProviderDefaults
{
    /** @throws InvalidQueueConfigException|QueueNotFoundException|QueueProviderException */
    public function getConsumer(string|BackedEnum $name): QueueConsumerInterface;

    /** Whether this name has a configured consumer role. */
    public function hasConsumer(string|BackedEnum $name): bool;

    /** @return list<string> Names which have a configured consumer role. */
    public function getConsumerNames(): array;
}
