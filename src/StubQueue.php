<?php

declare(strict_types=1);

namespace Yiisoft\Queue;

use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Enum\JobStatus;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface;

final class StubQueue implements QueueInterface
{
    public function __construct(
        private string $channelName = Queue::DEFAULT_CHANNEL_NAME,
    ) {
    }

    public function push(
        MessageInterface $message,
        string|array|callable|MiddlewarePushInterface ...$middlewareDefinitions
    ): MessageInterface {
        return $message;
    }

    public function run(int $max = 0): int
    {
        return 0;
    }

    public function listen(): void
    {
    }

    public function status(int|string $id): JobStatus
    {
        return JobStatus::done();
    }

    public function withAdapter(AdapterInterface $adapter): QueueInterface
    {
        return clone $this;
    }

    public function getChannelName(): string
    {
        return $this->channelName;
    }

    public function withChannelName(string $channel): QueueInterface
    {
        $new = clone $this;
        $new->channelName = $channel;
        return $new;
    }
}
