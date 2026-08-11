<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Debug;

use BackedEnum;
use Yiisoft\Queue\Provider\QueueConsumerProviderInterface;
use Yiisoft\Queue\QueueConsumerInterface;

final class QueueConsumerProviderProxy implements QueueConsumerProviderInterface
{
    public function __construct(private readonly QueueConsumerProviderInterface $provider, private readonly QueueCollector $collector) {}

    public function getConsumer(string|BackedEnum $queue): QueueConsumerInterface
    {
        return new QueueConsumerDecorator($this->provider->getConsumer($queue), $this->collector);
    }

    public function hasConsumer(string|BackedEnum $queue): bool
    {
        return $this->provider->hasConsumer($queue);
    }

    public function getConsumerQueues(): array
    {
        return $this->provider->getConsumerQueues();
    }
}
