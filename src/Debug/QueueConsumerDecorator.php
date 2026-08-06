<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Debug;

use Yiisoft\Queue\QueueConsumerInterface;

final class QueueConsumerDecorator implements QueueConsumerInterface
{
    public function __construct(private readonly QueueConsumerInterface $queue, private readonly QueueCollector $collector) {}

    public function run(int $max = 0): int
    {
        return $this->queue->run($max);
    }

    public function listen(): void
    {
        $this->queue->listen();
    }
}
