<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\App;

use Exception;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\JobStatus;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface;
use Yiisoft\Queue\QueueInterface;

final class DummyQueue implements QueueInterface
{
    public function __construct(
        private readonly string $channel
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
        throw new Exception('`run()` method is not implemented yet.');
    }

    public function listen(): void
    {
    }

    public function status(string|int $id): JobStatus
    {
        throw new Exception('`status()` method is not implemented yet.');
    }

    public function withAdapter(AdapterInterface $adapter): QueueInterface
    {
        throw new Exception('`withAdapter()` method is not implemented yet.');
    }

    public function getChannel(): string
    {
        return $this->channel;
    }
}
