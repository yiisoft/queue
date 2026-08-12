<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Debug;

use BackedEnum;
use Yiisoft\Queue\Provider\QueueProducerProviderInterface;
use Yiisoft\Queue\QueueProducerInterface;

final class QueueProducerProviderProxy implements QueueProducerProviderInterface
{
    public function __construct(private readonly QueueProducerProviderInterface $provider, private readonly QueueCollector $collector) {}

    public function getProducer(string|BackedEnum $queueName): QueueProducerInterface
    {
        return new QueueProducerDecorator($this->provider->getProducer($queueName), $this->collector);
    }

    public function hasProducer(string|BackedEnum $queueName): bool
    {
        return $this->provider->hasProducer($queueName);
    }

    public function getProducerQueueNames(): array
    {
        return $this->provider->getProducerQueueNames();
    }
}
