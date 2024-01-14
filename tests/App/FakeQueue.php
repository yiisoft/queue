<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\App;

use Exception;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Enum\JobStatus;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\MiddlewareInterface;
use Yiisoft\Queue\QueueInterface;

final class FakeQueue implements QueueInterface
{
    private ?AdapterInterface $adapter = null;

    public function __construct(private string $channelName)
    {
    }

    public function push(
        MessageInterface $message,
        string|array|callable|MiddlewareInterface ...$middlewareDefinitions
    ): MessageInterface {
        return $message;
    }

    public function run(int $max = 0): void
    {
    }

    public function listen(): void
    {
    }

    public function status(string|int $id): JobStatus
    {
        throw new Exception('`status()` method is not implemented yet.');
    }

    public function withAdapter(AdapterInterface $adapter): self
    {
        $new = clone $this;
        $new->adapter = $adapter;

        return $new;
    }

    public function getChannelName(): string
    {
        return $this->channelName;
    }

    public function withChannelName(string $channel): self
    {
        throw new Exception('`withChannelName()` method is not implemented yet.');
    }

    public function getAdapter(): ?AdapterInterface
    {
        return $this->adapter;
    }
}
