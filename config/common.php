<?php

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Yiisoft\EventDispatcher\Dispatcher\Dispatcher;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Serializer\JsonSerializer;
use Yiisoft\Serializer\SerializerInterface;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Cli\SignalLoop;
use Yiisoft\Yii\Queue\Worker\Worker as QueueWorker;
use Yiisoft\Yii\Queue\Worker\WorkerInterface;

return [
    EventDispatcherInterface::class => Dispatcher::class,
    WorkerInterface::class => QueueWorker::class,
    ListenerProviderInterface::class => Provider::class,
    ContainerInterface::class => fn(ContainerInterface $container) => $container,
    LoopInterface::class => SignalLoop::class,
    SerializerInterface::class => JsonSerializer::class,
];
