<?php

declare(strict_types=1);

namespace Yiisoft\Queue;

use InvalidArgumentException;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Enum\JobStatus;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface;

/**
 * @internal Please don't use this interface. It is only used here to make tests simpler and will be removed
 * after tests refactoring. Use the {@see Queue} class directly instead.
 */
interface QueueInterface
{
    /**
     * Pushes a message into the queue.
     *
     * @param MessageInterface $message
     * @param array|callable|MiddlewarePushInterface|string ...$middlewareDefinitions
     *
     * @return MessageInterface
     */
    public function push(MessageInterface $message, MiddlewarePushInterface|callable|array|string ...$middlewareDefinitions): MessageInterface;

    /**
     * Execute all existing jobs and exit
     *
     * @param int $max
     *
     * @return int How many messages were processed
     */
    public function run(int $max = 0): int;

    /**
     * Listen to the queue and execute jobs as they come
     */
    public function listen(): void;

    /**
     * @param string $id A message id
     *
     * @throws InvalidArgumentException when there is no such id in the adapter
     *
     * @return JobStatus
     */
    public function status(string|int $id): JobStatus;

    public function withAdapter(AdapterInterface $adapter): self;

    public function getChannelName(): string;

    public function withChannelName(string $channel): self;
}
