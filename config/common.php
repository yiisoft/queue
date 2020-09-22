<?php

use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Cli\SignalLoop;
use Yiisoft\Yii\Queue\Worker\Worker as QueueWorker;
use Yiisoft\Yii\Queue\Worker\WorkerInterface;

return [
    QueueWorker::class => [
        '__class' => QueueWorker::class,
        '__construct()' => [$params['yiisoft/yii-queue']['handlers']],
    ],
    WorkerInterface::class => QueueWorker::class,
    LoopInterface::class => SignalLoop::class,
];
