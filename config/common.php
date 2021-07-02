<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Yiisoft\Yii\Queue\BehaviorApplier;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Cli\SignalLoop;
use Yiisoft\Yii\Queue\Cli\SimpleLoop;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\QueueFactory;
use Yiisoft\Yii\Queue\QueueFactoryInterface;
use Yiisoft\Yii\Queue\QueueInterface;
use Yiisoft\Yii\Queue\Worker\Worker as QueueWorker;
use Yiisoft\Yii\Queue\Worker\WorkerInterface;

/* @var array $params */

return [
    QueueWorker::class => [
        'class' => QueueWorker::class,
        '__construct()' => [$params['yiisoft/yii-queue']['handlers']],
    ],
    WorkerInterface::class => QueueWorker::class,
    LoopInterface::class => static function (ContainerInterface $container): LoopInterface {
        return extension_loaded('pcntl')
            ? $container->get(SignalLoop::class)
            : $container->get(SimpleLoop::class);
    },
    QueueFactoryInterface::class => QueueFactory::class,
    QueueFactory::class => [
        '__construct()' => ['channelConfiguration' => $params['yiisoft/yii-queue']['channel-definitions']],
    ],
    QueueInterface::class => Queue::class,
    BehaviorApplier::class => [
        '__construct' => ['behaviorMap' => $params['yiisoft/yii-queue']['behavior-appliers']],
    ],
];
