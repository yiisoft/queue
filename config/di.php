<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Cli\SignalLoop;
use Yiisoft\Queue\Cli\SimpleLoop;
use Yiisoft\Queue\Message\ClassResolver\ArrayMessageClassResolver;
use Yiisoft\Queue\Message\ClassResolver\MessageClassResolverInterface;
use Yiisoft\Queue\Message\Serializer\JsonMessageEncoder;
use Yiisoft\Queue\Message\Serializer\MessageEncoderInterface;
use Yiisoft\Queue\Message\Serializer\MessageSerializer;
use Yiisoft\Queue\Message\Serializer\MessageSerializerInterface;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareFactory;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareFactoryInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareFactory;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareFactoryInterface;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareConfig;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareFactory;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareFactoryInterface;
use Yiisoft\Queue\Worker\Worker as QueueWorker;
use Yiisoft\Queue\Worker\WorkerInterface;

/* @var array $params */

return [
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
    PushMiddlewareFactoryInterface::class => PushMiddlewareFactory::class,
    ConsumeMiddlewareFactoryInterface::class => ConsumeMiddlewareFactory::class,
    FailureMiddlewareFactoryInterface::class => FailureMiddlewareFactory::class,
    PushMiddlewareConfig::class => [
        '__construct()' => ['commonMiddlewareDefinitions' => $params['yiisoft/queue']['middlewares-push']],
    ],
    ConsumeMiddlewareDispatcher::class => [
        '__construct()' => ['middlewareDefinitions' => $params['yiisoft/queue']['middlewares-consume']],
    ],
    FailureMiddlewareDispatcher::class => [
        '__construct()' => ['middlewareDefinitions' => $params['yiisoft/queue']['middlewares-fail']],
    ],
    MessageEncoderInterface::class => JsonMessageEncoder::class,
    MessageSerializerInterface::class => MessageSerializer::class,
    MessageClassResolverInterface::class => [
        'class' => ArrayMessageClassResolver::class,
        '__construct()' => [
            'map' => $params['yiisoft/queue']['message-class-map'],
        ],
    ],
];
