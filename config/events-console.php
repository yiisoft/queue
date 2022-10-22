<?php

declare(strict_types=1);

use Yiisoft\Yii\Queue\Event\JobFailure;
use Yiisoft\Yii\Queue\FailureStrategy\FailedJobsHandler;

return [
    JobFailure::class => [
        [FailedJobsHandler::class, 'handle'],
    ],
];
