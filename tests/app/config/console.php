<?php

return [
    'controllerMap' => [
        'mysql-migrate' => [
            '__class'             => \yii\console\controllers\MigrateController::class,
            'db'                  => 'mysql',
            'migrationPath'       => null,
            'migrationNamespaces' => [
                'Yiisoft\Yii\Queue\Drivers\Db\migrations',
            ],
        ],
        'sqlite-migrate' => [
            '__class'             => \yii\console\controllers\MigrateController::class,
            'db'                  => 'sqlite',
            'migrationPath'       => null,
            'migrationNamespaces' => [
                'Yiisoft\Yii\Queue\Drivers\Db\migrations',
            ],
        ],
        'pgsql-migrate' => [
            '__class'             => \yii\console\controllers\MigrateController::class,
            'db'                  => 'pgsql',
            'migrationPath'       => null,
            'migrationNamespaces' => [
                'Yiisoft\Yii\Queue\Drivers\Db\migrations',
            ],
        ],
        'benchmark' => \Yiisoft\Yii\Queue\Tests\App\Benchmark\Controller::class,
    ],
];
