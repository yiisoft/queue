<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Stubs;

use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Enum\JobStatus;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface;
use Yiisoft\Queue\QueueInterface;

/**
 * Stub queue that does nothing. Job status is always "done".
 */
final class StubQueue implements QueueInterface
{
    public function __construct(private ?AdapterInterface $adapter = null)
    {
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

    public function getAdapter(): ?AdapterInterface
    {
        return $this->adapter;
    }

    public function withAdapter(AdapterInterface $adapter): QueueInterface
    {
        $new = clone $this;
        $new->adapter = $adapter;

        return $new;
    }

    public function getChannel(): ?string
    {
        return $this->adapter?->getChannel();
    }
}
