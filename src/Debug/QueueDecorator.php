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
        /** @psalm-var array{file: string, line: int} $callStack */
        $callStack = debug_backtrace()[0];

        $result = $this->queue->status($id);
        $this->collector->collectStatus((string) $id, $result, $callStack['file'] . ':' . $callStack['line']);

        return $result;
    }

    public function push(MessageInterface $message): MessageInterface
    {
        /** @psalm-var array{file: string, line: int} $callStack */
        $callStack = debug_backtrace()[0];

        $message = $this->queue->push($message);
        $this->collector->collectPush($this->queue->getName(), $message, $callStack['file'] . ':' . $callStack['line']);
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

    public function withMiddlewares(mixed ...$middlewareDefinitions): self
    {
        return new self($this->queue->withMiddlewares(...$middlewareDefinitions), $this->collector);
    }

    public function withMiddlewaresAdded(mixed ...$middlewareDefinitions): self
    {
        return new self($this->queue->withMiddlewaresAdded(...$middlewareDefinitions), $this->collector);
    }
}
