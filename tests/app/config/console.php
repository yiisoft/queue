<?php

return [
    'controllerMap' => [
        'mysql-migrate' => [
            '__class'             => \Yiisoft\Yii\Console\Controllers\MigrateController::class,
            'db'                  => 'mysql',
            'migrationPath'       => null,
            'migrationNamespaces' => [
                'yii\queue\db\migrations',
            ],
        ],
        'sqlite-migrate' => [
            '__class'             => \Yiisoft\Yii\Console\Controllers\MigrateController::class,
            'db'                  => 'sqlite',
            'migrationPath'       => null,
            'migrationNamespaces' => [
                'yii\queue\db\migrations',
            ],
        ],
        'pgsql-migrate' => [
            '__class'             => \Yiisoft\Yii\Console\Controllers\MigrateController::class,
            'db'                  => 'pgsql',
            'migrationPath'       => null,
            'migrationNamespaces' => [
                'yii\queue\db\migrations',
            ],
        ],
        'benchmark' => \yii\queue\tests\app\benchmark\Controller::class,
    ],
];
