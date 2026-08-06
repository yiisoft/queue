<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use BackedEnum;
use Yiisoft\Queue\QueueProducerInterface;

/** Finds producer capabilities by logical queue name. */
interface QueueProducerProviderInterface extends QueueProviderDefaults
{
    /** @throws InvalidQueueConfigException|QueueNotFoundException|QueueProviderException */
    public function getProducer(string|BackedEnum $name): QueueProducerInterface;

    /** Whether this name has a configured producer role. */
    public function hasProducer(string|BackedEnum $name): bool;

    /** @return list<string> Names which have a configured producer role. */
    public function getProducerNames(): array;
}
