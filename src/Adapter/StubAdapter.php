<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Adapter;

use Yiisoft\Queue\Enum\JobStatus;
use Yiisoft\Queue\Message\MessageInterface;

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
}
