<?php

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Yiisoft\Yii\Console\Application;
use Yiisoft\Yii\Queue\Tests\App\Benchmark\Controller;

return [
    Application::class => static function (ContainerInterface $container) {
        $commands = [
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
