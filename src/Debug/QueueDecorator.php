<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Debug;

use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Enum\JobStatus;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface;
use Yiisoft\Queue\QueueInterface;

final class QueueDecorator implements QueueInterface
{
    public function __construct(
        private QueueInterface $queue,
        private QueueCollector $collector,
    ) {
    }

    public function status(string|int $id): JobStatus
    {
        $result = $this->queue->status($id);
        $this->collector->collectStatus($id, $result);

        return $result;
    }

    public function push(
        MessageInterface $message,
        string|array|callable|MiddlewarePushInterface ...$middlewareDefinitions
    ): MessageInterface {
        $message = $this->queue->push($message, ...$middlewareDefinitions);
        $this->collector->collectPush($this->queue->getChannelName(), $message, ...$middlewareDefinitions);
        return $message;
    }

    public function run(int $max = 0): void
    {
        $this->queue->run($max);
    }

    public function listen(): void
    {
        $this->queue->listen();
    }

    public function withAdapter(AdapterInterface $adapter): QueueInterface
    {
        return new self($this->queue->withAdapter($adapter), $this->collector);
    }

    public function getChannelName(): string
    {
        return $this->queue->getChannelName();
    }

    public function withChannelName(string $channel): QueueInterface
    {
        $new = clone $this;
        $new->queue = $this->queue->withChannelName($channel);
        return $new;
    }
}
