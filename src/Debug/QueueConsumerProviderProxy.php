<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Debug;

use BackedEnum;
use Yiisoft\Queue\Provider\QueueConsumerProviderInterface;
use Yiisoft\Queue\QueueConsumerInterface;

final class QueueConsumerProviderProxy implements QueueConsumerProviderInterface
{
    public function __construct(private readonly QueueConsumerProviderInterface $provider, private readonly QueueCollector $collector) {}

    public function getConsumer(string|BackedEnum $name): QueueConsumerInterface
    {
        return new QueueConsumerDecorator($this->provider->getConsumer($name), $this->collector);
    }

    public function hasConsumer(string|BackedEnum $name): bool
    {
        return $this->provider->hasConsumer($name);
    }

    public function getConsumerNames(): array
    {
        return $this->provider->getConsumerNames();
    }
}
