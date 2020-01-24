<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue;

interface MessageInterface
{
    /**
     * Returns unique message id
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Returns a job to execute
     *
     * @return JobInterface
     */
    public function getJob(): JobInterface;

    /**
     * Returns time to reserve
     *
     * @return int
     */
    public function getTtr(): int;

    /**
     * Returns current job execution attempt number
     *
     * @return int
     */
    public function getAttempt(): int;
}
