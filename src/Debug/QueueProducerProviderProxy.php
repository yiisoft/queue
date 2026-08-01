<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Debug;

use BackedEnum;
use Yiisoft\Queue\Provider\QueueProducerProviderInterface;
use Yiisoft\Queue\QueueProducerInterface;

final class QueueProducerProviderProxy implements QueueProducerProviderInterface
{
    public function __construct(private readonly QueueProducerProviderInterface $provider, private readonly QueueCollector $collector) {}

    public function getProducer(string|BackedEnum $name): QueueProducerInterface
    {
        return new QueueProducerDecorator($this->provider->getProducer($name), $this->collector);
    }

    public function hasProducer(string|BackedEnum $name): bool
    {
        return $this->provider->hasProducer($name);
    }

    public function getProducerNames(): array
    {
        return $this->provider->getProducerNames();
    }
}
