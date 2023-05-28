<?php

declare(strict_types=1);

use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Command\ListenAllCommand;
use Yiisoft\Yii\Queue\Command\ListenCommand;
use Yiisoft\Yii\Queue\Command\RunCommand;
use Yiisoft\Yii\Queue\Debug\QueueCollector;
use Yiisoft\Yii\Queue\Debug\QueueFactoryInterfaceProxy;
use Yiisoft\Yii\Queue\Debug\QueueWorkerInterfaceProxy;
use Yiisoft\Yii\Queue\QueueFactoryInterface;
use Yiisoft\Yii\Queue\Worker\WorkerInterface;

return [
    'yiisoft/yii-console' => [
        'commands' => [
            'queue/run' => RunCommand::class,
            'queue/listen' => ListenCommand::class,
            'queue:listen:all' => ListenAllCommand::class,
        ],
    ],
    'yiisoft/yii-queue' => [
        'handlers' => [],
        'channel-definitions' => [
            QueueFactoryInterface::DEFAULT_CHANNEL_NAME => AdapterInterface::class,
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
            QueueFactoryInterface::class => [QueueFactoryInterfaceProxy::class, QueueCollector::class],
            WorkerInterface::class => [QueueWorkerInterfaceProxy::class, QueueCollector::class],
        ],
    ],
];
