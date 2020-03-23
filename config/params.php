<?php

declare(strict_types=1);

use Yiisoft\Yii\Queue\Commands\ListenCommand;
use Yiisoft\Yii\Queue\Commands\RunCommand;

return [
    'console' => [
        'commands' => [
            'queue/run' => RunCommand::class,
            'queue/listen' => ListenCommand::class,
        ],
    ],
];
