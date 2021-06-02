<?php

declare(strict_types=1);

use Yiisoft\Yii\Queue\Command\ListenCommand;
use Yiisoft\Yii\Queue\Command\RunCommand;

return [
    'yiisoft/yii-console' => [
        'commands' => [
            'queue/run' => RunCommand::class,
            'queue/listen' => ListenCommand::class,
        ],
    ],
    'yiisoft/yii-queue' => [
        'handlers' => [],
        'channel-definitions' => [],
    ],
];
