<?php

use yii\di\Reference;

return [
    'app' => [
        'bootstrap' => [
            'queue' => 'queue',
        ],
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
