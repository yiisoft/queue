<?php

declare(strict_types=1);

use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Command\ListenAllCommand;
use Yiisoft\Queue\Command\ListenCommand;
use Yiisoft\Queue\Command\RunCommand;
use Yiisoft\Queue\Debug\QueueCollector;
use Yiisoft\Queue\Debug\QueueProviderInterfaceProxy;
use Yiisoft\Queue\Debug\QueueWorkerInterfaceProxy;
use Yiisoft\Queue\Provider\QueueProviderInterface;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Worker\WorkerInterface;

return [
    'yiisoft/yii-console' => [
        'commands' => [
            'queue:run' => RunCommand::class,
            'queue:listen' => ListenCommand::class,
            'queue:listen:all' => ListenAllCommand::class,
        ],
    ],
    'yiisoft/queue' => [
        'handlers' => [],
        'channels' => [
            QueueInterface::DEFAULT_CHANNEL_NAME => AdapterInterface::class,
        ],
        'middlewares-push' => [],
        'middlewares-consume' => [],
        'middlewares-fail' => [],
    ],
    'yiisoft/yii-debug' => [
        'collectors' => [
            QueueCollector::class,
        ],
        'trackedServices' => [
            QueueProviderInterface::class => [QueueProviderInterfaceProxy::class, QueueCollector::class],
            WorkerInterface::class => [QueueWorkerInterfaceProxy::class, QueueCollector::class],
        ],
    ],
];
