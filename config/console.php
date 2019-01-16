<?php

use yii\di\Reference;

return [
    'app' => [
        'bootstrap' => [
            'queue' => 'queue',
        ],
    ],
    \yii\queue\serializers\SerializerInterface::class => Reference::to('queue.serializer'),
    'queue.serializer' => [
        '__class' => \yii\queue\serializers\PhpSerializer::class,
    ],
    \PDO::class => \yii\di\Reference::to('pdo'),
    'pdo' => [
        '__class'   => \PDO::class,
        '__construct()' => [
            'dsn' => $params['db.dsn'],
            'username'  => $params['db.username'],
            'password'  => $params['db.password'],
            'options' => [],
        ]
    ],
    \yii\mutex\Mutex::class => \yii\di\Reference::to('mutex'),
    'mutex' => [
        '__class' => \yii\mutex\MysqlMutex::class
    ],
];
