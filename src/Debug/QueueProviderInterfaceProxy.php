<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Debug;

use BackedEnum;
use Yiisoft\Queue\Provider\QueueProviderInterface;
use Yiisoft\Queue\QueueInterface;

final class QueueProviderInterfaceProxy implements QueueProviderInterface
{
    public function __construct(
        private readonly QueueProviderInterface $queueProvider,
        private readonly QueueCollector $collector,
    ) {}

    public function get(string|BackedEnum $channel): QueueInterface
    {
        $queue = $this->queueProvider->get($channel);
        return new QueueDecorator($queue, $this->collector);
    }

    public function has(string|BackedEnum $channel): bool
    {
        return $this->queueProvider->has($channel);
    }
}
