<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Debug;

use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\QueueProducerInterface;

final class QueueProducerDecorator implements QueueProducerInterface
{
    public function __construct(private readonly QueueProducerInterface $queue, private readonly QueueCollector $collector) {}

    public function status(string|int $id): MessageStatus
    { /** @psalm-var array{file: string, line: int} $stack */ $stack = debug_backtrace()[0];
        $result = $this->queue->status($id);
        $this->collector->collectStatus((string) $id, $result, $stack['file'] . ':' . $stack['line']);
        return $result;
    }

    public function push(MessageInterface $message): MessageInterface
    { /** @psalm-var array{file: string, line: int} $stack */ $stack = debug_backtrace()[0];
        $message = $this->queue->push($message);
        $this->collector->collectPush($this->queue->getName(), $message, $stack['file'] . ':' . $stack['line']);
        return $message;
    }

    public function getName(): string
    {
        return $this->queue->getName();
    }
}
