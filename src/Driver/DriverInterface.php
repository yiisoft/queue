<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Driver;

use InvalidArgumentException;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Payload\DelayablePayloadInterface;
use Yiisoft\Yii\Queue\Payload\PayloadInterface;
use Yiisoft\Yii\Queue\Payload\PrioritisedPayloadInterface;
use Yiisoft\Yii\Queue\Payload\RetryablePayloadInterface;
use Yiisoft\Yii\Queue\MessageInterface;

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
     * @param PayloadInterface $payload
     *
     * @return MessageInterface
     */
    public function push(PayloadInterface $payload): MessageInterface;

    /**
     * Listen to the queue and pass messages to the given handler as they come
     *
     * @param callable $handler The handler which will execute jobs
     */
    public function subscribe(callable $handler): void;

    /**
     * Takes care about supporting {@see DelayablePayloadInterface}, {@see PrioritisedPayloadInterface}
     * and {@see RetryablePayloadInterface} and any other conditions.
     *
     * @param PayloadInterface $payload
     *
     * @return bool
     */
    public function canPush(PayloadInterface $payload): bool;
}
