<?php
return [
    'controllerMap' => [
        'mysql-migrate' => [
            '__class' => \yii\console\controllers\MigrateController::class,
            'db' => 'mysql',
            'migrationPath' => null,
            'migrationNamespaces' => [
                'yii\queue\db\migrations',
            ],
        ],
        'sqlite-migrate' => [
            '__class' => \yii\console\controllers\MigrateController::class,
            'db' => 'sqlite',
            'migrationPath' => null,
            'migrationNamespaces' => [
                'yii\queue\db\migrations',
            ],
        ],
        'pgsql-migrate' => [
            '__class' => \yii\console\controllers\MigrateController::class,
            'db' => 'pgsql',
            'migrationPath' => null,
            'migrationNamespaces' => [
                'yii\queue\db\migrations',
            ],
        ],
        'benchmark' => \yii\queue\tests\app\benchmark\Controller::class,
    ],
];
