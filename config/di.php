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
use Yiisoft\Queue\Middleware\Worker\WorkerMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Worker\WorkerMiddlewareFactory;
use Yiisoft\Queue\Middleware\Worker\WorkerMiddlewareFactoryInterface;
use Yiisoft\Queue\Provider\QueueProducerStatusProvider;
use Yiisoft\Queue\Provider\QueueProducerStatusProviderInterface;
use Yiisoft\Queue\Message\Handler\HandlerResolver;
use Yiisoft\Queue\Debug\Middleware\PushDebugMiddleware;
use Yiisoft\Queue\Debug\Middleware\WorkerDebugMiddleware;
use Yiisoft\Queue\Worker\Worker;
use Yiisoft\Yii\Debug\Collector\SummaryCollectorInterface;

/* @var array $params */

$debugEnabled = (bool) ($params['yiisoft/yii-debug']['enabled'] ?? false)
    && interface_exists(SummaryCollectorInterface::class);

$pushMiddlewareDefinitions = array_merge(
    $debugEnabled ? [PushDebugMiddleware::class] : [],
    $params['yiisoft/queue']['middlewares-push'],
);
$workerMiddlewareDefinitions = array_merge(
    $debugEnabled ? [WorkerDebugMiddleware::class] : [],
    $params['yiisoft/queue']['middlewares-worker'] ?? [],
);

$definitions = [
    HandlerResolver::class => [
        '__construct()' => [$params['yiisoft/queue']['handlers']],
    ],
    Worker::class => Worker::class,
    QueueProducerStatusProviderInterface::class => QueueProducerStatusProvider::class,
    LoopInterface::class => static function (ContainerInterface $container): LoopInterface {
        return \extension_loaded('pcntl')
            ? $container->get(SignalLoop::class)
            : $container->get(SimpleLoop::class);
    },
    PushMiddlewareFactoryInterface::class => PushMiddlewareFactory::class,
    ConsumeMiddlewareFactoryInterface::class => ConsumeMiddlewareFactory::class,
    FailureMiddlewareFactoryInterface::class => FailureMiddlewareFactory::class,
    PushMiddlewareConfig::class => [
        '__construct()' => ['commonMiddlewareDefinitions' => $pushMiddlewareDefinitions],
    ],
    WorkerMiddlewareFactoryInterface::class => WorkerMiddlewareFactory::class,
    WorkerMiddlewareDispatcher::class => [
        '__construct()' => ['middlewareDefinitions' => $workerMiddlewareDefinitions],
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
            'map' => $params['yiisoft/queue']['messages'],
        ],
    ],
];

return $definitions;
