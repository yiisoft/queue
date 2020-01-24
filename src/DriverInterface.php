<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue;

use Yiisoft\Yii\Queue\Jobs\JobInterface;

interface DriverInterface
{
    public function nextMessage(): ?MessageInterface;

    /**
     * @param string $id of a job message
     *
     * @return int status code
     */
    public function status(string $id): int;

    /**
     * @param JobInterface $job
     * @param int $ttr time to reserve in seconds
     * @param int $delay
     * @param int|null $priority
     *
     * @return string id of a job message
     */
    public function pushMessage(JobInterface $job, int $ttr, int $delay, ?int $priority): string;

    /**
     * Listen to the queue and execute messages as they come
     *
     * @param callable $handler The handler which will execute jobs
     */
    public function subscribe(callable $handler): void;
}
