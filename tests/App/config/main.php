<?php

use Yiisoft\Db\Connection;
use Yiisoft\Factory\Definitions\Reference;
use Yiisoft\Mutex\File\FileMutex;
use Yiisoft\Mutex\Mutex as YiiMutex;
use Yiisoft\Serializer\SerializerInterface;
use Yiisoft\Yii\Queue\Drivers\Db\Queue as DbQueue;
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
    'mysql' => [
        '__class' => Connection::class,
        'dsn' => sprintf(
            'mysql:host=%s;dbname=%s',
            getenv('MYSQL_HOST') ?: 'localhost',
            getenv('MYSQL_DATABASE') ?: 'yii2_queue_test'
        ),
        'username' => getenv('MYSQL_USER') ?: 'root',
        'password' => getenv('MYSQL_PASSWORD') ?: '',
        'charset' => 'utf8',
        'attributes' => [
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET sql_mode = "STRICT_ALL_TABLES"',
        ],
    ],
    'mysqlQueue' => [
        '__class' => DbQueue::class,
        '__construct()' => [
            'serializer' => Reference::to(SerializerInterface::class),
            'db' => Reference::to('mysql'),
        ]
        /*'db'    => 'mysql',
        'mutex' => [
            '__class' => \Yiisoft\Mutex\MysqlMutex::class,
            'db'    => 'mysql',
        ],*/
    ],
    'sqlite' => [
        '__class' => Connection::class,
        'dsn' => 'sqlite:@runtime/yii2_queue_test.db',
    ],
    'sqliteQueue' => [
        '__class' => DbQueue::class,
        '__construct()' => [
            'serializer' => Reference::to(SerializerInterface::class),
            'db' => Reference::to('sqlite'),
        ],
    ],
    'pgsql' => [
        '__class' => Connection::class,
        'dsn' => sprintf(
            'pgsql:host=%s;dbname=%s',
            getenv('POSTGRES_HOST') ?: 'localhost',
            getenv('POSTGRES_DB') ?: 'yii2_queue_test'
        ),
        'username' => getenv('POSTGRES_USER') ?: 'postgres',
        'password' => getenv('POSTGRES_PASSWORD') ?: '',
        'charset' => 'utf8',
    ],
    'pgsqlQueue' => [
        '__class' => DbQueue::class,
        '__construct()' => [
            'serializer' => Reference::to(SerializerInterface::class),
            'db' => Reference::to('pgsql'),
        ],
        'mutexTimeout' => 0,
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
