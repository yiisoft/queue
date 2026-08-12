<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Debug;

use BackedEnum;
use Yiisoft\Queue\Provider\QueueConsumerProviderInterface;
use Yiisoft\Queue\QueueConsumerInterface;

final class QueueConsumerProviderProxy implements QueueConsumerProviderInterface
{
    public function __construct(private readonly QueueConsumerProviderInterface $provider, private readonly QueueCollector $collector) {}

    public function getConsumer(string|BackedEnum $queueName): QueueConsumerInterface
    {
        return new QueueConsumerDecorator($this->provider->getConsumer($queueName), $this->collector);
    }

    public function hasConsumer(string|BackedEnum $queueName): bool
    {
        return $this->provider->hasConsumer($queueName);
    }

    public function getConsumerQueueNames(): array
    {
        return $this->provider->getConsumerQueueNames();
    }
}
