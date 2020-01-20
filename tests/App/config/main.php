<?php

use Yiisoft\Factory\Definitions\Reference;
use Yiisoft\Mutex\File\FileMutex;
use Yiisoft\Mutex\Mutex as YiiMutex;
use Yiisoft\Yii\Queue\Drivers\Interop\Queue as InteropQueue;
use Yiisoft\Yii\Queue\Drivers\Sync\Queue as SyncQueue;

return [
    'aliases' => [
        '@runtime' => dirname(__DIR__, 2) . '/runtime',
    ],

    'syncQueue' => [
        '__class' => SyncQueue::class,
    ],
    YiiMutex::class => Reference::to('mutex-file'),
    'mutex-file' => [
        '__class' => FileMutex::class,
        '__construct()' => [
            'mutexPath' => dirname(__DIR__, 2) . '/runtime/mutex',
        ],
    ],
    'interopQueue' => [
        '__class' => InteropQueue::class,
        'host' => getenv('RABBITMQ_HOST') ?: 'localhost',
        'user' => getenv('RABBITMQ_USER') ?: 'guest',
        'password' => getenv('RABBITMQ_PASSWORD') ?: 'guest',
        'queueName' => 'queue-interop',
        'exchangeName' => 'exchange-interop',
    ],
];
