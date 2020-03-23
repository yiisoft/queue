<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue;

use InvalidArgumentException;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Job\DelayableJobInterface;
use Yiisoft\Yii\Queue\Job\JobInterface;
use Yiisoft\Yii\Queue\Job\PrioritisedJobInterface;
use Yiisoft\Yii\Queue\Job\RetryableJobInterface;

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
     * @param JobInterface $job
     *
     * @return MessageInterface
     */
    public function push(JobInterface $job): MessageInterface;

    /**
     * Listen to the queue and pass messages to the given handler as they come
     *
     * @param callable $handler The handler which will execute jobs
     */
    public function subscribe(callable $handler): void;

    /**
     * Takes care about supporting {@see DelayableJobInterface}, {@see PrioritisedJobInterface}
     * and {@see RetryableJobInterface} and any other conditions.
     *
     * @param JobInterface $job
     *
     * @return bool
     */
    public function canPush(JobInterface $job): bool;
}
