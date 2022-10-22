<?php

declare(strict_types=1);

use Yiisoft\Yii\Queue\Command\ListenCommand;
use Yiisoft\Yii\Queue\Command\RunCommand;
use Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\FailureStrategyMiddleware;

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
    'middlewares-push' => [],
    'middlewares-consume' => [
        FailureStrategyMiddleware::class,
    ],
];
