<?php

use Yiisoft\Yii\Queue\Event\JobFailure;
use Yiisoft\Yii\Queue\FailureStrategy\FailedJobsHandler;

return [
    JobFailure::class => [
        [FailedJobsHandler::class, 'handle'],
    ],
];
