<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Adapter;

use InvalidArgumentException;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Exception\BehaviorNotSupportedException;
use Yiisoft\Yii\Queue\Message\MessageInterface;

interface AdapterInterface
{
    /**
     * Returns the first message from the queue if it exists (null otherwise).
     */
    public function runExisting(callable $callback): void;

    /**
     * Returns status code of a message with the given id.
     *
     * @param string $id ID of a job message.
     *
     * @throws InvalidArgumentException When there is no such id in the adapter.
     */
    public function status(string $id): JobStatus;

    /**
     * Pushing a message to the queue. Adapter sets message ID if available.
     *
     * @throws BehaviorNotSupportedException Adapter may throw exception when it does not support all the attached behaviors.
     */
    public function push(MessageInterface $message): void;

    /**
     * Listen to the queue and pass messages to the given handler as they come.
     *
     * @param callable $handler The handler which will execute jobs.
     */
    public function subscribe(callable $handler): void;

    public function withChannel(string $channel): self;
}
