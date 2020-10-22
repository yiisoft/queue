<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\FailureStrategy\Dispatcher;

use Yiisoft\Yii\Queue\Event\JobFailure;

interface DispatcherInterface
{
    public function handle(JobFailure $event): void;
}
