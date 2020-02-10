<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Events;

use Yiisoft\Yii\Queue\Jobs\JobInterface;
use Yiisoft\Yii\Queue\Queue;

class BeforePush implements BeforePushInterface
{
    protected bool $stop = false;
    private Queue $queue;
    private JobInterface $job;

    public function __construct(Queue $queue, JobInterface $job)
    {
        $this->queue = $queue;
        $this->job = $job;
    }

    public function getJob(): JobInterface
    {
        return $this->job;
    }

    public function getQueue(): Queue
    {
        return $this->queue;
    }

    /**
     * @inheritDoc
     */
    public function isPropagationStopped(): bool
    {
        return $this->stop;
    }

    public function stopPropagation(): void
    {
        $this->stop = true;
    }
}
