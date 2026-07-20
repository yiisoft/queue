<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Debug;

use BackedEnum;
use Yiisoft\Queue\Provider\QueueProviderInterface;
use Yiisoft\Queue\QueueConsumerInterface;
use Yiisoft\Queue\QueueInterface;

final class QueueProviderInterfaceProxy implements QueueProviderInterface
{
    public function __construct(
        private readonly QueueProviderInterface $queueProvider,
        private readonly QueueCollector $collector,
    ) {}

    public function get(string|BackedEnum $name): QueueInterface
    {
        $queue = $this->queueProvider->get($name);

        return $queue instanceof QueueConsumerInterface
            ? new QueueConsumerDecorator($queue, $this->collector)
            : new QueueDecorator($queue, $this->collector);
    }

    public function has(string|BackedEnum $name): bool
    {
        return $this->queueProvider->has($name);
    }

    public function getNames(): array
    {
        return $this->queueProvider->getNames();
    }
}
