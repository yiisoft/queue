<?php

declare(strict_types=1);

use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Adapter\SynchronousAdapter;
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
        'channel-definitions' => [
            'some-channel' => static fn (AdapterInterface $a): AdapterInterface => $a
                ->withChannel('some-channel')
                ->withPushMiddleware(SomeMiddleware::class)
                ->withConsumeMiddleware(AnotherMiddleware::class),
            'another-channel' => [
                'class' => SynchronousAdapter::class,
                '__construct()' => [
                    'channel' => 'another-channel',
                ],
                'withPushMiddleware()' => [SomeMiddleware::class],
                'withConsumeMiddlewares()' => [AnotherMiddleware::class],
            ],
        ],
    ],
];
