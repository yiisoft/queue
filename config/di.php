<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Cli\SignalLoop;
use Yiisoft\Queue\Cli\SimpleLoop;
use Yiisoft\Queue\Command\ListenAllCommand;
use Yiisoft\Queue\Command\RunCommand;
use Yiisoft\Queue\Message\JsonMessageSerializer;
use Yiisoft\Queue\Message\MessageSerializerInterface;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\MiddlewareFactoryConsume;
use Yiisoft\Queue\Middleware\Consume\MiddlewareFactoryConsumeInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\FailureHandling\MiddlewareFactoryFailure;
use Yiisoft\Queue\Middleware\FailureHandling\MiddlewareFactoryFailureInterface;
use Yiisoft\Queue\Middleware\Push\MiddlewareFactoryPush;
use Yiisoft\Queue\Middleware\Push\MiddlewareFactoryPushInterface;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareDispatcher;
use Yiisoft\Queue\Provider\AdapterFactoryQueueProvider;
use Yiisoft\Queue\Provider\QueueProviderInterface;
use Yiisoft\Queue\Queue;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Worker\Worker as QueueWorker;
use Yiisoft\Queue\Worker\WorkerInterface;

/* @var array $params */

return [
    AdapterFactoryQueueProvider::class => [
        '__construct()' => [
            'definitions' => $params['yiisoft/queue']['channels'],
        ],
    ],
    QueueProviderInterface::class => AdapterFactoryQueueProvider::class,
    QueueWorker::class => [
        'class' => QueueWorker::class,
        '__construct()' => [$params['yiisoft/queue']['handlers']],
    ],
    WorkerInterface::class => QueueWorker::class,
    LoopInterface::class => static function (ContainerInterface $container): LoopInterface {
        return \extension_loaded('pcntl')
            ? $container->get(SignalLoop::class)
            : $container->get(SimpleLoop::class);
    },
    QueueInterface::class => Queue::class,
    MiddlewareFactoryPushInterface::class => MiddlewareFactoryPush::class,
    MiddlewareFactoryConsumeInterface::class => MiddlewareFactoryConsume::class,
    MiddlewareFactoryFailureInterface::class => MiddlewareFactoryFailure::class,
    PushMiddlewareDispatcher::class => [
        '__construct()' => ['middlewareDefinitions' => $params['yiisoft/queue']['middlewares-push']],
    ],
    ConsumeMiddlewareDispatcher::class => [
        '__construct()' => ['middlewareDefinitions' => $params['yiisoft/queue']['middlewares-consume']],
    ],
    FailureMiddlewareDispatcher::class => [
        '__construct()' => ['middlewareDefinitions' => $params['yiisoft/queue']['middlewares-fail']],
    ],
    MessageSerializerInterface::class => JsonMessageSerializer::class,
    RunCommand::class => [
        '__construct()' => [
            'channels' => array_keys($params['yiisoft/queue']['channels']),
        ],
    ],
    ListenAllCommand::class => [
        '__construct()' => [
            'channels' => array_keys($params['yiisoft/queue']['channels']),
        ],
    ],
];
