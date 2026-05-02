<?php

declare(strict_types=1);

namespace Yiisoft\Queue;

use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface;

interface QueueInterface
{
    /**
     * Pushes a message into the queue.
     *
     * @param MessageInterface $message The message to push.
     *
     * @return MessageInterface The pushed message, possibly enriched with metadata such as an assigned ID.
     */
    public function push(MessageInterface $message): MessageInterface;

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

    /**
     * Creates a new instance with the specified middlewares. All the existing middlewares are replaced.
     *
     * @param MiddlewarePushInterface|callable|array|string ...$middlewareDefinitions The middleware definitions.
     */
    public function withMiddlewares(MiddlewarePushInterface|callable|array|string ...$middlewareDefinitions): self;

    /**
     * Creates a new instance with the specified middlewares added after the existing ones.
     *
     * @param MiddlewarePushInterface|callable|array|string ...$middlewareDefinitions The middleware definitions.
     */
    public function withMiddlewaresAdded(MiddlewarePushInterface|callable|array|string ...$middlewareDefinitions): self;
}
