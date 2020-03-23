<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue;

use Yiisoft\Yii\Queue\Job\JobInterface;

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
}
