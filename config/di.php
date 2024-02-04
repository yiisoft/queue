<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Yiisoft\Config\ConfigInterface;
use Yiisoft\Definitions\Reference;
use Yiisoft\EventDispatcher\Dispatcher\Dispatcher;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Injector\Injector;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Adapter\SynchronousAdapter;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Cli\SignalLoop;
use Yiisoft\Queue\Cli\SimpleLoop;
use Yiisoft\Queue\Message\JsonMessageSerializer;
use Yiisoft\Queue\Message\MessageSerializerInterface;
use Yiisoft\Queue\Middleware\MiddlewareDispatcher;
use Yiisoft\Queue\Middleware\MiddlewareFactory;
use Yiisoft\Queue\Middleware\MiddlewareFactoryInterface;
use Yiisoft\Queue\Queue;
use Yiisoft\Queue\QueueFactory;
use Yiisoft\Queue\QueueFactoryInterface;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Worker\Worker as QueueWorker;
use Yiisoft\Queue\Worker\WorkerInterface;
use Yiisoft\Yii\Event\ListenerCollectionFactory;

/* @var array $params */

return [
    QueueWorker::class => [
        'class' => QueueWorker::class,
        '__construct()' => [
            'eventDispatcher' => Reference::to('queue.dispatcher'),
        ],
    ],
    WorkerInterface::class => QueueWorker::class,
    LoopInterface::class => static function (ContainerInterface $container): LoopInterface {
        return $container->get(
            extension_loaded('pcntl')
                ? SignalLoop::class
                : SimpleLoop::class
        );
    },
    'queue.middlewareDispatcher.push' => static function (Injector $injector) use ($params) {
        return $injector->make(
            MiddlewareDispatcher::class,
            ['middlewareDefinitions' => $params['yiisoft/queue']['middlewares-push']]
        );
    },
    Queue::class => [
        '__construct()' => [
            'adapter' => Reference::to(AdapterInterface::class),
            'pushMiddlewareDispatcher' => Reference::to('queue.middlewareDispatcher.push'),
        ],
    ],
    QueueFactoryInterface::class => QueueFactory::class,
    QueueFactory::class => [
        '__construct()' => ['channelConfiguration' => $params['yiisoft/queue']['channel-definitions']],
    ],
    AdapterInterface::class => SynchronousAdapter::class,

    QueueInterface::class => Queue::class,
    MessageSerializerInterface::class => JsonMessageSerializer::class,
    MiddlewareFactoryInterface::class => MiddlewareFactory::class,

    'queue.dispatcher' => static function (ConfigInterface $config, ListenerCollectionFactory $factory) {
        $listeners = $factory->create($config->get('queue'));

        $provider = new Provider($listeners);

        return new Dispatcher($provider);
    },
];
