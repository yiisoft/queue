<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Stubs;

use BackedEnum;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\JobStatus;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\QueueNameNormalizer;

/**
 * Stub queue that does nothing. Job status is always "done".
 */
final class StubQueue implements QueueInterface
{
    private string $name = 'default';

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
        return JobStatus::DONE;
    }

    public function getAdapter(): ?AdapterInterface
    {
        return $this->adapter;
    }

    public function withAdapter(AdapterInterface $adapter, string|BackedEnum|null $queueName = null): static
    {
        $new = clone $this;
        $new->adapter = $adapter;

        if ($queueName !== null) {
            $new->name = QueueNameNormalizer::normalize($queueName);
        }

        return $new;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
