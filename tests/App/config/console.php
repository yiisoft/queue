<?php

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Yiisoft\Db\Migration;
use Yiisoft\Yii\Console\Application;
use Yiisoft\Yii\Queue\Tests\App\Benchmark\Controller;

return [
    Application::class => static function (ContainerInterface $container) {
        $commands = [
            'mysql-migrate' => [
                '__class' => Migration::class,
                'db' => 'mysql',
                'migrationPath' => null,
                'migrationNamespaces' => [
                    'Yiisoft\Yii\Queue\Drivers\Db\migrations',
                ],
            ],
            'sqlite-migrate' => [
                '__class' => Migration::class,
                'db' => 'sqlite',
                'migrationPath' => null,
                'migrationNamespaces' => [
                    'Yiisoft\Yii\Queue\Drivers\Db\migrations',
                ],
            ],
            'pgsql-migrate' => [
                '__class' => Migration::class,
                'db' => 'pgsql',
                'migrationPath' => null,
                'migrationNamespaces' => [
                    'Yiisoft\Yii\Queue\Drivers\Db\migrations',
                ],
            ],
            'benchmark' => Controller::class,
        ];

        $app = new Application();
        $loader = new ContainerCommandLoader(
            $container,
            $commands
        );
        $app->setCommandLoader($loader);

        return $app;
    },
];
