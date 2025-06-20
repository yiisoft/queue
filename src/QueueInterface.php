<?php

declare(strict_types=1);

namespace Yiisoft\Queue;

use InvalidArgumentException;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface;

interface QueueInterface
{
    /** @psalm-suppress MissingClassConstType */
    public const DEFAULT_CHANNEL = 'yii-queue';

    /**
     * Pushes a message into the queue.
     *
     * @param array|callable|MiddlewarePushInterface|string ...$middlewareDefinitions
     * @return MessageInterface
     */
    public function push(MessageInterface $message, MiddlewarePushInterface|callable|array|string ...$middlewareDefinitions): MessageInterface;

    /**
     * Execute all existing jobs and exit
     *
     * @return int Number of messages processed.
     */
    public function run(int $max = 0): int;

    /**
     * Listen to the queue and execute jobs as they come
     */
    public function listen(): void;

    /**
     * @param int|string $id A message id
     *
     * @throws InvalidArgumentException when there is no such id in the adapter
     *
     * @return JobStatus
     */
    public function status(string|int $id): JobStatus;

    public function withAdapter(AdapterInterface $adapter): self;

    public function getChannel(): string;
}
