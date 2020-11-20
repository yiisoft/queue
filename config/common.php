<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Cli\SignalLoop;
use Yiisoft\Yii\Queue\Cli\SimpleLoop;
use Yiisoft\Yii\Queue\Worker\Worker as QueueWorker;
use Yiisoft\Yii\Queue\Worker\WorkerInterface;

/* @var array $params */

return [
    QueueWorker::class => [
        '__class' => QueueWorker::class,
        '__construct()' => [$params['yiisoft/yii-queue']['handlers']],
    ],
    WorkerInterface::class => QueueWorker::class,
    LoopInterface::class => static function (ContainerInterface $container) {
        return extension_loaded('pcntl')
            ? $container->get(SignalLoop::class)
            : $container->get(SimpleLoop::class);
    },
];
