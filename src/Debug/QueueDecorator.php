<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Debug;

use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\QueueInterface;

final class QueueDecorator implements QueueInterface
{
    public function __construct(
        private readonly QueueInterface $queue,
        private readonly QueueCollector $collector,
    ) {}

    public function status(string|int $id): MessageStatus
    {
        $result = $this->queue->status($id);
        $this->collector->collectStatus((string) $id, $result);

        return $result;
    }

    public function push(MessageInterface $message): MessageInterface
    {
        $message = $this->queue->push($message);
        $this->collector->collectPush($this->queue->getName(), $message);
        return $message;
    }

    public function run(int $max = 0): int
    {
        return $this->queue->run($max);
    }

    public function listen(): void
    {
        $this->queue->listen();
    }

    public function getName(): string
    {
        return $this->queue->getName();
    }
}
