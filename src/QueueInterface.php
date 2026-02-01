<?php

declare(strict_types=1);

namespace Yiisoft\Queue;

use BackedEnum;
use InvalidArgumentException;
use Yiisoft\Queue\Adapter\AdapterInterface;
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

    /**
     * @param AdapterInterface $adapter Adapter to use.
     * @param string|null $queueName Queue name to use.
     *
     * @return static A new queue with the given adapter and queue name.
     */
    public function withAdapter(AdapterInterface $adapter, string|BackedEnum|null $queueName = null): static;

    /**
     * Returns the logical name of the queue.
     */
    public function getName(): string;
}
