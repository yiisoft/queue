<?php

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Yiisoft\EventDispatcher\Dispatcher;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Yii\Queue\Workers\Worker as QueueWorker;
use Yiisoft\Yii\Queue\Workers\WorkerInterface;

return [
    EventDispatcherInterface::class => Dispatcher::class,
    WorkerInterface::class => QueueWorker::class,
    ListenerProviderInterface::class => Provider::class,
];
