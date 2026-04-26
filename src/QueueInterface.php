<?php

declare(strict_types=1);

namespace Yiisoft\Queue;

use InvalidArgumentException;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface;

interface QueueInterface
{
    /**
     * Pushes a message into the queue.
     *
     * @param array|callable|MiddlewarePushInterface|string ...$middlewareDefinitions
     * @return MessageInterface
     */
    public function push(MessageInterface $message, MiddlewarePushInterface|callable|array|string ...$middlewareDefinitions): MessageInterface;

    /**
     * Handle all existing messages and exit
     *
     * @return int Number of messages processed.
     */
    public function run(int $max = 0): int;

    /**
     * Listen to the queue and handle messages as they come
     */
    public function listen(): void;

    /**
     * @param int|string $id A message ID.
     *
     * @return MessageStatus
     */
    public function status(string|int $id): MessageStatus;

    /**
     * Returns the logical name of the queue.
     */
    public function getName(): string;
}
