<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Stubs;

use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Enum\JobStatus;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\QueueInterface;

/**
 * Stub adapter that does nothing. Job status is always "done".
 */
final class StubAdapter implements AdapterInterface
{
    public function runExisting(callable $handlerCallback): void
    {
    }

    public function status(int|string $id): JobStatus
    {
        return JobStatus::done();
    }

    public function push(MessageInterface $message): MessageInterface
    {
        return $message;
    }

    public function subscribe(callable $handlerCallback): void
    {
    }

    public function withChannel(string $channel): AdapterInterface
    {
        return clone $this;
    }

    public function getChannelName(): string
    {
        return QueueInterface::DEFAULT_CHANNEL_NAME;
    }
}
