<?php

declare(strict_types=1);

use Yiisoft\Queue\Command\ListenCommand;
use Yiisoft\Queue\Command\RunCommand;
use Yiisoft\Queue\Debug\QueueCollector;
use Yiisoft\Queue\Debug\QueueFactoryInterfaceProxy;
use Yiisoft\Queue\Debug\QueueWorkerInterfaceProxy;
use Yiisoft\Queue\QueueFactoryInterface;
use Yiisoft\Queue\Worker\WorkerInterface;

return [
    'yiisoft/yii-console' => [
        'commands' => [
            'queue:run' => RunCommand::class,
            'queue:listen' => ListenCommand::class,
        ],
    ],
    'yiisoft/queue' => [
        'handlers' => [],
        'channel-definitions' => [],
        'middlewares-push' => [],
        'middlewares-consume' => [],
        'middlewares-fail' => [],
    ],
    'yiisoft/yii-debug' => [
        'collectors' => [
            QueueCollector::class,
        ],
        'trackedServices' => [
            QueueFactoryInterface::class => [QueueFactoryInterfaceProxy::class, QueueCollector::class],
            WorkerInterface::class => [QueueWorkerInterfaceProxy::class, QueueCollector::class],
        ],
    ],
];
