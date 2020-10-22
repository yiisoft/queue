<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\FailureStrategy;

use Yiisoft\Yii\Queue\Event\JobFailure;
use Yiisoft\Yii\Queue\FailureStrategy\Dispatcher\DispatcherFactoryInterface;

final class FailedJobsHandler
{
    private DispatcherFactoryInterface $factory;

    public function __construct(DispatcherFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    public function handle(JobFailure $event): void
    {
        $dispatcher = $this->factory->get($event->getMessage()->getPayloadName());
        $dispatcher->handle($event);
    }
}
