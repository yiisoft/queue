<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Driver;

use InvalidArgumentException;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Exception\BehaviorNotSupportedException;
use Yiisoft\Yii\Queue\Message\MessageInterface;

interface DriverInterface
{
    /**
     * Returns the first message from the queue if it exists (null otherwise).
     *
     * @return MessageInterface|null
     */
    public function nextMessage(): ?MessageInterface;

    /**
     * Returns status code of a message with the given id.
     *
     * @param string $id ID of a job message.
     *
     * @throws InvalidArgumentException When there is no such id in the driver.
     *
     * @return JobStatus
     */
    public function status(string $id): JobStatus;

    /**
     * Pushing a message to the queue. Driver sets message ID if available.
     *
     * @param MessageInterface $message
     *
     * @throws BehaviorNotSupportedException Driver may throw exception when it does not support all the attached behaviors.
     */
    public function push(MessageInterface $message): void;

    /**
     * Listen to the queue and pass messages to the given handler as they come.
     *
     * @param callable $handler The handler which will execute jobs.
     */
    public function subscribe(callable $handler): void;
}
