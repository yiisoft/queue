<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Yiisoft\Definitions\ReferencesArray;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Cli\SignalLoop;
use Yiisoft\Yii\Queue\Cli\SimpleLoop;
use Yiisoft\Yii\Queue\Command\ListenCommand;
use Yiisoft\Yii\Queue\Command\RunCommand;
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

return [
    Application::class => [
        '__construct()' => [
            'name' => 'Yii Queue Tool',
            'version' => '1.0.0',
        ],
        'addCommands()' => [
            ReferencesArray::from(
                [
                    RunCommand::class,
                    ListenCommand::class,
                ],
            ),
        ],
    ],
    QueueWorker::class => [
        'class' => QueueWorker::class,
        '__construct()' => [[]],
    ],
    WorkerInterface::class => QueueWorker::class,
    LoopInterface::class => static function (ContainerInterface $container): LoopInterface {
        return extension_loaded('pcntl')
            ? $container->get(SignalLoop::class)
            : $container->get(SimpleLoop::class);
    },
    QueueFactoryInterface::class => QueueFactory::class,
    QueueFactory::class => [
        '__construct()' => [[]],
    ],
    QueueInterface::class => Queue::class,
    MiddlewareFactoryPushInterface::class => MiddlewareFactoryPush::class,
    MiddlewareFactoryConsumeInterface::class => MiddlewareFactoryConsume::class,
    MiddlewareFactoryFailureInterface::class => MiddlewareFactoryFailure::class,
    PushMiddlewareDispatcher::class => [
        '__construct()' => ['middlewareDefinitions' => []],
    ],
    ConsumeMiddlewareDispatcher::class => [
        '__construct()' => ['middlewareDefinitions' => []],
    ],
    FailureMiddlewareDispatcher::class => [
        '__construct()' => ['middlewareDefinitions' => []],
    ],
];
