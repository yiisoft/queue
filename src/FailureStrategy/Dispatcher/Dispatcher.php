<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\FailureStrategy\Dispatcher;

use Yiisoft\Yii\Queue\Event\JobFailure;

class Dispatcher implements DispatcherInterface
{
    private PipelineInterface $pipeline;

    public function __construct(PipelineInterface $pipeline)
    {
        $this->pipeline = $pipeline;
    }

    public function handle(JobFailure $event): void
    {
        if ($this->pipeline->handle($event->getMessage())) {
            $event->preventThrowing();
        }
    }
}
