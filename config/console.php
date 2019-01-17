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
    \PDO::class => Reference::to('pdo'),
    'pdo' => [
        '__class'   => \PDO::class,
        '__construct()' => [
            'dsn' => $params['db.dsn'],
            'username'  => $params['db.username'],
            'password'  => $params['db.password'],
            'options' => [],
        ]
    ],
    \yii\mutex\Mutex::class => Reference::to('mutex'),
    'mutex' => [
        '__class' => \yii\mutex\MysqlMutex::class
    ],
];
