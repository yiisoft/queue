<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\FailureStrategy;

use Yiisoft\Yii\Queue\Event\JobFailure;

final class FailedJobsHandler
{
    private DispatcherFactory $factory;

    public function __construct(DispatcherFactory $factory)
    {
        $this->factory = $factory;
    }

    public function handle(JobFailure $event): void
    {
        $dispatcher = $this->factory->get($event->getMessage()->getPayloadName());
        $dispatcher->handle($event);
    }
}
