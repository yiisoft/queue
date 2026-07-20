<?php

declare(strict_types=1);

namespace Yiisoft\Queue;

interface QueueConsumerInterface
{
    /**
     * Handle all existing messages and exit.
     *
     * @return int Number of messages processed.
     */
    public function run(int $max = 0): int;

    /**
     * Listen to the queue and handle messages as they come.
     */
    public function listen(): void;
}
