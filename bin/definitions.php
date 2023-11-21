<?php

declare(strict_types=1);

use Symfony\Component\Console\Application;
use Yiisoft\Definitions\ReferencesArray;
use Yiisoft\Yii\Queue\Command\ListenCommand;
use Yiisoft\Yii\Queue\Command\RunCommand;

return [
    Application::class => [
        '__construct()' => [
            'name' => 'Yii Queue Tool',
            'version' => '1.0.0',
        ],
        'addCommands()' => [
            ReferencesArray::from(
                [
                    RunCommand::class,
                    ListenCommand::class,
                ],
            ),
        ],
    ],
];
