<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue;

use InvalidArgumentException;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Exception\BehaviorNotSupportedException;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Middleware\Push\MiddlewarePushInterface;

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
     * @throws BehaviorNotSupportedException
     *
     * @return MessageInterface
     */
    public function push(MessageInterface $message, MiddlewarePushInterface|callable|array|string ...$middlewareDefinitions): MessageInterface;

    /**
     * Execute all existing jobs and exit
     *
     * @param int $max
     */
    public function run(int $max = 0): void;

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
    public function status(string $id): JobStatus;

    public function withAdapter(AdapterInterface $adapter): self;

    public function getChannelName(): string;
}
