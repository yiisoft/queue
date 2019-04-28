<?php

$config = [
    'app' => [
        '__class' => \Yiisoft\Yii\Console\Application::class,
        'id'          => 'yii2-queue-app',
        'basePath'    => dirname(__DIR__),
        'runtimePath' => dirname(dirname(__DIR__)).'/runtime',
        'bootstrap'   => [
            'mysqlQueue',
            'sqliteQueue',
            'pgsqlQueue',
            'interopQueue',
        ],
    ],
    'request' => [
        '__class' => \Yiisoft\Yii\Console\Request::class,
        'cookieValidationKey' => new \Yiisoft\Arrays\UnsetArrayValue(),
        'scriptFile' => dirname(__DIR__, 2) . '/yii',
        'scriptUrl' =>  new \Yiisoft\Arrays\UnsetArrayValue(),
    ],
    'response' => [
        '__class' => \Yiisoft\Yii\Console\Response::class,
        'formatters' =>  new \Yiisoft\Arrays\UnsetArrayValue(),
    ],

    'aliases' => [
        '@runtime' => dirname(__DIR__, 2) . '/runtime',
    ],

    'syncQueue' => [
        '__class' => \Yiisoft\Yii\Queue\Drivers\Sync\Queue::class,
    ],
    \Yiisoft\Mutex\Mutex::class => \yii\di\Reference::to('mutex-file'),
    'mutex-file' => [
        '__class' => \Yiisoft\Mutex\FileMutex::class,
        '__construct()' => [
            'mutexPath' => dirname(__DIR__, 2) . '/runtime/mutex',
        ],
    ],
    'mysql' => [
        '__class' => \yii\db\Connection::class,
        'dsn'   => sprintf(
            'mysql:host=%s;dbname=%s',
            getenv('MYSQL_HOST') ?: 'localhost',
            getenv('MYSQL_DATABASE') ?: 'yii2_queue_test'
        ),
        'username'   => getenv('MYSQL_USER') ?: 'root',
        'password'   => getenv('MYSQL_PASSWORD') ?: '',
        'charset'    => 'utf8',
        'attributes' => [
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET sql_mode = "STRICT_ALL_TABLES"',
        ],
    ],
    'mysqlQueue' => [
        '__class' => \Yiisoft\Yii\Queue\Drivers\Db\Queue::class,
        '__construct()' => [
            'serializer' => \yii\di\Reference::to(\Yiisoft\Yii\Queue\Serializers\SerializerInterface::class),
            'db' => \yii\di\Reference::to('mysql')
        ]
        /*'db'    => 'mysql',
        'mutex' => [
            '__class' => \Yiisoft\Mutex\MysqlMutex::class,
            'db'    => 'mysql',
        ],*/
    ],
    'sqlite' => [
        '__class' => \yii\db\Connection::class,
        'dsn'   => 'sqlite:@runtime/yii2_queue_test.db',
    ],
    'sqliteQueue' => [
        '__class' => \Yiisoft\Yii\Queue\Drivers\Db\Queue::class,
        '__construct()' => [
            'serializer' => \yii\di\Reference::to(\Yiisoft\Yii\Queue\Serializers\SerializerInterface::class),
            'db' => \yii\di\Reference::to('sqlite')
        ]
    ],
    'pgsql' => [
        '__class' => \yii\db\Connection::class,
        'dsn'   => sprintf(
            'pgsql:host=%s;dbname=%s',
            getenv('POSTGRES_HOST') ?: 'localhost',
            getenv('POSTGRES_DB') ?: 'yii2_queue_test'
        ),
        'username' => getenv('POSTGRES_USER') ?: 'postgres',
        'password' => getenv('POSTGRES_PASSWORD') ?: '',
        'charset'  => 'utf8',
    ],
    'pgsqlQueue' => [
        '__class' => \Yiisoft\Yii\Queue\Drivers\Db\Queue::class,
        '__construct()' => [
            'serializer' => \yii\di\Reference::to(\Yiisoft\Yii\Queue\Serializers\SerializerInterface::class),
            'db' => \yii\di\Reference::to('pgsql')
        ],
        'mutexTimeout' => 0,
    ],
    'interopQueue' => [
        '__class'        => \Yiisoft\Yii\Queue\Drivers\Interop\Queue::class,
        'host'         => getenv('RABBITMQ_HOST') ?: 'localhost',
        'user'         => getenv('RABBITMQ_USER') ?: 'guest',
        'password'     => getenv('RABBITMQ_PASSWORD') ?: 'guest',
        'queueName'    => 'queue-interop',
        'exchangeName' => 'exchange-interop',
    ],

];

if (defined('GEARMAN_SUCCESS')) {
    $config['bootstrap'][] = 'gearmanQueue';
    $config['components']['gearmanQueue'] = [
        '__class' => \Yiisoft\Yii\Queue\gearman\Queue::class,
        'host'  => getenv('GEARMAN_HOST') ?: 'localhost',
    ];
}

if (getenv('AWS_SQS_ENABLED')) {
    $config['bootstrap'][] = 'sqsQueue';
    $config['components']['sqsQueue'] = [
        '__class'  => \Yiisoft\Yii\Queue\sqs\Queue::class,
        'url'    => getenv('AWS_SQS_URL'),
        'key'    => getenv('AWS_KEY'),
        'secret' => getenv('AWS_SECRET'),
        'region' => getenv('AWS_REGION'),
    ];
}

return $config;
