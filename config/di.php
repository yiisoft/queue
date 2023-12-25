<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Cli\SignalLoop;
use Yiisoft\Yii\Queue\Cli\SimpleLoop;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Yii\Queue\Middleware\Consume\MiddlewareFactoryConsume;
use Yiisoft\Yii\Queue\Middleware\Consume\MiddlewareFactoryConsumeInterface;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\MiddlewareFactoryFailure;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\MiddlewareFactoryFailureInterface;
use Yiisoft\Yii\Queue\Middleware\Push\MiddlewareFactoryPush;
use Yiisoft\Yii\Queue\Middleware\Push\MiddlewareFactoryPushInterface;
use Yiisoft\Yii\Queue\Middleware\Push\PushMiddlewareDispatcher;
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
        return $container->get(extension_loaded('pcntl')
            ? SignalLoop::class
            : SimpleLoop::class
        );
    },
    QueueFactoryInterface::class => QueueFactory::class,
    QueueFactory::class => [
        '__construct()' => ['channelConfiguration' => $params['yiisoft/yii-queue']['channel-definitions']],
    ],
    QueueInterface::class => Queue::class,
    MiddlewareFactoryPushInterface::class => MiddlewareFactoryPush::class,
    MiddlewareFactoryConsumeInterface::class => MiddlewareFactoryConsume::class,
    MiddlewareFactoryFailureInterface::class => MiddlewareFactoryFailure::class,
    PushMiddlewareDispatcher::class => [
        '__construct()' => ['middlewareDefinitions' => $params['yiisoft/yii-queue']['middlewares-push']],
    ],
    ConsumeMiddlewareDispatcher::class => [
        '__construct()' => ['middlewareDefinitions' => $params['yiisoft/yii-queue']['middlewares-consume']],
    ],
    FailureMiddlewareDispatcher::class => [
        '__construct()' => ['middlewareDefinitions' => $params['yiisoft/yii-queue']['middlewares-fail']],
    ],
];
