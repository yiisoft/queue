<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue;

interface QueueDriverInterface
{
    /**
     * @param string $id of a job message
     *
     * @return int status code
     */
    public function status(string $id): int;

    /**
     * @param string $message
     * @param int $ttr time to reserve in seconds
     * @param int $delay
     * @param int|null $priority
     *
     * @return string id of a job message
     */
    public function pushMessage(string $message, int $ttr, int $delay, ?int $priority): string;
}
