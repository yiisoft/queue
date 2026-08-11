<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Debug;

use BackedEnum;
use Yiisoft\Queue\Provider\QueueProducerProviderInterface;
use Yiisoft\Queue\QueueProducerInterface;

final class QueueProducerProviderProxy implements QueueProducerProviderInterface
{
    public function __construct(private readonly QueueProducerProviderInterface $provider, private readonly QueueCollector $collector) {}

    public function getProducer(string|BackedEnum $queue): QueueProducerInterface
    {
        return new QueueProducerDecorator($this->provider->getProducer($queue), $this->collector);
    }

    public function hasProducer(string|BackedEnum $queue): bool
    {
        return $this->provider->hasProducer($queue);
    }

    public function getProducerQueues(): array
    {
        return $this->provider->getProducerQueues();
    }
}
