<?php

use Yiisoft\Yii\Queue\Event\JobFailure;
use Yiisoft\Yii\Queue\Queue;

return [
    JobFailure::class => [
        [Queue::class, 'jobRetry'],
    ],
];
