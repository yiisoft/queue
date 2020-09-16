<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Driver;

use InvalidArgumentException;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Payload\AttemptsRestrictedPayloadInterface;
use Yiisoft\Yii\Queue\Payload\DelayablePayloadInterface;
use Yiisoft\Yii\Queue\Payload\PrioritisedPayloadInterface;

interface DriverInterface
{
    /**
     * Returns the first message from the queue if it exists (null otherwise)
     *
     * @return MessageInterface|null
     */
    public function nextMessage(): ?MessageInterface;

    /**
     * Returns status code of a message with the given id.
     *
     * @param string $id of a job message
     *
     * @return JobStatus
     *
     * @throws InvalidArgumentException when there is no such id in the driver
     */
    public function status(string $id): JobStatus;

    /**
     * Pushing a job to the queue
     *
     * @param MessageInterface $message
     *
     * @return string Id of a pushed message
     */
    public function push(MessageInterface $message): ?string;

    /**
     * Listen to the queue and pass messages to the given handler as they come
     *
     * @param callable $handler The handler which will execute jobs
     */
    public function subscribe(callable $handler): void;

    /**
     * Takes care about supporting {@see DelayablePayloadInterface}, {@see PrioritisedPayloadInterface}
     * and {@see AttemptsRestrictedPayloadInterface} and any other conditions.
     *
     * @param MessageInterface $message
     *
     * @return bool
     */
    public function canPush(MessageInterface $message): bool;
}
