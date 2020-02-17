<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue;

use Yiisoft\Yii\Queue\Jobs\DelayableJobInterface;
use Yiisoft\Yii\Queue\Jobs\JobInterface;
use Yiisoft\Yii\Queue\Jobs\PrioritisedJobInterface;
use Yiisoft\Yii\Queue\Jobs\RetryableJobInterface;

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
     * @return int status code
     */
    public function status(string $id): int;

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
